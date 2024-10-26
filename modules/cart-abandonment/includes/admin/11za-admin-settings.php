<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Cartflows view for cart abandonment tabs.
 *
 * @package 11ZA-Chat-And-Notification
 */

?>
<div class="wrap">
	<div class="loading-content overlay" id="wp11za_loding">
		<div class="overlay-content">
			<div class="loader"></div>
		</div>
	</div>
	<h1 id="wcf_cart_abandonment_tracking_table"><?php echo esc_html__( '11ZA Chat and Notification', '11za-chat-and-notification' ); ?></h1>
	<br/>
	<form id="engees_11za_setting_form">
		<div>API Key <span id="api_key_invalid" style="color: red; display:none;">(Invalid API Key)</span></div>
		<input type="text" class="wp11za-input" id="setting_api_key" value="<?php echo esc_attr( $api_key ); ?>"/>
		<div>Shop Name</div>
		<input type="text" class="wp11za-input" id="setting_shop_name" value="<?php echo esc_attr( $shop_name ); ?>" required/>
		<div>Email</div>
		<input type="email" class="wp11za-input" id="setting_email" value="<?php echo esc_attr( $email ); ?>" required/>
		<div>Whatsapp Number</div>
		<input type="tel" class="wp11za-input" id="setting_whatsapp_number" value="<?php echo esc_attr( $whatsapp_number ); ?>" required/>
		<div>11ZA Url</div>
		<input type="text" class="wp11za-input" disabled id="setting_11za_domain" value="<?php echo esc_attr( $wp11za_domain ); ?>" />
		<br/><br/>
		<div>
			<input type="submit" id="wp11za_btn_trial" class="button-primary" value="<?php echo esc_attr__( 'Continue with Trial', '11za-chat-and-notification' ); ?>" />
			<input type="submit" id="wp11za_save_settings" class="button-primary" value="<?php echo esc_attr__( 'Save Settings', '11za-chat-and-notification' ); ?>" />
			<input type="submit" id="wp11za_goto_settings" class="button-primary" value="<?php echo esc_attr__( 'Go to 11ZA Settings', '11za-chat-and-notification' ); ?>" onclick="window.open('<?php echo esc_url( $wp11za_setting_url ); ?>', '_blank')"/>
		</div>	
	</form>
</div>