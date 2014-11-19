<?php
/**
 * Plugin Name: Receiptful for WooCommerce
 * Plugin URI: http://receiptful.com
 * Description: Receiptful replaces and supercharges the default WooCommerce receipts. Just activate, add API and be awesome.
 * Author: Receiptful
 * Author URI: http://receiptful.com
 * Version: 1.0.0
 * Text Domain: receiptful
 * Domain Path: /languages/
 *
 * @package   Receiptful-WooCommerce
 * @author    Receiptful
 * @copyright Copyright (c) 2012-2014, Receiptful
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Class Receiptful_WooCommerce
 *
 *	Main class initializes the plugin
 *
 *	@class		Receiptful_WooCommerce
 *	@version	1.0.0
 *	@author		Receiptful
 */
class Receiptful_WooCommerce {


	/**
	 * Plugin version.
	 *
	 * @since 1.0.1
	 * @var string $version Plugin version number.
	 */
	public $version = '1.0.1';


	/**
	 * Plugin file.
	 *
	 * @since 1.0.0
	 * @var string $file Plugin file path.
	 */
	public $file = __FILE__;


	/**
	 * Instance of Receiptful_WooCommerce.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var object $instance The instance of Receiptful_WooCommerce.
	 */
	protected static $instance;


	/**
	 * Constructor.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Check if WooCommerce is active
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
				return;
			}
		}

		// Initialize plugin parts
		$this->init();

		do_action( 'receiptful_loaded' );

	}


	/**
	 * Instance.
	 *
	 * An global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 1.0.0
	 *
	 * @return Receiptful_WooCommerce Instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	/**
	 * init.
	 *
	 * Initialize plugin parts.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		if ( is_admin() ) {

			/**
			 * Admin settings class
			 */
			require_once 'includes/admin/class-wc-receiptful-admin.php';
			$this->admin = new WC_Receiptful_Admin();

		}

		/**
		 * Main Receiptful class
		 */
		require_once 'includes/class-receiptful-email.php';
		$this->email = new Receiptful_Email();

		/**
		 * Receiptful API
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-receiptful-api.php';
		$this->api = new Receiptful_Api();


		// Add the plugin page Settings and Docs links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'receiptful_plugin_links' ));

		// Plugin activation message
		add_action( 'admin_notices', array( $this, 'plugin_activation' ) ) ;

	}


	/**
	 * Plugin activation.
	 *
	 * Saves the version of the plugin to the database and displays an
	 * activation notice on where users can access the new options.
	 *
	 * @since 1.0.0
	 */
	public function plugin_activation() {

		$api_key = get_option( 'receiptful_api_key' );
		if ( empty( $api_key ) ) {

			add_option( 'receiptful_woocommerce_version', $this->version );

			?><div class="updated">
				<p><?php
					_e( 'Receiptful has been activated. Please click <a href="admin.php?page=wc-settings&tab=receiptful">here</a> to add your API key & supercharge your receipts.', 'receiptful' );
				?></p>
			</div><!-- /.updated --><?php

		}

		// Update version number if its not the same
		if ( $this->version != get_option( 'receiptful_woocommerce_version' ) ) {
			update_option( 'receiptful_woocommerce_version', $this->version );
		}

	}


	/**
	 * Plugin page links
	 *
	 * @param array $links
	 * @return array
	 */
	function receiptful_plugin_links( $links ) {

		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=receiptful' ) . '">' . __( 'Settings', 'receiptful' ) . '</a>';

		return $links;

	}


	/**
	 * Coupon by code.
	 *
	 * Get the coupon ID by the coupon code.
	 *
	 * @param 	string $coupon_code Code that is used as coupon code.
	 * @return
	 */
	public function get_coupon_by_code( $coupon_code ) {

		global $wpdb;

		$coupon_id = $wpdb->get_var( $wpdb->prepare( apply_filters( 'woocommerce_coupon_code_query', "
			SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = 'shop_coupon'
			AND post_status = 'publish'
		" ), $coupon_code ) );

		 if ( ! $coupon_id ) {
		 	return false;
		 } else {
		 	return $coupon_id;
		 }

	}


}


/**
 * Receiptful CRON events
 */
require_once plugin_dir_path( __FILE__ ) . '/includes/functions-receiptful-cron.php';


/**
 *  After plugins are loaded check compatibility based on existence of WooCommerce functions
 */
add_action( 'plugins_loaded', 'receiptful_compatibility_check' );

function receiptful_compatibility_check() {
	/**
	 * Receiptful compatibility functions
	 */
	require_once plugin_dir_path( __FILE__ ) . '/includes/functions-receiptful-compatibility.php';

}


/**
 * The main function responsible for returning the Receiptful_WooCommerce object.
 *
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * Example: <?php Receiptful()->method_name(); ?>
 *
 * @since 1.0.0
 *
 * @return object Receiptful_WooCommerce class object.
 */
if ( ! function_exists( 'Receiptful' ) ) {

 	function Receiptful() {
		return Receiptful_WooCommerce::instance();
	}

}

Receiptful();
