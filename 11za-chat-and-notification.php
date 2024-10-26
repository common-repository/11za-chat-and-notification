<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Plugin Name:       11za Chat and Notification
 * Plugin URI:        https://11za.com/
 * Description:       Team Inbox, Abandoned Cart, Order Update, Chatbot for 11za
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            11Za
 * Author URI:        https://11za.com/about-us/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       11za-chat-and-notification
 * WC requires at least: 3.0
 * WC tested up to: 4.3.2
 *
 * @package 11za-Chat-And-Notification
 */

/**
 * Set constants.
 */
define( 'ENGEES_11ZA_PLUGIN_FILE', __FILE__ );

/**
 * Loader
 */
require_once 'classes/class-11za-loader.php';
