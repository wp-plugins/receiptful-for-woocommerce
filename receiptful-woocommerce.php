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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
	 * Instance of Receiptful_WooCommerce.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $instance The instance of Receiptful_WooCommerce.
	 */
	private static $instance;

	/**
	 * Construct.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->init();

	}


	/**
	 * Instance.
	 *
	 * An global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 1.0.0
	 * @return object Instance of the class.
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

		if ( is_admin() ) :

			/**
			 * Admin settings class
			 */
			require_once 'includes/admin/class-wc-receiptful-admin.php';
			$this->admin = new WC_Receiptful_Admin();

		endif;

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
	 * Saves the version of the plugin to the database and displays an activation notice on where users
	 * can access the new options.
	 */
	public function plugin_activation() {

		if( RECEIPTFUL_WOOCOMMERCE != get_option( 'receiptful_woocommerce_version' ) ) {

			add_option( 'receiptful_woocommerce_version', RECEIPTFUL_WOOCOMMERCE );

			$html = '<div class="updated">';
			$html .= '<p>';
			$html .= __( 'Receiptful has been activated. Please click <a href="admin.php?page=wc-settings&tab=receiptful">here</a> to add your API key & supercharge your receipts.', 'receiptful' );
			$html .= '</p>';
			$html .= '</div><!-- /.updated -->';

			echo $html;

		} // end if

	} // end plugin_activation


	/**
	 * Plugin page links
	 *
	 * @param array $links
	 * @return array
	 */
	function receiptful_plugin_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=receiptful' ) . '">' . __( 'Settings', 'receiptful' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}




}

if ( ! function_exists( 'Receiptful_Init' ) ) :

	// Set the version of this plugin
	if( ! defined( 'RECEIPTFUL_WOOCOMMERCE' ) ) {
	  define( 'RECEIPTFUL_WOOCOMMERCE', '1.0' );
	} // end if

	/**
	 * The main function responsible for returning the Receiptful_WooCommerce object.
	 *
	 * Use this function like you would a global variable, except without needing to declare the global.
	 *
	 * @since 1.0.0
	 *
	 * @return object Receiptful_WooCommerce class object.
	 */

	/**
	 * WC Detection
	 */
	if ( ! function_exists( 'is_woocommerce_active' ) ) {
		function is_woocommerce_active() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
		}
	}

	if ( is_woocommerce_active() ) {
		function Receiptful_Init() {
			return Receiptful_WooCommerce::instance();
		}

		Receiptful_Init();

		/**
		 * Receiptful CRON events
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/functions-receiptful-cron.php';

	}
endif;


function Receiptful() {
	return Receiptful_WooCommerce::instance();
}


