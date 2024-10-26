<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * CartFlows Loader.
 *
 * @package 11za-Chat-And-Notification
 */

 if ( ! class_exists( 'ENGEES_11ZA_Loader' ) ) {
    /**
	 * Class ENGEES_11ZA_Loader.
	 */

     final class ENGEES_11ZA_Loader {

        /**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance = null;

		/**
		 * Member Variable
		 *
		 * @var utils
		 */
		public $utils = null;


		/**
		 *  Initiator
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {

				self::$instance = new self();

				/**
				 * CartFlows CA loaded.
				 *
				 * Fires when Cartflows CA was fully loaded and instantiated.
				 *
				 * @since 1.0.0
				 */
				do_action( '11za_cartflow_ca_loaded' );
			}

			return self::$instance;
		}

        /**
		 * Constructor
		 */
		public function __construct() {

			$this->define_constants();

			// Activation hook.
			register_activation_hook( ENGEES_11ZA_PLUGIN_FILE, array( $this, 'activation_reset' ) );

			// deActivation hook.
			register_deactivation_hook( ENGEES_11ZA_PLUGIN_FILE, array( $this, 'deactivation_reset' ) );

			add_action( 'plugins_loaded', array( $this, 'load_plugin' ), 99 );

		}

        /**
		 * Defines all constants
		 *
		 * @since 1.0.0
		 */
		public function define_constants() {
			define( 'ENGEES_11ZA_BASE', plugin_basename( ENGEES_11ZA_PLUGIN_FILE ) );
			define( 'ENGEES_11ZA_DIR', plugin_dir_path( ENGEES_11ZA_PLUGIN_FILE ) );
			define( 'ENGEES_11ZA_URL', plugins_url( '/', ENGEES_11ZA_PLUGIN_FILE ) );
			define( 'ENGEES_11ZA_VER', '1.0.0' );
			define( 'ENGEES_11ZA_SLUG', '11ZA_cartflows_ca' );
			define( 'ENGEES_11ZA_SETTING_TABLE', '11za_setting' );
			define( 'ENGEES_11ZA_ABANDONMENT_TABLE', '11za_abandonment' );
			define( 'ENGEES_11ZA_PAGE_NAME', '11za-chat-and-notification' );
			define( 'ENGEES_11ZA_GENERAL_SETTINGS_SECTION', 'cartflows_cart_abandonment_settings_section' );
			
		}

        /**
		 * Loads plugin files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load_plugin() {

			if ( ! function_exists( 'WC' ) ) {
				add_action( 'admin_notices', array( $this, 'fails_to_load' ) );
				return;
			}

			$this->load_core_components();

			$this->initialize_cart_abandonment_tables();
			
			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment.php';
			$Abandonment = ENGEES_11ZA_Chat_And_Notification_Aband_Cart::get_instance();
			$wp11zaDomain = $Abandonment -> get_11za_setting_by_meta("wp11za_domain");
			$Abandonment -> set_11za_setting_by_meta("plugin_activated", "true");

			/**
			 * CartFlows Init.
			 *
			 * Fires when Cartflows is instantiated.
			 *
			 * @since 1.0.0
			 */
			
			do_action( 'wp11za_cartflow_ca_init' );
		}

        /**
		 * Fires admin notice when Elementor is not installed and activated.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function fails_to_load() {

			$this->initialize_cart_abandonment_tables();
			$screen = get_current_screen();

			if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
				return;
			}

			$class = 'notice notice-error';
			/* translators: %s: html tags */
			$message = sprintf( __( 'The %1$s11ZA Chat and Notification%2$s plugin requires %1$sWooCommerce%2$s plugin installed & activated.', '11za-chat-and-notification' ), '<strong>', '</strong>' );
			$plugin  = 'woocommerce/woocommerce.php';

			if ( $this->is_woo_installed() ) {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}

				$action_url   = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
				$button_label = __( 'Activate WooCommerce', '11za-chat-and-notification' );

			} else {
				if ( ! current_user_can( 'install_plugins' ) ) {
					return;
				}

				$action_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
				$button_label = __( 'Install WooCommerce', '11za-chat-and-notification' );
			}

			$button = '<p><a href="' . $action_url . '" class="button-primary">' . $button_label . '</a></p><p></p>';

			printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), wp_kses_post( $message ), wp_kses_post( $button ) );
		}

        /**
		 * Is woocommerce plugin installed.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 */
		public function is_woo_installed() {

			$path    = 'woocommerce/woocommerce.php';
			$plugins = get_plugins();

			return isset( $plugins[ $path ] );
		}

		/**
		 * Create new database tables for plugin updates.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function initialize_cart_abandonment_tables() {

			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment-db.php';
			$db = ENGEES_11ZA_Chat_And_Notification_Aband_Cart_Db::get_instance();
			$db->create_tables();			
			$db->init_tables();
			
			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment.php';
			$Abandonment = ENGEES_11ZA_Chat_And_Notification_Aband_Cart::get_instance();
			$meta_data['integration_service_url'] = 'https://app.11za.in';
			$Abandonment -> set_11za_setting_by_meta("integration_service_url", $meta_data['integration_service_url']);
		}


		/**
		 * Load Core Components.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load_core_components() {

			/* Cart abandonment templates class */
			include_once ENGEES_11ZA_DIR . 'classes/class-11za-settings.php';
			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-module-loader.php';			
		}


		/**
		 * Activation Reset
		 */
		public function activation_reset() {
			register_uninstall_hook( ENGEES_11ZA_PLUGIN_FILE, array( $this, 'uninstall_plugin' ));
			if ( !class_exists( 'WooCommerce' ) ) {
				return;
			}
			$this->initialize_cart_abandonment_tables();
			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment.php';
			$Abandonment = ENGEES_11ZA_Chat_And_Notification_Aband_Cart::get_instance();
			$wp11zaDomain = $Abandonment -> get_11za_setting_by_meta("wp11za_domain");
			$Abandonment -> set_11za_setting_by_meta("plugin_activated", "true");
			$meta_data['integration_service_url'] = 'https://app.11za.in';
			$Abandonment -> set_11za_setting_by_meta("integration_service_url", $meta_data['integration_service_url']);
			if ($wp11zaDomain != null || $wp11zaDomain != "")
				$Abandonment-> save_webhook_url($wp11zaDomain);
		}

		/**
		 * Deactivation Reset
		 */
		public function deactivation_reset() {
			if ( !class_exists( 'WooCommerce' ) ) {
				return;
			}
			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment.php';
			$Abandonment = ENGEES_11ZA_Chat_And_Notification_Aband_Cart::get_instance();
			$wp11zaDomain = $Abandonment -> get_11za_setting_by_meta("wp11za_domain");
			$Abandonment -> set_11za_setting_by_meta("plugin_activated", "false");
			if ($wp11zaDomain != null || $wp11zaDomain != "")
				$Abandonment-> disable_webhook_url($wp11zaDomain);
		}

		/**
		 * Uninstall Plugin
		 */
		public function uninstall_plugin() {
			if ( !class_exists( 'WooCommerce' ) ) {
				return;
			}
			include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment.php';
			$Abandonment = ENGEES_11ZA_Chat_And_Notification_Aband_Cart::get_instance();
			$wp11zaDomain = $Abandonment -> get_11za_setting_by_meta("wp11za_domain");
			$Abandonment -> set_11za_setting_by_meta("plugin_activated", "false");
			if ($wp11zaDomain != null || $wp11zaDomain != "")
				$Abandonment-> disable_webhook_url($wp11zaDomain);
		}

     }

     /**
	 *  Prepare if class 'ENGEES_11ZA_Loader' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
     ENGEES_11ZA_Loader::get_instance();
    
 }

 if ( ! function_exists( 'wp11za_ca' ) ) {
	/**
	 * Get global class.
	 *
	 * @return object
	 */
	function wp11za_ca() {
		return ENGEES_11ZA_Loader::get_instance();
	}
}