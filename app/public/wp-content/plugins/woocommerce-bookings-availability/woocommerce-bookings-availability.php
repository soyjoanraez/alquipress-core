<?php
/**
 * Plugin Name: WooCommerce Bookings Availability
 * Requires Plugins: woocommerce
 * Plugin URI: https://woocommerce.com/products/bookings-availability/
 * Description: Show booking availabilities via Gutenberg blocks.
 * Version: 1.3.4
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce-bookings-availability
 * Domain Path: /languages
 * Tested up to: 6.8
 * Requires at least: 6.7
 * WC tested up to: 10.3
 * WC requires at least: 10.1
 * PHP tested up to: 8.4
 * Requires PHP: 7.4
 * Woo: 4228225:0ad21600fc33292ffbad45fb632c6ae0
 *
 * @package woocommerce-bookings-availability
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Minimum required version of Bookings.
 */
if ( ! defined( 'WC_BOOKINGS_AVAILABILITY_MIN_BOOKINGS_VERSION' ) ) {
	define( 'WC_BOOKINGS_AVAILABILITY_MIN_BOOKINGS_VERSION', '1.14.5' );
}

// phpcs:disable WordPress.Files.FileName

/**
 * Bookings fallback notice.
 *
 * @since 1.0.0
 */
function woocommerce_bookings_availability_missing_bookings_notice() {
	/* translators: %s WC Bookings URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Bookings Availability requires WooCommerce Bookings. You can purchase %s here.', 'woocommerce-bookings-availability' ), '<a href="https://woocommerce.com/products/woocommerce-bookings/" target="_blank">WooCommerce Bookings</a>' ) . '</strong></p></div>';
}

/**
 * Bookings version fallback notice.
 *
 * @since 1.0.0
 */
function woocommerce_bookings_availability_wrong_bookings_version_notice() {
	/* translators: %s WC Bookings version number. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Bookings Availability requires WooCommerce Bookings version %s or higher. Please update your current WooCommerce Bookings version.', 'woocommerce-bookings-availability' ), esc_html( WC_BOOKINGS_AVAILABILITY_MIN_BOOKINGS_VERSION ) ) . '</strong></p></div>';
}

register_activation_hook( __FILE__, 'woocommerce_bookings_availability_activate' );

/**
 * Fires on plugin activation.
 *
 * @since 1.0.0
 */
function woocommerce_bookings_availability_activate() {
	if ( ! class_exists( 'WC_Bookings' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_availability_missing_bookings_notice' );
		return;
	}

	if ( version_compare( WC_BOOKINGS_VERSION, WC_BOOKINGS_AVAILABILITY_MIN_BOOKINGS_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_availability_wrong_bookings_version_notice' );
		return;
	}

	if ( is_admin() ) {
		$notice_html = '<strong>' . esc_html__( 'You\'ve activated Bookings Availability for WooCommerce Bookings!', 'woocommerce-bookings-availability' ) . '</strong><br><br>';
		/* translators: first href link to wc-admin pages, second href link to wc-admin posts */
		$notice_sub   = __( 'You can now add a schedule or calendar into your <a href="%1$s">pages</a> and <a href="%2$s">posts</a>.<br><br>', 'woocommerce-bookings-availability' );
		$notice_sub   = sprintf( $notice_sub, admin_url( 'edit.php?post_type=page' ), admin_url( 'edit.php' ) );
		$notice_html .= $notice_sub;
		/* translators: button to create a new pages */
		$notice_html .= '<a href="' . esc_url( admin_url( 'post-new.php?post_type=page' ) ) . '" class="button-primary">' . __( 'Create a page', 'woocommerce-bookings-availability' ) . '</a> ';
		/* translators: button to create a new post */
		$notice_html .= '<a href="' . esc_url( admin_url( 'post-new.php' ) ) . '" class="button-primary">' . __( 'Create a post', 'woocommerce-bookings-availability' ) . '</a>';

		$notice_name = 'woocommerce_bookings_availability_activation';
		if ( ! get_user_meta( get_current_user_id(), 'dismissed_' . $notice_name . '_notice', true ) ) {
			WC_Admin_Notices::add_custom_notice( $notice_name, $notice_html );
		}
	}
}

if ( ! class_exists( 'WC_Bookings_Availability' ) ) :
	define( 'WC_BOOKINGS_AVAILABILITY_ABSPATH', dirname( __FILE__ ) . '/' );
	define( 'WC_BOOKINGS_AVAILABILITY_VERSION', '1.3.4' ); // WRCS: DEFINED_VERSION.
	define( 'WC_BOOKINGS_AVAILABILITY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
	define( 'WC_BOOKINGS_AVAILABILITY_MAIN_FILE', __FILE__ );

	/**
	 * WC Bookings Availability class.
	 */
	class WC_Bookings_Availability {
		/**
		 * The single instance of the class.
		 *
		 * @var $_instance
		 * @since 1.0.0
		 */
		protected static $_instance = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->includes();
			add_action( 'enqueue_block_assets', array( $this, 'load_block_params' ) );
			add_action( 'init', array( $this, 'register_blocks' ), 99 );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			// Declare compatibility with High-Performance Order Storage.
			add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		}


		/**
		 * Declare compatibility with High-Performance Order Storage.
		 *
		 * @since 1.1.23
		 */
		public function declare_hpos_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

		/**
		 * Main Bookings Availability Instance.
		 *
		 * Ensures only one instance of Bookings Availability is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @return WC_Bookings_Availability
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce-bookings-availability' ), WC_BOOKINGS_AVAILABILITY_VERSION );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce-bookings-availability' ), WC_BOOKINGS_AVAILABILITY_VERSION );
		}

		/**
		 * Cleanup on plugin deactivation.
		 *
		 * @since 1.0.0
		 */
		public function deactivate() {
			WC_Admin_Notices::remove_notice( 'woocommerce_bookings_availability_activation' );
		}

		/**
		 * Load dependencies.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			require_once WC_BOOKINGS_AVAILABILITY_ABSPATH . 'includes/wc-bookings-availability-common.php';
			if ( is_admin() ) {
				require_once WC_BOOKINGS_AVAILABILITY_ABSPATH . 'includes/admin/class-wc-bookings-availability-admin.php';
			} else {
				require_once WC_BOOKINGS_AVAILABILITY_ABSPATH . 'includes/class-wc-bookings-availability-frontend.php';
			}
		}

		/**
		 * Load localized blocks parameters.
		 *
		 * @since 1.0.0
		 */
		public function load_block_params() {
			$block_parameters = wc_bookings_availability_default_block_parameters();

			wc_bookings_availability_register_script(
				'wc-bookings-availability-common',
				'bookings-availability-common'
			);

			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_enqueue_script( 'wc-bookings-availability-common' );

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'wc-bookings-availability-common', 'woocommerce-bookings-availability', WC_BOOKINGS_AVAILABILITY_ABSPATH . 'languages' );
			}

			wp_localize_script(
				'wc-bookings-availability-common',
				'wc_bookings_availability_args',
				$block_parameters
			);
		}

		/**
		 * Registers the blocks.
		 *
		 * @since 1.0.0
		 */
		public function register_blocks() {
			if ( ! class_exists( 'WC_AJAX' ) ) {
				return;
			}

			register_block_type(
				'woocommerce/bookings-availability-calendar-block',
				array(
					'script_handles'        => array( 'woocommerce-bookings-availability-calendar-block-frontend' ),
					'editor_script_handles' => array( 'woocommerce-bookings-availability-calendar-block' ),
					'style_handles'         => array( 'woocommerce-bookings-availability-calendar-block-style-frontend' ),
					'editor_style_handles'  => array( 'woocommerce-bookings-availability-calendar-block-style' ),
				)
			);

			register_block_type(
				'woocommerce/bookings-availability-schedule-block',
				array(
					'script_handles'        => array( 'woocommerce-bookings-availability-schedule-block-frontend' ),
					'editor_script_handles' => array( 'woocommerce-bookings-availability-schedule-block' ),
					'style_handles'         => array( 'woocommerce-bookings-availability-schedule-block-style-frontend' ),
					'editor_style_handles'  => array( 'woocommerce-bookings-availability-schedule-block-style' ),
				)
			);
		}
	}
endif;

add_action( 'plugins_loaded', 'woocommerce_bookings_availability_init', 10 );

/**
 * Initializes the plugin after plugins are loaded.
 *
 * @since 1.0.0
 */
function woocommerce_bookings_availability_init() {
	load_plugin_textdomain( 'woocommerce-bookings-availability', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WC_Bookings' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_availability_missing_bookings_notice' );
		return;
	}

	if ( version_compare( WC_BOOKINGS_VERSION, WC_BOOKINGS_AVAILABILITY_MIN_BOOKINGS_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_bookings_availability_wrong_bookings_version_notice' );
		return;
	}

	WC_Bookings_Availability::instance();
}
