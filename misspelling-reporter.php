<?php

/**
 * Plugin Name: Misspelling Reporter
 * Plugin URI: https://github.com/blobaugh/misspelling-reporter
 * Description: Allows users to highlight misspelled text and report to the site/article admins. Inspired by #BeachPress2013
 * Version: 0.6.5
 * Author: Ben Lobaugh
 * Author URI: http://ben.lobaugh.net
 */

if ( ! class_exists( 'Misspelt' ) ) {
class Misspelt {

	static $instance;

	/**
	 * Instance
	 */
	public function __construct() {
		self::$instance = $this;

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Init
	 *
	 * @since 0.6.2
	 */
	public function init() {
		define( 'MISSR_PLUGIN_DIR' , trailingslashit( dirname( __FILE__ ) ) );
		define( 'MISSR_PLUGIN_URL' , trailingslashit( WP_PLUGIN_URL . '/' . basename( __DIR__ ) ) );
		define( 'MISSR_PLUGIN_FILE', MISSR_PLUGIN_DIR . basename( __DIR__ ) . '.php' );

		load_plugin_textdomain( 'missr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( 'wp_enqueue_scripts'         , array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_missr_report'       , array( $this, 'ajax_report' ) );
		add_action( 'wp_ajax_nopriv_missr_report', array( $this, 'ajax_report' ) );
	}

	/**
	 * Enqueue some stuff
	 *
	 * @since 0.6
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() )
			return;

		// Front end text selection code
		wp_enqueue_script( 'missr_highlighter', MISSR_PLUGIN_URL . 'js/highlighter.js', array( 'jquery' ) );
		wp_enqueue_style( 'misspelling_style', MISSR_PLUGIN_URL . 'style.css' );

		$info = array(
			'post_id'         => get_the_ID(),
			'ajaxurl'         => admin_url( 'admin-ajax.php', 'relative' ),
			'success'         => __( 'Success!', 'missr' ),
			'click_to_report' => __( 'Click to report misspelling', 'missr' )
		);

		wp_localize_script(
			'missr_highlighter',
			'post',
			$info
		);
	}

	/**
	 * Ajax some stuff
	 *
	 * @since 0.6
	 */
	public function ajax_report() {
		$original_post_id = absint( $_POST['post_id'] );
		$typo             = sanitize_text_field( $_POST['selected'] ); 

		if ( 0 != count( $this->typo_check( $original_post_id, $typo ) ) ) {
			_e( 'Misspelling Already Reported', 'missr' );
			die; 
		}
		
		$args = array(
			'post_type'   => 'missr_report',
			'post_title'  => $typo,
			'post_parent' => $original_post_id
		);

		wp_insert_post( $args );
		
		$this->email_notify( $original_post_id );
		
		die;
	}

	/**
	 * Notify some stuff
	 *
	 * @since 0.6.3
	 *
	 * @param int $post_id The supplied post id.
	 */
	public function email_notify( $post_id ) {
		$post = get_post( $post_id );

		$subject = __( 'Misspelling Report', 'missr' );

		$body  = __( 'Post: ', 'missr' ) . get_permalink( $post->ID ) . "\n\n";
		$body .= __( 'Misspelling: ', 'missr' ) . esc_html( $_POST['selected'] );

		// Email site admin
		wp_mail( get_option( 'admin_email' ), $subject, $body );

		// mail post author
		$user = get_userdata( $post->post_author );

		if ( get_option( 'admin_email' ) !== $user->user_email )
			wp_mail( $user->user_email, $subject, $body );

		_e( 'Misspelling Reported', 'missr' );
	}
	
	/**
	 * Check for typo
	 *
	 * @since 0.6.3
	 *
	 * @param int $post_id The supplied post id.
	 * @param string $typo The supplied typo.
	 */
	public function typo_check( $post_id, $typo ) {
		// check if the typo has been submitted previously
		$args = array(
			's'           => $typo,
			'post_type'   => 'missr_report',
			'post_status' => 'draft',
			'post_parent' => $post_id
		); 
		
		return get_posts( $args );
		
	}

} // Misspelt
} // class exists

$load = new Misspelt;
