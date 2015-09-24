<?php
/**
 * Plugin Name: WooCommerce Memberships
 * Plugin URI: http://www.woothemes.com/products/woocommerce-memberships/
 * Description: Sell memberships that provide access to restricted content, products, discounts, and more!
 * Author: WooThemes / SkyVerge
 * Author URI: http://www.woothemes.com/
 * Version: 1.3.2
 * Text Domain: woocommerce-memberships
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2014-2015 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   Memberships
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '9288e7609ad0b487b81ef6232efa5cfc', '958589' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.1.1', __( 'WooCommerce Memberships', 'woocommerce-memberships' ), __FILE__, 'init_woocommerce_memberships', array( 'minimum_wc_version' => '2.2', 'backwards_compatible' => '4.0.0' ) );

function init_woocommerce_memberships() {


/**
 * WooCommerce Memberships Main Plugin Class
 *
 * @since 1.0.0
 */
class WC_Memberships extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.3.2';

	/** @var WC_Memberships single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'memberships';

	/** plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-memberships';

	/** @var \WC_Memberships_Admin instance */
	public $admin;

	/** @var \WC_Memberships_Frontend instance */
	public $frontend;

	/** @var \WC_Memberships_Checkout instance */
	public $checkout;

	/** @var \WC_Memberships_Emails instance */
	public $emails;

	/** @var \WC_Memberships_Capabilities instance */
	public $capabilities;

	/** @var \WC_Memberships_Member_Discounts instance */
	public $member_discounts;

	/** @var \WC_Memberships_AJAX instance */
	public $ajax;

	/** @var \WC_Memberships_Rules instance */
	public $rules;

	/** @var \WC_Memberships_Membership_Plans instance */
	public $plans;

	/** @var \WC_Memberships_User_Memberships instance */
	public $user_memberships;

	/** @var bool helper for lazy subscriptions active check */
	private $subscriptions_active;

	/** @var bool helper for lazy user switching active check */
	private $user_switching_active;

	/** @var bool helper for lazy groups active check */
	private $groups_active;

	/** @var bool helper for lazy bookings active check */
	private $bookings_active;


	/**
	 * Initializes the plugin
	 *
	 * @since 1.0.0
	 * @return \WC_Memberships
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			self::TEXT_DOMAIN
		);

		// Include required files
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );

		add_action( 'init', array( $this, 'init' ) );

		// Make sure template files are searched for in our plugin
		add_filter( 'woocommerce_locate_template',      array( $this, 'locate_template' ), 20, 3 );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_template' ), 20, 3 );

		add_action( 'woocommerce_order_status_completed', array( $this, 'grant_membership_access' ), 11 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'grant_membership_access' ), 11 );

		// Lifecycle
		add_action( 'admin_init', array ( $this, 'maybe_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}


	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-post-types.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-emails.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-rules.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-membership-plans.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-user-memberships.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-capabilities.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-member-discounts.php' );

		// Global functions
		require_once( $this->get_plugin_path() . '/includes/wc-memberships-membership-plan-functions.php' );
		require_once( $this->get_plugin_path() . '/includes/wc-memberships-user-membership-functions.php' );

		$this->emails           = new WC_Memberships_Emails();
		$this->rules            = new WC_Memberships_Rules();
		$this->plans            = new WC_Memberships_Membership_Plans();
		$this->user_memberships = new WC_Memberships_User_Memberships();
		$this->capabilities     = new WC_Memberships_Capabilities();
		$this->member_discounts = new WC_Memberships_Member_Discounts();

		// Frontend includes
		if ( ! is_admin() ) {
			$this->frontend_includes();
		}

		// Admin includes
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$this->admin_includes();
		}

		// AJAX includes
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX  ) {
			$this->ajax_includes();
		}

		// Integrations
		$this->integration_includes();
	}


	/**
	 * Include required frontend files
	 *
	 * @since 1.0.0
	 */
	private function frontend_includes() {

		require_once( $this->get_plugin_path() . '/includes/wc-memberships-template-functions.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-shortcodes.php' );

		WC_Memberships_Shortcodes::initialize();

		require_once( $this->get_plugin_path() . '/includes/frontend/class-wc-memberships-frontend.php' );
		require_once( $this->get_plugin_path() . '/includes/frontend/class-wc-memberships-checkout.php' );
		require_once( $this->get_plugin_path() . '/includes/frontend/class-wc-memberships-restrictions.php' );

		$this->frontend     = new WC_Memberships_Frontend();
		$this->checkout     = new WC_Memberships_Checkout();
		$this->restrictions = new WC_Memberships_Restrictions();
	}


	/**
	 * Include required admin files
	 *
	 * @since 1.0.0
	 */
	private function admin_includes() {

		require_once( $this->get_plugin_path() . '/includes/admin/class-wc-memberships-admin.php' );
		$this->admin = new WC_Memberships_Admin();

		// message handler
		$this->admin->message_handler = $this->get_message_handler();
	}


	/**
	 * Include required AJAX files
	 *
	 * @since 1.0.0
	 */
	private function ajax_includes() {

		require_once( $this->get_plugin_path() . '/includes/class-wc-memberships-ajax.php' );
		$this->ajax = new WC_Memberships_AJAX();

		// checkout processes during Ajax request
		if ( empty( $this->checkout ) ) {
			require_once( $this->get_plugin_path() . '/includes/frontend/class-wc-memberships-checkout.php' );
			$this->checkout = new WC_Memberships_Checkout();
		}
	}


	/**
	 * Include required integration files
	 *
	 * @since 1.0.0
	 */
	private function integration_includes() {

		if ( $this->is_subscriptions_active() ) {
			require_once( $this->get_plugin_path() . '/includes/integrations/class-wc-memberships-integration-subscriptions.php' );
		}

		if ( $this->is_user_switching_active() ) {
			require_once( $this->get_plugin_path() . '/includes/integrations/class-wc-memberships-integration-user-switching.php' );
		}

		if ( $this->is_groups_active() ) {
			require_once( $this->get_plugin_path() . '/includes/integrations/class-wc-memberships-integration-groups.php' );
		}

		if ( $this->is_bookings_active() ) {
			require_once( $this->get_plugin_path() . '/includes/integrations/class-wc-memberships-integration-bookings.php' );
		}
	}


	/**
	 * Initialize post types
	 *
	 * @since 1.0.0
	 */
	public function init() {
		WC_Memberships_Post_Types::initialize();
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::load_translation()
	 */
	public function load_translation() {
		load_plugin_textdomain( 'woocommerce-memberships', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/**
	 * Locates the WooCommerce template files from our templates directory
	 *
	 * @since 1.0.0
	 * @param string $template Already found template
	 * @param string $template_name Searchable template name
	 * @param string $template_path Template path
	 * @return string Search result for the template
	 */
	public function locate_template( $template, $template_name, $template_path ) {

		// Tmp holder
		$_template = $template;

		if ( ! $template_path ) {
			$template_path = WC()->template_path();
		}

		// Set our base path
		$plugin_path = $this->get_plugin_path() . '/templates/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);

		// Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $template ) {
			$template = $_template;
		}

		// Return what we found
		return $template;
	}


	/** Plugin functionality methods ***************************************/


	/**
	 * Grant customer access to membership when making a purchase
	 *
	 * This method is run also when an order is made manually in WC admin
	 *
	 * TODO: this should be refactored to separate the code that checks
	 * if a given order contains a product that grants access to a membership @MR June 2015
	 *
	 * @since 1.0.0
	 * @param int $order_id Order ID
	 */
	public function grant_membership_access( $order_id ) {

		// Get order items
		$order   = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		$items   = $order->get_items();

		// Skip if there is no user associated with this order or there are no items
		if ( ! $user_id || empty( $items ) ) {
			return;
		}

		// Get membership plans
		$membership_plans = $this->plans->get_membership_plans();

		// Bail out if there are no membership plans
		if ( empty( $membership_plans ) ) {
			return;
		}

		// Loop over all available membership plans
		foreach ( $membership_plans as $plan ) {

			// Skip if no products grant access to this plan
			if ( ! $plan->has_products() ) {
				continue;
			}

			// Array to store products that grant access to this plan
			$access_granting_product_ids = array();

			// Loop over items to see if any of them grant access to any memberships
			foreach ( $items as $key => $item ) {

				// Product grants access to this membership
				if ( $plan->has_product( $item['product_id'] ) ) {
					$access_granting_product_ids[] = $item['product_id'];
				}

				// Variation access
				if ( isset( $item['variation_id'] ) && $item['variation_id'] && $plan->has_product( $item['variation_id'] ) ) {
					$access_granting_product_ids[] = $item['variation_id'];
				}

			}

			// No products grant access, skip further processing
			if ( empty( $access_granting_product_ids ) ) {
				continue;
			}

			/**
			 * Filter the product ID that grants access to the membership plan via purchase
			 *
			 * Multiple products from a single order can grant access to a membership plan.
			 * Default behavior is to use the first product that grants access, but this can
			 * be overriden using this filter.
			 *
			 * @since 1.0.0
			 * @param int $product_id
			 * @param array $access_granting_product_ids Array of product IDs that can grant access to this plan
			 * @param WC_Memberships_Membership_Plan $plan Membership plan access will be granted to
			 */
			$product_id = apply_filters( 'wc_memberships_access_granting_purchased_product_id', $access_granting_product_ids[0], $access_granting_product_ids, $plan );

			// Sanity check: make sure the selected product ID in fact does grant access
			if ( ! $plan->has_product( $product_id ) ) {
				continue;
			}

			// Delegate granting access to the membership plan instance
			$plan->grant_access_from_purchase( $user_id, $product_id, $order_id );
		}

	}


	/** Admin methods ******************************************************/


	/**
	 * Render a notice for the user to read the docs before adding add-ons
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		$screen = get_current_screen();

		// only render on plugins or settings screen
		if ( 'plugins' === $screen->id || $this->is_plugin_settings() ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				sprintf( __( 'Thanks for installing Memberships! To get started, take a minute to %sread the documentation%s and then %ssetup a membership plan%s :)', self::TEXT_DOMAIN ),
					'<a href="http://docs.woothemes.com/document/woocommerce-memberships/" target="_blank">', '</a>', '<a href="' . admin_url( 'edit.php?post_type=wc_membership_plan' ) . '">', '</a>' ),
				'get-started-notice',
				array( 'always_show_on_settings' => false, 'notice_class' => 'updated' )
			);
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Memberships Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.0.0
	 * @see wc_memberships()
	 * @return WC_Memberships
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Gets the plugin documentation URL
	 *
	 * @since 1.2.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string
	 */
	public function get_documentation_url() {
		return 'http://docs.woothemes.com/document/woocommerce-memberships/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.2.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {
		return 'http://support.woothemes.com/';
	}


	/**
	 * Search an array of arrays by key-value
	 *
	 * If a match is found in the array more than once,
	 * only the first matching key is returned.
	 *
	 * @since 1.0.0
	 * @param array $array Array of arrays
	 * @param string $key The key to search for
	 * @param string $value The value to search for
	 * @return array|boolean Found results, or false if none found
	 */
	public function array_search_key_value( $array, $key, $value ) {

		if ( ! is_array( $array ) ) {
			return null;
		}

		if ( empty( $array ) ) {
			return false;
		}

		$found_key = false;

		foreach ( $array as $element_key => $element ) {

			if ( isset( $element[ $key ] ) && $value == $element[ $key ] ) {

				$found_key = $element_key;
				break;
			}
		}

		return $found_key;
	}


	/**
	 * Workaround the last day of month quirk in PHP's strtotime function.
	 *
	 * Adding +1 month to the last day of the month can yield unexpected results with strtotime()
	 * For example,
	 * - 30 Jan 2013 + 1 month = 3rd March 2013
	 * - 28 Feb 2013 + 1 month = 28th March 2013
	 *
	 * What humans usually want is for the charge to continue on the last day of the month.
	 *
	 * Copied from WooCommerce Subscriptions
	 *
	 * @since 1.0.0
	 * @param string $from_timestamp Original timestamp to add months to
	 * @param int $months_to_add Number of months to add to the timestamp
	 * @return int corrected timestamp
	 */
	public function add_months( $from_timestamp, $months_to_add ) {

		$first_day_of_month = date( 'Y-m', $from_timestamp ) . '-1';
		$days_in_next_month = date( 't', strtotime( "+ {$months_to_add} month", strtotime( $first_day_of_month ) ) );

		// It's the last day of the month OR number of days in next month is less than the the day of this month (i.e. current date is 30th January, next date can't be 30th February)
		if ( date( 'd m Y', $from_timestamp ) === date( 't m Y', $from_timestamp ) || date( 'd', $from_timestamp ) > $days_in_next_month ) {

			for ( $i = 1; $i <= $months_to_add; $i++ ) {

				$next_month = strtotime( '+ 3 days', $from_timestamp ); // Add 3 days to make sure we get to the next month, even when it's the 29th day of a month with 31 days
				$next_timestamp = $from_timestamp = strtotime( date( 'Y-m-t H:i:s', $next_month ) ); // NB the "t" to get last day of next month
			}
		}

		// It's safe to just add a month
		else {
			$next_timestamp = strtotime( "+ {$months_to_add} month", $from_timestamp );
		}

		return $next_timestamp;
	}


	/**
	 * Creates a human readable list of an array
	 *
	 * @since 1.0.0
	 * @param string[] $ranges array to list items of
	 * @param string $conjunction optional. The word to join together the penultimate and last item. Defaults to 'or'
	 * @return string 'item1, item2, item3 or item4'
	 */
	public function list_items( $items, $conjunction = null ) {

		if ( ! $conjunction ) {
			$conjunction = __( 'or', WC_Memberships::TEXT_DOMAIN );
		}

		array_splice( $items, -2, 2, implode( ' ' . $conjunction . ' ', array_slice( $items, -2, 2 ) ) );
		return implode( ', ', $items );
	}


	/**
	 * Return a list of edit post links for the provided posts
	 *
	 * @since 1.1.0
	 * @param array Array of post objects
	 * @return string
	 */
	public function admin_list_post_links( $posts ) {

		if ( empty( $posts ) ) {
			return '';
		}

		$items = array();

		foreach ( $posts as $post ) {
			$items[] = '<a href="' . get_edit_post_link( $post->ID ) . '">' . get_the_title( $post->ID ) . '</a>';
		}

		return $this->list_items( $items, __( 'and', WC_Memberships::TEXT_DOMAIN ) );
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'WooCommerce Memberships', $this->text_domain );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


	/**
	 * Returns true if on the memberships settings page
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {
		return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['tab'] ) && 'memberships' == $_GET['tab'];
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_settings_link()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {
		return admin_url( 'admin.php?page=wc-settings&tab=memberships' );
	}


	/**
	 * Checks is WooCommerce Subscriptions is active
	 *
	 * @since 1.0.0
	 * @return bool true if the WooCommerce Subscriptions plugin is active, false if not active
	 */
	public function is_subscriptions_active() {

		if ( is_bool( $this->subscriptions_active ) ) {
			return $this->subscriptions_active;
		}

		return $this->subscriptions_active = $this->is_plugin_active( 'woocommerce-subscriptions.php' );
	}


	/**
	 * Checks is User Switching is active
	 *
	 * @since 1.0.0
	 * @return bool true if the User Switching plugin is active, false if not active
	 */
	public function is_user_switching_active() {

		if ( is_bool( $this->user_switching_active ) ) {
			return $this->user_switching_active;
		}

		return $this->user_switching_active = $this->is_plugin_active( 'user-switching.php' );
	}


	/**
	 * Checks is Groups is active
	 *
	 * @since 1.0.0
	 * @return bool true if the Groups plugin is active, false if not active
	 */
	public function is_groups_active() {

		if ( is_bool( $this->groups_active ) ) {
			return $this->groups_active;
		}

		return $this->groups_active = $this->is_plugin_active( 'groups.php' );
	}


	/**
	 * Checks is Bookings is active
	 *
	 * @since 1.3.0
	 * @return bool true if the WooCommerce Bookings plugin is active, false if not active
	 */
	public function is_bookings_active() {

		if ( is_bool( $this->bookings_active ) ) {
			return $this->bookings_active;
		}

		return $this->bookings_active = $this->is_plugin_active( 'woocommmerce-bookings.php' );
	}


	/**
	 * Encode a variable into JSON via wp_json_encode() if available, fall back
 	 * to json_encode otherwise.
	 *
	 * json_encode() may fail and return `null` in some envrionments (esp. with
	 * character encoding issues)
	 *
	 * @since 1.2.0
	 * @param mixed $data    Variable (usually an array or object) to encode as JSON.
	 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
	 * @param int   $depth   Optional. Maximum depth to walk through $data. Must be greater than 0. Default 512.
	 * @return bool|string   The JSON encoded string, or false if it cannot be encoded.
	 */
	public function wp_json_encode( $data, $options = 0, $depth = 512 ) {

		return function_exists( 'wp_json_encode' ) ? wp_json_encode( $data, $options, $depth ) : json_encode( $data, $options, $depth );
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Install default settings & pages
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {

		// install default "content restricted" page
		$title   = _x( 'Content restricted', 'Page title', WC_Memberships::TEXT_DOMAIN );
		$slug    = _x( 'content-restricted', 'Page slug', WC_Memberships::TEXT_DOMAIN );
		$content = '[wcm_content_restricted]';

		wc_create_page( esc_sql( $slug ), 'wc_memberships_redirect_page_id', $title, $content );

		// include settings so we can install defaults
		include_once( WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php' );
		$settings = require_once( $this->get_plugin_path() . '/includes/admin/class-wc-memberships-settings.php' );

		// install default settings for each section
		foreach ( $settings->get_sections() as $section => $label ) {

			foreach ( $settings->get_settings( $section ) as $setting ) {

				if ( isset( $setting['default'] ) ) {

					update_option( $setting['id'], $setting['default'] );
				}
			}
		}

	}


	/**
	 * Upgrade
	 *
	 * @since 1.1.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function upgrade( $installed_version ) {

		// upgrade to version 1.1.0
		if ( version_compare( $installed_version, '1.1.0', '<' ) ) {

			$all_rules = array();

			// Merge rules from different options into a single option
			foreach ( array( 'content_restriction', 'product_restriction', 'purchasing_discount' ) as $rule_type ) {
				$rules = get_option( "wc_memberships_{$rule_type}_rules" );

				if ( is_array( $rules ) && ! empty( $rules ) ) {

					foreach ( $rules as $rule ) {

						// Skip empty/corrupt rules
						if ( empty( $rule ) || isset( $rule[0] ) && ! $rule[0] ) {
							continue;
						}

						$rule['rule_type'] = $rule_type;
						$all_rules[] = $rule;
					}
				}

				delete_option( "wc_memberships_{$rule_type}_rules" );
			}

			update_option( 'wc_memberships_rules', $all_rules );
		}
	}


	/**
	 * Handle plugin activation
	 *
	 * @since 1.0.0
	 */
	public function maybe_activate() {

		$is_active = get_option( 'wc_memberships_is_active', false );

		if ( ! $is_active ) {

			update_option( 'wc_memberships_is_active', true );

			/**
			 * Run when Memberships is activated
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_memberships_activated' );
		}

	}


	/**
	 * Handle plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		delete_option( 'wc_memberships_is_active' );

		/**
		 * Run when Memberships is deactivated
		 *
		 * @since 1.0.0
		 */
		do_action( 'wc_memberships_deactivated' );
	}


} // end WC_Memberships class


/**
 * Returns the One True Instance of Memberships
 *
 * @since 1.0.0
 * @return WC_Memberships
 */
function wc_memberships() {
	return WC_Memberships::instance();
}

// fire it up!
wc_memberships();

} // init_woocommerce_memberships()
