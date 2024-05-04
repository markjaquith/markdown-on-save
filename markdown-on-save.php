<?php
/*
Plugin Name: Markdown on Save
Description: Allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately, so you can deactivate this plugin and your posts won't spew out Markdown.
Version: 1.3.1
Author: Mark Jaquith
Author URI: http://coveredweb.com/
*/

use \Michelf\MarkdownExtra;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	wp_die('Markdown on Save: Markdown library not found. Did you run `composer install`?');
}

class CWS_Markdown {
	const PM = '_cws_is_markdown';
	const PMD = '_cws_is_markdown_gmt';
	const FLAG = '<!--markdown-->';
	public $kses = false;
	public $debug = false;
	public $monitoring_for_insert_post = [];
	public $monitoring_for_insert_post_child = [];

	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		load_plugin_textdomain( 'markdown-on-save', NULL, basename( dirname( __FILE__ ) ) );
		add_filter( 'wp_insert_post_data', [ $this, 'wp_insert_post_data' ], 10, 2 );
		// add_action( 'do_meta_boxes', [ $this, 'do_meta_boxes' ], 20, 2 );
		add_action( 'post_submitbox_misc_actions', [ $this, 'submitbox_actions' ] );
		add_filter( 'edit_post_content', [ $this, 'edit_post_content' ], 10, 2 );
		add_filter( 'edit_post_content_filtered', [ $this, 'edit_post_content_filtered' ], 10, 2 );
		add_action( 'load-post.php', [ $this, 'load' ] );
		add_action( 'load-post.php', [ $this, 'enqueue' ] );
		add_action( 'load-post-new.php', [ $this, 'enqueue' ] );
		add_action( 'xmlrpc_call', [ $this, 'xmlrpc_actions' ] );
		add_action( 'init', [ $this, 'maybe_remove_kses' ], 99 );
		add_action( 'set_current_user', [ $this, 'maybe_remove_kses' ], 99 );
		add_action( 'wp_insert_post', [ $this, 'wp_insert_post' ] );
		add_action( 'wp_restore_post_revision', [ $this, 'wp_restore_post_revision' ], 10, 2 );
		add_filter( '_wp_post_revision_fields', [ $this, '_wp_post_revision_fields' ] );
	}

	public function maybe_remove_kses() {
		if (
			// Filters return true if they existed before you removed them
			remove_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' ) &&
			remove_filter( 'content_save_pre', 'wp_filter_post_kses' )
		) {
			$this->kses = true;
		}
	}

	public function xmlrpc_actions($xmlrpc_method) {
		if ( 'metaWeblog.getRecentPosts' === $xmlrpc_method ) {
			add_action( 'parse_query', [ $this, 'make_filterable' ], 10, 1 );
		} elseif ( 'metaWeblog.getPost' === $xmlrpc_method ) {
			$this->prime_post_cache();
		} elseif ( 'wp.getPosts' === $xmlrpc_method ) {
			add_action( 'parse_query', [ $this, 'make_filterable' ], 10, 1 );
		} elseif ( 'wp.getPost' === $xmlrpc_method ) {
			$this->prime_post_cache();
		}
	}

	private function prime_post_cache() {
		global $wp_xmlrpc_server;
		$params = $wp_xmlrpc_server->message->params;
		$post_id = array_shift( $params );
		// prime the post cache
		if ( $this->is_markdown( $post_id ) ) {
			$post = get_post( $post_id );
			$post->post_content = self::FLAG . "\n\n" . $post->post_content_filtered;
			wp_cache_delete( $post->ID, 'posts' );
			wp_cache_add( $post->ID, $post, 'posts' );
		}
	}

	public function _wp_post_revision_fields( $fields ) {
		$fields['post_content_filtered'] = __( 'Markdown content', 'markdown-on-save' );
		return $fields;
	}

	public function make_filterable( $wp_query ) {
		$wp_query->set( 'suppress_filters', false );
		add_action( 'the_posts', [ $this, 'the_posts' ], 10, 2 );
	}

	public function the_posts( $posts, $wp_query ) {
		foreach ( $posts as $key => $post ) {
			if ( $this->is_markdown( $post->ID ) ) {
				$posts[$key]->post_content = self::FLAG . "\n\n" . $posts[$key]->post_content_filtered;
			}
		}
		return $posts;
	}

	public function enqueue() {
		wp_enqueue_script( 'markdown-on-save', plugin_dir_url( __FILE__ ) . '/js/markdown-on-save.js', [ 'jquery' ], '20120426' );
	}

	public function load() {
		if ( !isset( $_GET['post'] ) )
			return;
		if ( $this->is_markdown( $_GET['post'] ) )
			add_filter( 'user_can_richedit', '__return_false', 99 );
	}

	public function wp_restore_post_revision( $post_id, $revision_id ) {
		if ( $this->is_markdown( $revision_id ) ) {
			$revision = get_post( $revision_id, ARRAY_A );
			$post = get_post( $post_id, ARRAY_A );
			$post['post_content'] = $revision['post_content_filtered']; // Yes, we put it in post_content, because our wp_insert_post_data() expects that
			$post['force_markdown'] = true;
			wp_update_post( $post );
		}
	}

	public function wp_insert_post( $post_id ) {
		$post_parent = get_post_field( 'post_parent', $post_id );
		if ( isset( $this->monitoring_for_insert_post[$post_id] ) ) {
			unset( $this->monitoring_for_insert_post[$post_id] );
			$this->set_markdown( $post_id );
		} elseif ( isset( $this->monitoring_for_insert_post_child[$post_parent] ) ) {
			unset( $this->monitoring_for_insert_post_child[$post_parent] );
			$this->set_markdown( $post_id );
		} else {
			return $post_id;
		}
	}

	public function format( $text ) {
		return MarkdownExtra::defaultTransform( $text );
	}

	public function wp_insert_post_data( $data, $postarr ) {
		// Note, the $data array is SLASHED!
		$has_changed = false;
		if ( isset( $postarr['ID'] ) ) {
			$post_meta_post_id = $postarr['ID'];
			$post = get_post( $postarr['ID'], ARRAY_A );
			$has_changed = $data['post_content'] !== addslashes( $post['post_content'] ?? '' );
			// Note that $has_changed is only correct in a non-Markdown-aware saving mode.
		} elseif ( isset( $postarr['post_parent'] ) && $postarr['post_parent'] ) {
			$post = get_post( $postarr['post_parent'], ARRAY_A );
		}
		$nonce = isset( $postarr['_cws_markdown_nonce'] ) && wp_verify_nonce( $postarr['_cws_markdown_nonce'], 'cws-markdown-save' );
		$autosave_and_was_markdown = defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE && isset( $post_meta_post_id ) && $this->is_markdown( $post_meta_post_id );
		$revision_and_was_markdown = 'revision' == $postarr['post_type'] && $this->is_markdown( $postarr['post_parent'] );
		$check = ( $nonce ) ? isset( $postarr['cws_using_markdown'] ) : false;
		$comment = false !== stripos( $data['post_content'], self::FLAG );
		$force_markdown = isset( $postarr['force_markdown'] ) && $postarr['force_markdown'];

		$data['post_content'] = trim( str_ireplace( self::FLAG, '', $data['post_content'] ) );
		if ( ( $nonce && $check ) || $comment || $autosave_and_was_markdown || $force_markdown || $revision_and_was_markdown ) {
			if ( $revision_and_was_markdown && !$has_changed ) {
				// Copying to a revision from the current post. So grab it from the current post.
				$data['post_content'] = addslashes( $post['post_content_filtered'] );
			}
			$data['post_content_filtered'] = $data['post_content'];
			$data['post_content'] = addslashes( $this->unp( $this->format( stripslashes( $data['post_content'] ) ) ) );
			if ( $this->kses )
				$data['post_content'] = wp_kses_post( $data['post_content'] );
			if ( $postarr['ID'] )
				$this->monitoring_for_insert_post[$postarr['ID']] = true; // Defer this, for when we know the post_modified_gmt value
			elseif ( $revision_and_was_markdown )
				$this->monitoring_for_insert_post_child[$data['post_parent']] = true; // We may not know the ID of the revision yet, so we tell our wp_insert_post() hook it's on the way.
		} elseif ( ( $nonce && !$check ) || $has_changed ) {
			if ( $this->kses )
				$data['post_content'] = wp_kses_post( $data['post_content'] );
			$data['post_content_filtered'] = '';
			if ( $postarr['ID'] )
				$this->set_not_markdown( $postarr['ID'] );
		}
		return $data;
	}

	public function submitbox_actions() {
		$markdown = isset( $GLOBALS['post'] ) && isset( $GLOBALS['post']->ID ) && $this->is_markdown( $GLOBALS['post']->ID );
		echo '
			<style>
					#submitdiv h2, #submitdiv h3 {
						margin-left: 38px;
					}
					#cws-markdown {
						position: absolute;
						top: 7px;
						left: 10px;
					}
					#cws-markdown img {
						vertical-align: bottom;
						margin-right: 10px;
					}
					#cws-markdown button {
						all: unset;
						display: inline;
						background: none;
						color: inherit;
						font: inherit;
						border: none;
						padding: 0;
						margin: 0;
						cursor: pointer;
					}
			</style>
		';
		echo '<div id="cws-markdown" style="display: none"><button type="button"><img ' . ( !$markdown ? 'style="display:none" ' : '' ) . 'class="markdown-status markdown-on" src="' . plugin_dir_url( __FILE__ ) . '/img/32x20-solid.png" width="32" height="20" /><img ' . ( $markdown ? 'style="display:none" ' : '' ) . 'class="markdown-status markdown-off" src="' . plugin_dir_url( __FILE__ ) . '/img/32x20.png" width="32" height="20" /></button></div>';
		echo '<script>document.getElementById("cws-markdown").style.display = "none";</script>';
		echo '<input style="display: none" type="checkbox" name="cws_using_markdown" id="cws_using_markdown" value="1" ';
		checked( $this->is_markdown( $GLOBALS['post']->ID ) );
		echo ' />';
		wp_nonce_field( 'cws-markdown-save', '_cws_markdown_nonce', false, true );

	}

	public function meta_box() {
		global $post;
		echo '<style>img.markdown-status:hover{cursor:pointer}</style>';
		wp_nonce_field( 'cws-markdown-save', '_cws_markdown_nonce', false, true );
		echo '<p><input type="checkbox" name="cws_using_markdown" id="cws_using_markdown" value="1" ';
		checked( $this->is_markdown( $post->ID ) );
		echo ' /> <label for="cws_using_markdown">' . __( 'This post is formatted with Markdown', 'markdown-on-save' ) . '</label></p>';
	}

	private function unp( $content ) {
		return preg_replace( "#<p>(.*?)</p>(\n|$)#", '$1$2', $content );
	}

	private function is_markdown( $post_id ) {
		$markdown = get_metadata( 'post', $post_id, self::PM, true );
		$date_match = get_post_field( 'post_modified_gmt', $post_id ) == get_metadata( 'post', $post_id, self::PMD, true );
		$is_markdown = ( 2 == $markdown && $date_match ) || ( $markdown && 2 != $markdown );
		return !! $is_markdown;
	}

	private function set_markdown( $post_id ) {
		update_metadata( 'post', $post_id, self::PMD, get_post_field( 'post_modified_gmt', $post_id ) );
		return update_metadata( 'post', $post_id, self::PM, 2 );
	}

	private function set_not_markdown( $post_id ) {
		delete_metadata( 'post', $post_id, self::PMD );
		return delete_metadata( 'post', $post_id, self::PM );
	}

	public function edit_post_content( $content, $id ) {
		if ( $this->is_markdown( $id ) ) {
			$post = get_post( $id );
			if ( $post )
				$content = $post->post_content_filtered;
		}
		return $content;
	}

	public function edit_post_content_filtered( $content, $id ) {
		if ( $this->is_markdown( $id ) ) {
			$post = get_post( $id );
			if ( $post )
				$content = $post->post_content;
		}
		return $content;
	}

}

new CWS_Markdown;
