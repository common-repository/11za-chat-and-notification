<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Settings.
 *
 * @package 11za-Chat-And-Notification
 */

/**
 * Class ENGEES_11ZA_Utils.
 */
class ENGEES_11ZA_Settings {


	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;


	/**
	 * ENGEES_11ZA_Settings constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'wcf_initialize_settings' ) );
		add_filter( 'plugin_action_links_' . ENGEES_11ZA_BASE, array( $this, 'add_action_links' ), 999 );
	}


	/**
	 * Adding action links for plugin list page.
	 *
	 * @param array $links links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=' . ENGEES_11ZA_PAGE_NAME ) . '">Settings</a>',
		);

		return array_merge( $mylinks, $links );
	}
	/**
	 * Add new settings for cart abandonment settings.
	 *
	 * @since 1.1.5
	 */
	public function wcf_initialize_settings() {

		// Start: Settings for cart abandonment.
		add_settings_section(
			ENGEES_11ZA_GENERAL_SETTINGS_SECTION,
			__( 'Cart Abandonment Settings', '11za-chat-and-notification' ),
			array( $this, 'wcf_cart_abandonment_options_callback' ),
			ENGEES_11ZA_PAGE_NAME
		);
	}

	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}




}
ENGEES_11ZA_Settings::get_instance();
