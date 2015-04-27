<?php
/**
 * Plugin Name: 	Receiptful for WooCommerce
 * Plugin URI: 		http://receiptful.com
 * Description: 	Receiptful replaces and supercharges the default WooCommerce receipts. Just activate, add API and be awesome.
 * Author: 			Receiptful
 * Author URI: 		http://receiptful.com
 * Version: 		1.1.5
 * Text Domain: 	receiptful
 * Domain Path: 	/languages/
 *
 * @package		Receiptful-WooCommerce
 * @author		Receiptful
 * @copyright	Copyright (c) 2012-2014, Receiptful
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Receiptful_WooCommerce
 *
 * Main class initializes the plugin
 *
 * @class		Receiptful_WooCommerce
 * @version		1.0.0
 * @author		Receiptful
 */
class Receiptful_WooCommerce {


	/**
	 * Plugin version.
	 *
	 * @since 1.0.1
	 * @var string $version Plugin version number.
	 */
	public $version = '1.1.4';


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
				return false;
			}
		}

		// Initialize plugin parts
		$this->init();

		// Plugin hooks
		$this->hooks();

		// Textdomain
		$this->load_textdomain();

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
			require_once plugin_dir_path( __FILE__ ) . '/includes/admin/class-receiptful-admin.php';
			$this->admin = new Receiptful_Admin();

		}

		/**
		 * Main Receiptful class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-receiptful-email.php';
		$this->email = new Receiptful_Email();

		/**
		 * Front end class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-receiptful-front-end.php';
		$this->front_end = new Receiptful_Front_End();

		/**
		 * Receiptful API
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-receiptful-api.php';
		$this->api = new Receiptful_Api();

		/**
		 * Receiptful Products sync
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-receiptful-products.php';
		$this->products = new Receiptful_Products();

		/**
		 * Subscription integration.
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/integrations/woocommerce-subscriptions.php';

		/**
		 * WPML Compatibility
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/integrations/wpml.php';

	}


	/**
	 * Hooks.
	 *
	 * Initial plugin hooks.
	 *
	 * @since 1.1.1
	 */
	public function hooks() {

		// Add the plugin page Settings and Docs links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'receiptful_plugin_links' ));

		// Plugin activation message
		add_action( 'admin_notices', array( $this, 'plugin_activation' ) ) ;

		// Add tracking script
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Tracking calls
		add_action( 'wp_footer', array( $this, 'print_scripts' ), 99 );

		// Track order
		add_action( 'woocommerce_thankyou', array( $this, 'thank_you_tracking' ) );

		// Helper functions
		add_action( 'plugins_loaded', array( $this, 'load_helper_functions' ) );

	}


	/**
	 * Textdomain.
	 *
	 * Load the textdomain based on WP language.
	 *
	 * @since 1.1.1
	 */
	public function load_textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale(), 'receiptful' );

		// Load textdomain
		load_textdomain( 'receiptful', WP_LANG_DIR . '/receiptful-for-woocommerce/receiptful-' . $locale . '.mo' );
		load_plugin_textdomain( 'receiptful', false, basename( dirname( __FILE__ ) ) . '/languages' );

	}


	/**
	 * Enqueue script.
	 *
	 * Enqueue Receiptful tracking script to track click conversions.
	 *
	 * @since 1.0.2
	 */
	public function enqueue_scripts() {

		// Add tracking script
		wp_enqueue_script( 'receiptful-tracking', 'https://media.receiptful.com/scripts/tracking.js', array(), $this->version, false );

	}


	/**
	 * Print script.
	 *
	 * Print initializing javascript.
	 *
	 * @since 1.0.2
	 */
	public function print_scripts() {

		if ( ! is_checkout() || ( is_checkout() && ! isset( $_GET['order-received'] ) ) ) {
			?><script type='text/javascript'>
				document.addEventListener('DOMContentLoaded', function(event) {
					if ( typeof Receiptful !== 'undefined' ) {
						Receiptful.setTrackingCookie();
					}
				});
			</script><?php
		}

	}


	/**
	 * Track order.
	 *
	 * Track the click conversion on the order thank-you page.
	 *
	 * @since 1.0.2
	 *
	 * @param int $order_id ID of the order being completed.
	 */
	public function thank_you_tracking( $order_id ) {

		$order					= wc_get_order( $order_id );
		$coupon_tracking_code	= '';

		// Register the usage of Receiptful coupons
		foreach ( $order->get_used_coupons() as $coupon ) {

			$coupon_id					= wc_get_coupon_by_code( $coupon );
			$is_receiptful_coupon		= get_post_meta( $coupon_id, 'receiptful_coupon', true );
			$coupon_code				= esc_js( strtoupper( $coupon ) );

			if ( 'yes' == $is_receiptful_coupon ) {
				$coupon_tracking_code = "Receiptful.conversion.couponCode = '$coupon_code';";
			}

		}

		?><script type='text/javascript'>
			document.addEventListener('DOMContentLoaded', function(event) {
				if ( typeof Receiptful !== 'undefined' ) {
					Receiptful.conversion.reference = '<?php echo esc_js( ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'receiptful' ) ) ); ?>';
					Receiptful.conversion.amount	= <?php echo esc_js( $order->get_total() ); ?>;
					Receiptful.conversion.currency	= '<?php echo esc_js( $order->get_order_currency() ); ?>';
					<?php echo $coupon_tracking_code; ?>
					Receiptful.trackConversion();
				}
			});
		</script><?php

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
			</div><?php

		}

		// Update version number if its not the same
		if ( $this->version != get_option( 'receiptful_woocommerce_version' ) ) {
			update_option( 'receiptful_woocommerce_version', $this->version );
		}

	}


	/**
	 * Plugin page link.
	 *
	 * Add a 'settings' link to the plugin on the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $links	List of existing plugin links.
	 * @return 	array			List of modified plugin links.
	 */
	function receiptful_plugin_links( $links ) {

		$links['settings'] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=receiptful' ) . '">' . __( 'Settings', 'receiptful' ) . '</a>';

		return $links;

	}


	/**
	 * Helper functions,
	 *
	 * Load helper functions after all plugins to prevent 'function already exists' errors.
	 *
	 * @since 1.0.4
	 */
	public function load_helper_functions() {

		/**
		 * Helper functions
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/receiptful-helper-functions.php';

		/**
		 * Receiptful CRON events
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/receiptful-cron-functions.php';

	}


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
