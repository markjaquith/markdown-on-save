<?php
/*
Plugin Name: Markdown on Save
Description: Allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately, so you can deactivate this plugin and your posts won't spew out Markdown.
Version: 1.1.2-beta
Author: Mark Jaquith
Author URI: http://coveredwebservices.com/
*/

class CWS_Markdown {
	const PM = '_cws_is_markdown';
	var $instance;

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
	}

	public function wp_insert_post_data( $data, $postarr ) {
		if ( isset( $_POST['cws_using_markdown'] ) && wp_verify_nonce( $_POST['_cws_markdown_nonce'], 'cws-markdown-save' ) || false !== stripos($data['post_content'], '<!--markdown-->') ) {
			$data['post_content_filtered'] = $data['post_content'];
			$data['post_content'] = str_ireplace('<!--markdown-->', '', $data['post_content']);
			$data['post_content'] = $this->unp( Markdown( $data['post_content'] ) );
			if ( $postarr['ID'] )
				update_post_meta( $postarr['ID'], self::PM, true );
		} else {
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
		return $content;
	}

}

require_once( dirname( __FILE__) . '/markdown-extra/markdown-extra.php' );
new CWS_Markdown;
