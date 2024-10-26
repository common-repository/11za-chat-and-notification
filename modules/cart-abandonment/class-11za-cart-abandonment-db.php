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
class ENGEES_11ZA_Chat_And_Notification_Aband_Cart_Db {



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
	 *  Create tables
	 */
	public function create_tables() {
		$this->create_cart_abandonment_table();
		$this->create_11za_setting_table();
	}

	/**
	 *  Create Plugin Setting meta table.
	 */
	public function create_11za_setting_table() {
		global $wpdb;

		$wp11za_setting_tb       = $wpdb->prefix . ENGEES_11ZA_SETTING_TABLE;
		$charset_collate              = $wpdb->get_charset_collate();

		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wp11za_setting_tb );
		
		if ( !$wpdb->get_var( $query ) == $wp11za_setting_tb ) {
			$sql = "CREATE TABLE $wp11za_setting_tb (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`meta_key` varchar(255) NOT NULL,
				`meta_value` longtext NOT NULL,
				PRIMARY KEY (`id`)
				) $charset_collate;\n";
				include_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
		}
	}

	/**
	 *  Create tables for analytics.
	 */
	public function create_cart_abandonment_table() {

		global $wpdb;

		$cart_abandonment_db = $wpdb->prefix . ENGEES_11ZA_ABANDONMENT_TABLE;
		$charset_collate     = $wpdb->get_charset_collate();
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $cart_abandonment_db );
		if ( !$wpdb->get_var( $query ) == $cart_abandonment_db ) {
			// Cart abandonment tracking db sql command.
			$sql = "CREATE TABLE $cart_abandonment_db (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				checkout_id int(11) NOT NULL, 
				email VARCHAR(100),
				cart_contents LONGTEXT,
				cart_total DECIMAL(10,2),
				session_id VARCHAR(60) NOT NULL,
				other_fields LONGTEXT,
				order_status ENUM( 'normal','abandoned','completed','lost') NOT NULL DEFAULT 'normal',
				unsubscribed  boolean DEFAULT 0,
				coupon_code VARCHAR(50),
				time DATETIME DEFAULT NULL,
				PRIMARY KEY  (`id`, `session_id`),
				UNIQUE KEY `session_id_UNIQUE` (`session_id`)
			) $charset_collate;\n";

			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

	}

	/**
	 *  Insert initial data.
	 */
	public function init_tables() {
		global $wpdb;
		$wp11za_setting_tb       = $wpdb->prefix . ENGEES_11ZA_SETTING_TABLE;

		$meta_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wp11za_setting_tb") );
		if ( ( ! $meta_count ) ) {
			
			$meta_data['integration_service_url'] = 'https://app.11za.in';
			$meta_data["access_token"] = md5( uniqid( wp_rand(), true ) );
			foreach ( $meta_data as $meta_key => $meta_value ) {
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $wp11za_setting_tb ( `meta_key`, `meta_value` ) 
						VALUES ( %s, %s )",
						$meta_key,
						$meta_value
					)
				);
			}
		}
	}
}

ENGEES_11ZA_Chat_And_Notification_Aband_Cart_Db::get_instance();
