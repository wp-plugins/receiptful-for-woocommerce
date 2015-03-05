<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_Admin.
 *
 * Admin class.
 *
 * @class		Receiptful_Admin
 * @version		1.0.0
 * @author		Receiptful
 */
class Receiptful_Admin {

	/**
	 * URL for the store owner's Profile page in the Receiptful app.
	 * @var string
	 */
	public $receiptful_profile_url = 'https://app.receiptful.com/profile';

	/**
	 * URL for the store owner's Template in the Receiptful app.
	 * @var string
	 */
	public $receiptful_template_url = 'https://app.receiptful.com/template';

	/**
	 * URL for the store owner's Dashboard in the Receiptful app.
	 * @var string
	 */
	public $receiptful_stats_url = 'https://app.receiptful.com/dashboard';


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->hooks();

	}


	/**
	 * Class hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Add WC settings tab
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'settings_tab' ), 60 );

		// Settings page contents
		add_action( 'woocommerce_settings_receiptful', array( $this, 'settings_page' ) );

		// Save settings page
		add_action( 'woocommerce_update_options_receiptful', array( $this, 'update_options' ) );

	}


	/**
	 * Settings tab.
	 *
	 * Add a WooCommerce settings tab for the Receiptful settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param	array	$tabs	Array of default tabs used in WC.
	 * @return	array			All WC settings tabs including newly added.
	 */
	public function settings_tab( $tabs ) {

		$tabs['receiptful'] = __( 'Receiptful', 'woocommerce-receiptful' );

		return $tabs;

	}


	/**
	 * Settings page array.
	 *
	 * Get settings page fields array.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of settings for the settings page.
	 */
	public function get_settings() {

		$settings = apply_filters( 'woocommerce_receiptful_settings', array(

			array(
				'title'		=> __( 'Receiptful General', 'woocommerce-receiptful' ),
				'type'		=> 'title',
				'desc'		=> sprintf(__("To get started with Receiptful, please add your API key (<a href='%s' target='_blank'>which you can find here</a>) and save the settings.", 'receiptful'), $this->receiptful_profile_url),
				'id'		=> 'receiptful_general',
			),
			array(
				'title'		=> __( 'API Key', 'woocommerce-receiptful' ),
				'desc'		=> '',
				'id'		=> 'receiptful_api_key',
				'default'	=> '',
				'type'		=> 'text',
				'autoload'	=> false
			),
			array(
				'type'		=> 'sectionend',
				'id'		=> 'receiptful_end'
			),
			array(
				'title'		=> '',
				'type'		=> 'title',
				'desc'		=> sprintf(__("<a href='%s'>Edit My Template</a> | <a href='%s'>View Statistics</a>", 'receiptful'),
								$this->receiptful_template_url, $this->receiptful_stats_url),
				'id'		=> 'receiptful_links',
			),
			array(
				'type'		=> 'sectionend',
				'id'		=> 'receiptful_end'
			),

		) );

		return $settings;

	}


	/**
	 * Settings page content.
	 *
	 * Output settings page content via WooCommerce output_fields() method.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {

		WC_Admin_Settings::output_fields( $this->get_settings() );

	}


	/**
	 * Save settings.
	 *
	 * Save settings based on WooCommerce save_fields() method.
	 *
	 * @since 1.0.0
	 */
	public function update_options() {

		WC_Admin_Settings::save_fields( $this->get_settings() );

	}


}
