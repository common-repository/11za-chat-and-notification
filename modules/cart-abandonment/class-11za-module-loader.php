<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * 11ZA Chat and Notification
 *
 * @package 11ZA-Chat-And-Notification
 */

/**
 * 11ZA Chat and Notification class.
 */
class ENGEES_11ZA_Cartflows_Ca_Module_Loader {



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
	 *  Constructor
	 */
	public function __construct() {

		$this->load_module_files();
	}


	/**
	 *  Load required files for module.
	 */
	private function load_module_files() {
		/* Cart abandonment tracking */
		include_once ENGEES_11ZA_DIR . 'modules/cart-abandonment/class-11za-cart-abandonment.php';
	}

}

ENGEES_11ZA_Cartflows_Ca_Module_Loader::get_instance();
