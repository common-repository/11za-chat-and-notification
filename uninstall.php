<?php
/**
 * 11ZA Chat and Notification
 * Unscheduling the events.
 *
 * @package 11ZA-Chat-And-Notification
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


wp_clear_scheduled_hook( 'engees_11za_cartflow_ca_update_order_status_action' );
