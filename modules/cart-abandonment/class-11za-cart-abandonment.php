<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Cart Abandonment
 *
 * @package 11ZA-Chat-And-Notification
 */

/**
 * Cart abandonment tracking class.
 */
class ENGEES_11ZA_Chat_And_Notification_Aband_Cart {


    /**
	 * Member Variable
	 *
	 * @var object instance
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *  Constructor function that initializes required actions and hooks.
	 */
	public function __construct() {

		$this->define_cart_abandonment_constants();

		add_action( 'admin_menu', array( $this, 'abandoned_cart_tracking_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'webhook_setting_script' ), 20 );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'cart_abandonment_tracking_script' ) );

		//trigger abandoned checkout event
		add_action( 'wp_ajax_11za_cartflows_save_cart_abandonment_data', array( $this, 'save_cart_abandonment_data' ) );
		add_action( 'wp_ajax_nopriv_11za_cartflows_save_cart_abandonment_data', array( $this, 'save_cart_abandonment_data' ) );

		add_action( 'wp_ajax_engees_11za_set_wordpress_domain_to_integration_service', array( $this, 'engees_11za_set_wordpress_domain_to_integration_service' ) );
		add_action( 'wp_ajax_nopriv_engees_11za_set_wordpress_domain_to_integration_service', array( $this, 'engees_11za_set_wordpress_domain_to_integration_service' ) );

		add_action( 'rest_api_init', function () {
			register_rest_route( 'api/v1', '/getWoocommerceInfo', array(
			  'methods' => 'GET',
			  'callback' => array( $this, 'getWoocommerceInfo' ),
			));
		});
		add_action( 'rest_api_init', function () {
			register_rest_route( 'api/v1', '/getAccessToken', array(
			  'methods' => 'GET',
			  'callback' => array( $this, 'getAccessToken' ),
			));
		});
		
		add_action( 'rest_api_init', function () {
			register_rest_route( 'api/v1', '/getOrderInfo', array(
			  'methods' => 'GET',
			  'callback' => array( $this, 'getOrderInfo' ),
			));
		});
		add_action( 'rest_api_init', function () {
			register_rest_route( 'api/v1', '/getCheckoutInfo', array(
			  'methods' => 'GET',
			  'callback' => array( $this, 'getCheckoutInfo' ),
			));
		});

		add_filter( 'jwt_auth_whitelist', function ( $endpoints ) {
			return array(
				'/wp-json/api/v1/getWoocommerceInfo',
				'/wp-json/api/v1/getOrderInfo',
				'/wp-json/api/v1/getAccessToken',
				'/wp-json/api/v1/getCheckoutInfo',
			);
		});

		add_filter( 'wp', array( $this, 'restore_cart_abandonment_data' ), 10 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'engees_11za_ca_update_order_status' ), 999, 3 );
	}

	/**
	 *  Initialise all the constants
	 */
    public function define_cart_abandonment_constants() {
		define( 'ENGEES_11ZA_CARTFLOWS_CART_ABANDONMENT_TRACKING_DIR', ENGEES_11ZA_DIR . 'modules/cart-abandonment/' );
		define( 'ENGEES_11ZA_CARTFLOWS_CART_ABANDONMENT_TRACKING_URL', ENGEES_11ZA_URL . 'modules/cart-abandonment/' );
		define( 'ENGEES_11ZA_CART_ABANDONED_ORDER', 'abandoned' );
		define( 'ENGEES_11ZA_CART_COMPLETED_ORDER', 'completed' );
		define( 'ENGEES_11ZA_CART_LOST_ORDER', 'lost' );
		define( 'ENGEES_11ZA_CART_NORMAL_ORDER', 'normal' );
		define( 'ENGEES_11ZA_CART_FAILED_ORDER', 'failed' );

		define( 'ENGEES_11ZA_ACTION_ABANDONED_CARTS', 'abandoned_carts' );
		define( 'ENGEES_11ZA_ACTION_RECOVERED_CARTS', 'recovered_carts' );
		define( 'ENGEES_11ZA_ACTION_LOST_CARTS', 'lost_carts' );
		define( 'ENGEES_11ZA_ACTION_SETTINGS', 'settings' );
		define( 'ENGEES_11ZA_ACTION_REPORTS', 'reports' );

		define( 'ENGEES_11ZA_SUB_ACTION_REPORTS_VIEW', 'view' );
		define( 'ENGEES_11ZA_SUB_ACTION_REPORTS_RESCHEDULE', 'reschedule' );

		define( 'ENGEES_11ZA_DEFAULT_CUT_OFF_TIME', 15 );
		define( 'ENGEES_11ZA_DEFAULT_COUPON_AMOUNT', 10 );

		define( 'ENGEES_11ZA_CA_DATETIME_FORMAT', 'Y-m-d H:i:s' );

		define( 'ENGEES_11ZA_CA_COUPON_DESCRIPTION', 'This coupon is for abandoned cart email templates.' );
		define( 'ENGEES_11ZA_CA_COUPON_GENERATED_BY', '11za-chat-and-notification' );
	}

    public function abandoned_cart_tracking_menu() {

		$capability = current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';

		add_submenu_page(
			'woocommerce',
			__( '11ZA Chat & Notification', '11za-chat-and-notification' ),
			__( '11ZA Chat & Notification', '11za-chat-and-notification' ),
			$capability,
			ENGEES_11ZA_PAGE_NAME,
			array( $this, 'render_abandoned_cart_tracking' )
		);
	}

    public function render_abandoned_cart_tracking() {

		$api_key = $this->get_11za_setting_by_meta("api_key");
		$wp11za_domain = $this->get_11za_setting_by_meta("wp11za_domain");
		$wp11za_domain_front = $this->get_11za_setting_by_meta("wp11za_domain_front");

		$shop_name = $this->get_11za_setting_by_meta("shop_name");
		$email = $this->get_11za_setting_by_meta("email");
		$whatsapp_number = $this->get_11za_setting_by_meta("whatsapp_number");
		$code = $this->get_11za_setting_by_meta("code");
		$wp11za_setting_url = $wp11za_domain_front . "/registerWoocommerce?code=" . $code . "&wordpressDomain=" . get_home_url();

		if ($shop_name == "")
			// $shop_name = $_SERVER['HTTP_HOST'];
			$shop_name = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_STRING);
		if ($email == ""){			
			global $current_user;			
			$current_user = wp_get_current_user();
			$email = (string) $current_user->user_email;
		}
			
		?>

		<?php
		include_once ENGEES_11ZA_CARTFLOWS_CART_ABANDONMENT_TRACKING_DIR . 'includes/admin/11za-admin-settings.php';
		?>
		<?php
	}

    public function get_11za_setting_by_meta($meta_key) {
		global $wpdb;
		$wp11za_setting_table = $wpdb->prefix . ENGEES_11ZA_SETTING_TABLE;
		
		$res = $wpdb->get_row(
			$wpdb->prepare( "select * from $wp11za_setting_table where meta_key = %s", $meta_key ) // phpcs:ignore
		);

		if ( $res != null )
		{
			return $res->meta_value;
		}

		return null;
	}

	public function set_11za_setting_by_meta($input_meta_key, $input_meta_value) {
		global $wpdb;
		$wp11za_setting_tb = $wpdb->prefix . ENGEES_11ZA_SETTING_TABLE;
	
		if ($input_meta_key == "integration_service_url" && ($input_meta_value == "" || $input_meta_value == null)) {
			$input_meta_value = "https://app.11za.in";
		}
	
		// Prepare the SQL statement without interpolating the table name directly
		$sql = $wpdb->prepare(
			"INSERT INTO {$wp11za_setting_tb} (meta_key, meta_value) 
			VALUES (%s, %s) 
			ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
			$input_meta_key,
			$input_meta_value
		);
	
		// Execute the query
		$wpdb->query($sql);
	
		return true;
	}
	

    public function cart_abandonment_tracking_script() {
		$current_user        = wp_get_current_user();
		$roles               = $current_user->roles;
		$role                = array_shift( $roles );
		
		global $post;
		wp_enqueue_script(
			'11za-abandonment-tracking',
			ENGEES_11ZA_CARTFLOWS_CART_ABANDONMENT_TRACKING_URL . 'assets/js/11za-abandonment-tracking.js',
			array( 'jquery' ),
			"1.0",
			true
		);

		$vars = array(
			'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
			'_nonce'                    => wp_create_nonce( 'cartflows_save_cart_abandonment_data' ),
			'_post_id'                  => get_the_ID(),
			'_show_gdpr_message'        => false,
			'_gdpr_message'             => get_option( 'wcf_ca_gdpr_message' ),
			'_gdpr_nothanks_msg'        => __( 'No Thanks', '11za-chat-and-notification' ),
			'_gdpr_after_no_thanks_msg' => __( 'You won\'t receive further emails from us, thank you!', '11za-chat-and-notification' ),
			'enable_ca_tracking'        => true,
		);

		wp_localize_script( '11za-abandonment-tracking', 'CartFlowsProCAVars', $vars );
	}

    public function webhook_setting_script() {
		$current_user        = wp_get_current_user();
		$roles               = $current_user->roles;
		$role                = array_shift( $roles );
		
		global $post;
		wp_enqueue_script(
			'webhook_setting_script',
			ENGEES_11ZA_CARTFLOWS_CART_ABANDONMENT_TRACKING_URL . 'assets/js/webhook-setting.js',
			array( 'jquery' ),
			"1.0",
			true
		);

		wp_enqueue_style( 'webhook_setting_script', ENGEES_11ZA_CARTFLOWS_CART_ABANDONMENT_TRACKING_URL .'assets/css/style.css', array(), '1.0', 'all' );


		$vars = array(
			'ajaxurl'                   => admin_url( 'admin-ajax.php' )
		);

		wp_localize_script( 'webhook_setting_script', 'WPVars', $vars );
	}

    public function save_cart_abandonment_data() {
		$post_data = $this->sanitize_post_data();
		if ( isset( $post_data['wcf_email'] ) ) {
			$user_email = sanitize_email( $post_data['wcf_email'] );
			global $wpdb;
			$cart_abandonment_table = $wpdb->prefix . ENGEES_11ZA_ABANDONMENT_TABLE;

			// Verify if email is already exists.
			$session_id               = WC()->session->get( 'wcf_session_id' );
			$session_checkout_details = null;
			if ( isset( $session_id ) ) {
				$session_checkout_details = $this->get_checkout_details( $session_id );
			} else {
				$session_checkout_details = $this->get_checkout_details_by_email( $user_email );
				if ( $session_checkout_details ) {
					$session_id = $session_checkout_details->session_id;
					WC()->session->set( 'wcf_session_id', $session_id );
				} else {
					$session_id = md5( uniqid( wp_rand(), true ) );
				}
			}

			$checkout_details = $this->prepare_abandonment_data( $post_data );

			if ( isset( $session_checkout_details ) && $session_checkout_details->order_status === "completed" ) {
				WC()->session->__unset( 'wcf_session_id' );
				$session_id = md5( uniqid( wp_rand(), true ) );
			}

			if ( isset( $checkout_details['cart_total'] ) && $checkout_details['cart_total'] > 0 ) {

				if ( ( ! is_null( $session_id ) ) && ! is_null( $session_checkout_details ) ) {

					// Updating row in the Database where users Session id = same as prevously saved in Session.
					$wpdb->update(
						$cart_abandonment_table,
						$checkout_details,
						array( 'session_id' => $session_id )
					);
					$this->webhook_abandonedCheckout_to_11za($session_id, '');
				} else {

					$checkout_details['session_id'] = sanitize_text_field( $session_id );
					// Inserting row into Database.
					$wpdb->insert(
						$cart_abandonment_table,
						$checkout_details
					);

					// Storing session_id in WooCommerce session.
					WC()->session->set( 'wcf_session_id', $session_id );
					$this->webhook_abandonedCheckout_to_11za($session_id, '');
				}
			}

			wp_send_json_success();
		}
	}

    public function engees_11za_ca_update_order_status( $order_id, $old_order_status, $new_order_status ) {
		if ( ( ENGEES_11ZA_CART_FAILED_ORDER === $new_order_status ) ) {
			return;
		}

		$session_id = null;

		if ( WC()->session ) {
			$session_id = WC()->session->get( 'wcf_session_id' );
		}

		if ( $order_id  && $session_id ) {

			$session_id = WC()->session->get( 'wcf_session_id' );
			$captured_data = $this->get_checkout_details( $session_id );
			if ( $captured_data ) {
				$captured_data->order_status = ENGEES_11ZA_CART_COMPLETED_ORDER;
				$this->webhook_abandonedCheckout_to_11za($session_id, ENGEES_11ZA_CART_COMPLETED_ORDER);
				
				global $wpdb;
				$cart_abandonment_table = $wpdb->prefix . ENGEES_11ZA_ABANDONMENT_TABLE;
				$wpdb->delete( $cart_abandonment_table, array( 'session_id' => sanitize_key( $session_id ) ) );
				if ( WC()->session ) {
					WC()->session->__unset( 'wcf_session_id' );
				}
			}
		}
	}

    public function restore_cart_abandonment_data( $fields = array() ) {
		global $woocommerce;
		$result = array();
		// Restore only of user is not logged in.
		$wcf_session_id = filter_input( INPUT_GET, 'session_id', FILTER_SANITIZE_STRING );
		$result = $this->get_checkout_details( $wcf_session_id );
		if ( isset( $result ) && (ENGEES_11ZA_CART_ABANDONED_ORDER === $result->order_status || ENGEES_11ZA_CART_LOST_ORDER === $result->order_status) ) {
			WC()->session->set( 'wcf_session_id', $wcf_session_id );
		}
		if ( $result ) {
			$cart_content = unserialize( $result->cart_contents );

			if ( $cart_content ) {
				$woocommerce->cart->empty_cart();
				wc_clear_notices();
				foreach ( $cart_content as $cart_item ) {

					$cart_item_data = array();
					$variation_data = array();
					$id             = $cart_item['product_id'];
					$qty            = $cart_item['quantity'];

					// Skip bundled products when added main product.
					if ( isset( $cart_item['bundled_by'] ) ) {
						continue;
					}

					if ( isset( $cart_item['variation'] ) ) {
						foreach ( $cart_item['variation']  as $key => $value ) {
							$variation_data[ $key ] = $value;
						}
					}

					$cart_item_data = $cart_item;

					$woocommerce->cart->add_to_cart( $id, $qty, $cart_item['variation_id'], $variation_data, $cart_item_data );
				}

				if ( isset( $token_data['wcf_coupon_code'] ) && ! $woocommerce->cart->applied_coupons ) {
					$woocommerce->cart->add_discount( $token_data['wcf_coupon_code'] );
				}
			}
			$other_fields = unserialize( $result->other_fields );

			$parts = explode( ',', $other_fields['wcf_location'] );
			if ( count( $parts ) > 1 ) {
				$country = $parts[0];
				$city    = trim( $parts[1] );
			} else {
				$country = $parts[0];
				$city    = '';
			}

			foreach ( $other_fields as $key => $value ) {
				$key           = str_replace( 'wcf_', '', $key );
				$_POST[ $key ] = sanitize_text_field( $value );
			}
			$_POST['billing_first_name'] = sanitize_text_field( $other_fields['wcf_first_name'] );
			$_POST['billing_last_name']  = sanitize_text_field( $other_fields['wcf_last_name'] );
			$_POST['billing_phone']      = sanitize_text_field( $other_fields['wcf_phone_number'] );
			$_POST['billing_email']      = sanitize_email( $result->email );
			$_POST['billing_city']       = sanitize_text_field( $city );
			$_POST['billing_country']    = sanitize_text_field( $country );

		}
		return $fields;
	}

    public function prepare_abandonment_data( $post_data = array() ) {

		if ( function_exists( 'WC' ) ) {

			// Retrieving cart total value and currency.
			$cart_total = WC()->cart->total;

			// Retrieving cart products and their quantities.
			$products     = WC()->cart->get_cart();
			$current_time = current_time( ENGEES_11ZA_CA_DATETIME_FORMAT );
			$other_fields = array(
				'wcf_billing_company'     => $post_data['wcf_billing_company'],
				'wcf_billing_address_1'   => $post_data['wcf_billing_address_1'],
				'wcf_billing_address_2'   => $post_data['wcf_billing_address_2'],
				'wcf_billing_state'       => $post_data['wcf_billing_state'],
				'wcf_billing_postcode'    => $post_data['wcf_billing_postcode'],
				'wcf_shipping_first_name' => $post_data['wcf_shipping_first_name'],
				'wcf_shipping_last_name'  => $post_data['wcf_shipping_last_name'],
				'wcf_shipping_company'    => $post_data['wcf_shipping_company'],
				'wcf_shipping_country'    => $post_data['wcf_shipping_country'],
				'wcf_shipping_address_1'  => $post_data['wcf_shipping_address_1'],
				'wcf_shipping_address_2'  => $post_data['wcf_shipping_address_2'],
				'wcf_shipping_city'       => $post_data['wcf_shipping_city'],
				'wcf_shipping_state'      => $post_data['wcf_shipping_state'],
				'wcf_shipping_postcode'   => $post_data['wcf_shipping_postcode'],
				'wcf_order_comments'      => $post_data['wcf_order_comments'],
				'wcf_first_name'          => $post_data['wcf_name'],
				'wcf_last_name'           => $post_data['wcf_surname'],
				'wcf_phone_number'        => $post_data['wcf_phone'],
				'wcf_location'            => $post_data['wcf_country'] . ', ' . $post_data['wcf_city'],
			);

			$checkout_details = array(
				'email'         => $post_data['wcf_email'],
				'cart_contents' => serialize( $products ),
				'cart_total'    => sanitize_text_field( $cart_total ),
				'time'          => sanitize_text_field( $current_time ),
				'other_fields'  => serialize( $other_fields ),
				'checkout_id'   => $post_data['wcf_post_id'],
			);
		}
		return $checkout_details;
	}

	public function sanitize_post_data() {

		$input_post_values = array(
			'wcf_billing_company'     => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_email'               => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_EMAIL,
			),
			'wcf_billing_address_1'   => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_billing_address_2'   => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_billing_state'       => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_billing_postcode'    => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_first_name' => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_last_name'  => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_company'    => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_country'    => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_address_1'  => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_address_2'  => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_city'       => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_state'      => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_shipping_postcode'   => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_order_comments'      => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_name'                => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_surname'             => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_phone'               => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_country'             => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_city'                => array(
				'default'  => '',
				'sanitize' => FILTER_SANITIZE_STRING,
			),
			'wcf_post_id'             => array(
				'default'  => 0,
				'sanitize' => FILTER_SANITIZE_NUMBER_INT,
			),
		);
		$sanitized_post = array();
		foreach ( $input_post_values as $key => $input_post_value ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				// Validate email
				if ( 'wcf_email' === $key && !is_email( $_POST[ $key ] ) ) {
					$sanitized_post[ $key ] = $input_post_value['default'];
				} else {
					$sanitize_filter = $input_post_value['sanitize'];
					$sanitized_post[ $key ] = filter_var(wp_unslash($_POST[ $key ]), $sanitize_filter);
				}
			} else {
				$sanitized_post[ $key ] = $input_post_value['default'];
			}
		}
		return $sanitized_post;		
	}
	
	public function get_checkout_details( $wcf_session_id ) {
		global $wpdb;
		$cart_abandonment_table = $wpdb->prefix . ENGEES_11ZA_ABANDONMENT_TABLE;
		$result                 = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM `' . $cart_abandonment_table . '` WHERE session_id = %s AND order_status <> %s', $wcf_session_id, ENGEES_11ZA_CART_COMPLETED_ORDER) // phpcs:ignore
		);
		return $result;
	}

	public function get_checkout_details_by_email( $email ) {
		global $wpdb;
		$cart_abandonment_table = $wpdb->prefix . ENGEES_11ZA_ABANDONMENT_TABLE;
		$result                 = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM `' . $cart_abandonment_table . '` WHERE email = %s AND `order_status` IN ( %s, %s )', $email, ENGEES_11ZA_CART_ABANDONED_ORDER, ENGEES_11ZA_CART_NORMAL_ORDER ) // phpcs:ignore
		);
		return $result;
	}

	public function engees_11za_set_wordpress_domain_to_integration_service() {	
		$api_key = sanitize_text_field( $_POST['api_key'] );	
		$shop_name = sanitize_text_field( $_POST['shop_name'] );
		$whatsapp_number = sanitize_text_field( $_POST['whatsapp_number'] );
		$email = sanitize_email( $_POST['email'] );

		$url = $this->get_11za_setting_by_meta('integration_service_url') . "/apis/woocommerce/installPluginFromWordpress";

		$code = $this->rand_string(16);

		$this->set_11za_setting_by_meta("code", $code);
		$this->set_11za_setting_by_meta("shop_name", $shop_name);
		$this->set_11za_setting_by_meta("email", $email);
		$this->set_11za_setting_by_meta("whatsapp_number", $whatsapp_number);
		$this->set_11za_setting_by_meta("access_token", md5( uniqid( wp_rand(), true ) ));

		$data = array(
			'Id' => $api_key,
			'WordpressDomain' => get_home_url(),
			'siteTitle' => get_bloginfo( 'name' ),
			"Code" => $code
		);

		$options = [
			'body'        => wp_json_encode($data),
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'timeout'     => 60,
			'redirection' => 5,
			'blocking'    => true,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'data_format' => 'body',
		];
		
		$response = wp_remote_post( $url, $options );
		$response = json_decode( $response['body'] )->Data;
		if ($response && $response->result)
		{
			$this->set_11za_setting_by_meta("wp11za_domain", $response->wp11zaDomain);
			$this->set_11za_setting_by_meta("wp11za_domain_front", $response->wp11zaDomainFront);
			$this->set_11za_setting_by_meta("api_key", $api_key);
			$this->save_webhook_url($response->wp11zaDomain);
			wp_send_json_success($response);
		}
		else{
			wp_send_json_success();
		}
	}

	public function webhook_abandonedCheckout_to_11za($session_id, $order_status) {
		$checkoutDetails = $this->get_checkout_details($session_id);

		$url = $this->get_11za_setting_by_meta('wp11za_domain') . "/apis/woocommerce/webhookCheckout";		
			
		$other_fields = unserialize( $checkoutDetails->other_fields );
		
		$cart_contents = unserialize( $checkoutDetails->cart_contents );
		
		$cart_items = maybe_unserialize( $cart_contents );
        if ( ! is_array( $cart_items ) || ! count( $cart_items ) ) {
            return;
        }
        $all_items = array(); // Initialize an array to store all item details
        foreach ( $cart_items as $cart_item ) {
            if ( isset( $cart_item['product_id'] ) && isset( $cart_item['quantity'] ) && isset( $cart_item['line_total'] ) && isset( $cart_item['line_subtotal'] ) ) {
                $id        = 0 !== $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				$discount  = $discount + ( $cart_item['line_subtotal'] - $cart_item['line_total'] );
				$total     = $total + $cart_item['line_subtotal'];
				$tax       = $tax + $cart_item['line_tax'];
				$image_url = get_the_post_thumbnail_url( $id );
				$image_url = ! empty( $image_url ) ? $image_url : get_the_post_thumbnail_url( $cart_item['product_id'] );

				$product      = wc_get_product( $id );
				$product_name = $product ? $product->get_formatted_name() : '';

				if ( empty( $image_url ) ) {
				// 	$image_url = ONEONEZA_CA_URL . 'admin/assets/images/image-placeholder.png';
				}
				
				// Retrieve product title, price, and other details
                $product_title = $product ? $product->get_name() : '';
                $product_price = $product ? $product->get_price() : '';
                
                // Create an array with item details
                $item_details = array(
                    'title' => $product_title,
                    'product_price' => wc_price( $product_price ),
                    'image_url' => $image_url
                    // Add more details as needed
                );
                // Push item details to the all_items array
                $all_items[] = $item_details;
            }
            
        }

		$parts = explode( ',', $other_fields['wcf_location'] );
		if ( count( $parts ) > 1 ) {
			$country = $parts[0];
		} else {
			$country = $parts[0];
		}

		$data = array(
			'sessionId' => $checkoutDetails->session_id,
			'email' => $checkoutDetails->email,
			'phone' => sanitize_text_field( $other_fields['wcf_phone_number'] ),
			'country' => sanitize_text_field( $country ),
			'name' => sanitize_text_field( $other_fields['wcf_first_name'] ).' '.sanitize_text_field( $other_fields['wcf_last_name'] ),
			'first_name' => sanitize_text_field( $other_fields['wcf_first_name'] ),
			'last_name' => sanitize_text_field( $other_fields['wcf_last_name'] ),
			'total' => $checkoutDetails->cart_total,
			'status' => ($order_status == '' ? $checkoutDetails->order_status : $order_status),
			'checkoutUrl' => get_permalink( $checkoutDetails->checkout_id ) . '?session_id=' . $checkoutDetails->session_id,
			'line_items' => $all_items,
			'currency' => get_woocommerce_currency(),
			'pluginCode' => $this->get_11za_setting_by_meta('code'),
			'wordpressDomain' => get_home_url()
		);		
		
		$options = [
			'body'        => wp_json_encode($data),
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'timeout'     => 60,
			'redirection' => 5,
			'blocking'    => true,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'data_format' => 'body',
		];
		
		$response = wp_remote_post( $url, $options );

		return true;
	}

	public function getWoocommerceInfo() {
		$accessToken = sanitize_text_field( $_GET['accessToken'] );
		if ($accessToken == $this->get_11za_setting_by_meta('access_token')){
			return array(
				"currency" => get_woocommerce_currency(),
				"shopName" => $this->get_11za_setting_by_meta('shop_name'),
				"email" => $this->get_11za_setting_by_meta('email'),
				"whatsappNumber" => $this->get_11za_setting_by_meta('whatsapp_number'),
				"pluginActivated" => $this->get_11za_setting_by_meta('plugin_activated')
			);
		} else {
			return null;
		}
	}
	
	public function getAccessToken() {
		
		$code = sanitize_text_field( $_GET['code'] );
		if ($code == $this->get_11za_setting_by_meta('code')){
			$access_token = $this->get_11za_setting_by_meta("access_token");
			return array(
				"Access_Token" => $this->get_11za_setting_by_meta("access_token"),
				"WoocommerceInfo" => array(
					"Currency" => get_woocommerce_currency(),
					"ShopName" => $this->get_11za_setting_by_meta("shop_name"),
					"WhatsappNumber" => $this->get_11za_setting_by_meta("whatsapp_number"),
					"Email" => $this->get_11za_setting_by_meta("email")
				)
			);
		} else {
			return null;
		}
	}

	public function getOrderInfo(){
		$accessToken = sanitize_text_field( $_GET['accessToken'] );
		$order_id = sanitize_text_field( $_GET['order_id'] );
		if ($accessToken == $this->get_11za_setting_by_meta('access_token')){
			$order = wc_get_order($order_id);

			if (!$order){
				return null;
			}
			$order_data = $order->get_data(); // Get all order data as an array
			return array(
				"order_url" => $order->get_checkout_order_received_url(),
				"order_info" => $order_data
			);
		} else {
			return null;
		}
	}

	public function getCheckoutInfo(){
		$accessToken = sanitize_text_field( $_GET['accessToken'] );
		$session_id = sanitize_text_field( $_GET['sessionId'] );
		if ($accessToken == $this->get_11za_setting_by_meta('access_token')){
			$session_checkout_details = $this->get_checkout_details( $session_id );
			
			if (!$session_checkout_details){
				return null;
			}

			return array(
				"checkout_details" => $session_checkout_details
			);
		} else {
			return null;
		}
	}
	
	public function save_webhook_url($wp11zaDomain) {		
		$userID = get_current_user_id();

		$webhook = new WC_Webhook($this->get_11za_setting_by_meta("webhook_order_deleted_id"));
		$webhook->set_user_id($userID); 
		$webhook->set_topic( "order.deleted" ); 
		$webhook->set_secret( "secret" ); 
		$webhook->set_delivery_url( $wp11zaDomain . "/apis/woocommerce/order_deleted" ); 
		$webhook->set_status( "active" ); 
		$webhook->set_name( "ENGEES_11ZA_ORDER_DELETED" );
		$this->set_11za_setting_by_meta("webhook_order_deleted_id", $webhook->save());

		$webhook = new WC_Webhook($this->get_11za_setting_by_meta("webhook_order_updated_id"));
		$webhook->set_user_id($userID); 
		$webhook->set_topic( "order.updated" ); 
		$webhook->set_secret( "secret" ); 
		$webhook->set_delivery_url( $wp11zaDomain . "/apis/woocommerce/order_updated" ); 
		$webhook->set_status( "active" ); 
		$webhook->set_name( "ENGEES_11ZA_ORDER_UPDATED" );
		$this->set_11za_setting_by_meta("webhook_order_updated_id", $webhook->save());
		
		$webhook = new WC_Webhook($this->get_11za_setting_by_meta("webhook_order_created_id"));
		$webhook->set_user_id($userID); 
		$webhook->set_topic( "order.created" ); 
		$webhook->set_secret( "secret" ); 
		$webhook->set_delivery_url( $wp11zaDomain . "/apis/woocommerce/order_created" ); 
		$webhook->set_status( "active" ); 
		$webhook->set_name( "ENGEES_11ZA_ORDER_CREATED" );
		$this->set_11za_setting_by_meta("webhook_order_created_id", $webhook->save());
	}
	
	public function disable_webhook_url($wp11zaDomain) {
		$userID = get_current_user_id();

		$webhook = new WC_Webhook($this->get_11za_setting_by_meta("webhook_order_deleted_id"));
		$webhook->set_user_id($userID); 
		$webhook->set_topic( "order.deleted" ); 
		$webhook->set_secret( "secret" ); 
		$webhook->set_delivery_url( $wp11zaDomain . "/apis/woocommerce/order_deleted" ); 
		$webhook->set_status( "disabled" ); 
		$webhook->set_name( "ENGEES_11ZA_ORDER_DELETED" );
		$this->set_11za_setting_by_meta("webhook_order_deleted_id", $webhook->save());

		$webhook = new WC_Webhook($this->get_11za_setting_by_meta("webhook_order_updated_id"));
		$webhook->set_user_id($userID); 
		$webhook->set_topic( "order.updated" ); 
		$webhook->set_secret( "secret" ); 
		$webhook->set_delivery_url( $wp11zaDomain . "/apis/woocommerce/order_updated" ); 
		$webhook->set_status( "disabled" ); 
		$webhook->set_name( "ENGEES_11ZA_ORDER_UPDATED" );
		$this->set_11za_setting_by_meta("webhook_order_updated_id", $webhook->save());
		
		$webhook = new WC_Webhook($this->get_11za_setting_by_meta("webhook_order_created_id"));
		$webhook->set_user_id($userID); 
		$webhook->set_topic( "order.created" ); 
		$webhook->set_secret( "secret" ); 
		$webhook->set_delivery_url( $wp11zaDomain . "/apis/woocommerce/order_created" ); 
		$webhook->set_status( "disabled" ); 
		$webhook->set_name( "ENGEES_11ZA_ORDER_CREATED" );
		$this->set_11za_setting_by_meta("webhook_order_created_id", $webhook->save());
	}
	
	public function rand_string( $length ) {  
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";  
		$size = strlen( $chars );  
		$str = "";
		for( $i = 0; $i < $length; $i++ ) {  
			$str .= $chars[ wp_rand( 0, $size - 1 ) ];
		}
		return $str;
	}

}

ENGEES_11ZA_Chat_And_Notification_Aband_Cart::get_instance();