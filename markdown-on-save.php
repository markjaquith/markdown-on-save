<?php
/*
Plugin Name: Markdown on Save
Description: Allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately, so you can deactivate this plugin and your posts won't spew out Markdown.
Version: 1.1.5
Author: Mark Jaquith
Author URI: http://coveredwebservices.com/
*/

class CWS_Markdown {
	const PM = '_cws_is_markdown';
	var $instance;
	var $kses = false;

	public function __construct() {
		$this->instance =& $this;
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		load_plugin_textdomain( 'markdown-on-save', NULL, basename( dirname( __FILE__ ) ) );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_action( 'do_meta_boxes', array( $this, 'do_meta_boxes' ), 20, 2 );
		add_filter( 'edit_post_content', array( $this, 'edit_post_content' ), 10, 2 );
		add_filter( 'edit_post_content_filtered', array( $this, 'edit_post_content_filtered' ), 10, 2 );
		add_action( 'load-post.php', array( $this, 'load' ) );
		add_action( 'xmlrpc_call', array( $this, 'xmlrpc_actions' ) );
		add_action( 'init', array( $this, 'maybe_remove_kses' ), 99 );
		add_action( 'set_current_user', array( $this, 'maybe_remove_kses' ), 99 );
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
			add_action( 'parse_query', array( $this, 'make_filterable' ), 10, 1 );
		}
		else if ( 'metaWeblog.getPost' === $xmlrpc_method ) {
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
			$post->post_content = '<!--markdown-->' . $post->post_content_filtered;
			wp_cache_delete( $post->ID, 'posts' );
			wp_cache_add( $post->ID, $post, 'posts' );
		}
	}

	public function make_filterable( $wp_query ) {
		$wp_query->set( 'suppress_filters', false );
		add_action( 'the_posts', array( $this, 'the_posts' ), 10, 2 );
	}
	
	public function the_posts( $posts, $wp_query ) {
		foreach ( $posts as $key => $post ) {
			if ( $this->is_markdown( $post->ID ) ) {
				$posts[$key]->post_content = '<!--markdown-->' . $posts[$key]->post_content_filtered;
			}
		}
		return $posts;
	}

	public function load() {
		if ( !isset( $_GET['post'] ) )
			return;
		if ( $this->is_markdown( $_GET['post'] ) )
			add_filter( 'user_can_richedit', '__return_false', 99 );
	}

	public function wp_insert_post_data( $data, $postarr ) {
		// Note, the $data array is SLASHED!
		$has_changed = false;
		if ( isset( $postarr['ID'] ) ) {
			$post = get_post( $postarr['ID'], ARRAY_A );
			$has_changed = $data['post_content'] !== addslashes( $post['post_content'] );
		}
		$nonce = isset( $postarr['_cws_markdown_nonce'] ) && wp_verify_nonce( $postarr['_cws_markdown_nonce'], 'cws-markdown-save' );
		$check = ( $nonce ) ? isset( $postarr['cws_using_markdown'] ) : false;
		$comment = false !== stripos( $data['post_content'], '<!--markdown-->' );
		$data['post_content'] = str_ireplace( '<!--markdown-->', '', $data['post_content'] );
		if ( ( $nonce && $check ) || $comment ) {
			$data['post_content_filtered'] = $data['post_content'];
			$data['post_content'] = $this->unp( Markdown( $data['post_content'] ) );
			if ( $this->kses )
				$data['post_content'] = wp_kses_post( $data['post_content'] );
			if ( $postarr['ID'] )
				update_post_meta( $postarr['ID'], self::PM, true );
		} elseif ( ( $nonce && !$check ) || $has_changed ) {
			if ( $this->kses )
				$data['post_content'] = wp_kses_post( $data['post_content'] );
			$data['post_content_filtered'] = '';
			if ( $postarr['ID'] )
				delete_post_meta( $postarr['ID'], self::PM );
		}
		return $data;
	}

	public function do_meta_boxes( $type, $context ) {
		if ( 'side' == $context && in_array( $type, array_keys( get_post_types() ) ) )
			add_meta_box( 'cws-markdown', __( 'Markdown', 'markdown-on-save' ), array( $this, 'meta_box' ), $type, 'side', 'high' );
	}

	public function meta_box() {
		global $post;
		wp_nonce_field( 'cws-markdown-save', '_cws_markdown_nonce', false, true );
		echo '<p><input type="checkbox" name="cws_using_markdown" id="cws_using_markdown" value="1" ';
		checked( !! get_post_meta( $post->ID, self::PM, true ) );
		echo ' /> <label for="cws_using_markdown">' . __( 'This post is formatted with Markdown', 'markdown-on-save' ) . '</label></p>';
	}

	private function unp( $content ) {
		return preg_replace( "#<p>(.*?)</p>(\n|$)#", '$1$2', $content );
	}

	private function is_markdown( $id ) {
		return !! get_post_meta( $id, self::PM, true );
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

require_once( dirname( __FILE__) . '/markdown-extra/markdown-extra.php' );
new CWS_Markdown;
