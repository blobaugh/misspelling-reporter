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

		// The forth parameter disables autoload since option(s) are not essential to plugin's functionality.
		add_option('missr_options', array( 'persist_data_on_uninstall' => '1'), '', 'no');

		if(is_admin()) {
			add_action( 'admin_menu', array( $this, 'missr_add_page') );
			add_action('admin_init', array( $this, 'missr_admin_init') );
		}
	}

	/**
	 * Enqueue some stuff
	 *
	 * @since 0.6
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() ) {
			$post_id = NULL;
			$is_singular = false;
		} else {
			$post_id = get_the_ID();
			$is_singular = true;
		}

		// Front end text selection code
		wp_enqueue_script( 'missr_highlighter', MISSR_PLUGIN_URL . 'js/highlighter.js', array( 'jquery' ) );
		wp_enqueue_style( 'misspelling_style', MISSR_PLUGIN_URL . 'style.css' );

		$info = array(
			'post_id'         => $post_id,
			'is_singular'       => $is_singular,
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

		// do nothing if post doesn't exist
		if( ! get_post( $original_post_id ) ) {
			die;
		}

		$typo             = sanitize_text_field( $_POST['selected'] );

		$typo_check = $this->typo_check( $original_post_id, $typo );

		if ( ! empty( $typo_check ) ) {
			die( _e( 'Misspelling Already Reported', 'missr' ) );
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

	/* Adds submenu to the settings menu */
	public function missr_add_page() {
		add_options_page('Misspelling Reporter', 'Misspelling Reporter',
			'manage_options', 'missr_plugin', array( $this, 'missr_options_page') );
	}

	/* Template for settings page */
	public function missr_options_page() {
		?>

		<div class="wrap">
		<?php screen_icon(); ?>
			<h2>Misspelling Reporter</h2>
			<form action="options.php" method="post" >
		<?php
			settings_fields('missr_plugin_options');
			do_settings_sections('missr_plugin');
			submit_button();
		?>
			</form>
		</div>
		<?php
	}

	/* Configure settings page */
	public function missr_admin_init() {
		// add the option to whitelist_options
		register_setting(
			'missr_plugin_options',
			'missr_options',
			array( $this, 'missr_plugin_validate_options')
		);

		add_settings_section(
			'missr_plugin_main',
			'',
			'',
			'missr_plugin' // slug-name of the settings page
		);

		add_settings_field(
			'missr_plugin_select_form',
			'Plugin Data',
			array( $this, 'missr_plugin_setting_input'),
			'missr_plugin',
			'missr_plugin_main' // the section of settings page in which to show fields(defined in add_settings_section)
		);
	}

	public function missr_plugin_validate_options( $input ) {

		if( '1' === $input or '0' !== $input )
			$input = array( 'persist_data_on_uninstall' => '1' );
		else
			$input = array( 'persist_data_on_uninstall' => '0' );

		return $input;
	}

	/* Markup for settings page */
	public function missr_plugin_setting_input() { ?>

		<label for="keep_data">
			<input name="missr_options" type="radio" id="keep_data" value="1"
		<?php
			$options = get_option('missr_options');
			checked( '1', $options[ 'persist_data_on_uninstall' ] );
		?> />
			Keep all plugin data upon plugin removal
		</label><br />

		<label for="delete_data">
				<input name="missr_options" type="radio" id="delete_data" value="0"
		<?php
			$options = get_option('missr_options');
			checked('0', $options['persist_data_on_uninstall' ] );
		?> />
			Delete all data upon plugin removal
		</label>

		<?php
	}

} // Misspelt
} // class exists

$load = new Misspelt;

