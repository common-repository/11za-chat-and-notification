(function ($) {
    var webhookSetting = {

        init: function () {

            function onChangeAPIKey(){
                if ($("#setting_api_key").val() == ""){
                    $("#wp11za_btn_trial").css("display" , "inline-block");
                    $("#wp11za_save_settings").css("display" , "none");
                }else{                
                    $("#wp11za_btn_trial").css("display" , "none");
                    $("#wp11za_save_settings").css("display" , "inline-block");
                }
                
                if ($("#setting_11za_domain").val() == ""){
                    $("#wp11za_goto_settings").css("display" , "none");
                }else{
                    $("#wp11za_goto_settings").css("display" , "inline-block");
                }
                
                if ($("#setting_api_key").val() == "" && $("#setting_11za_domain").val() == ""){
                    $("#wp11za_btn_trial").css("display" , "inline-block");
                    $("#wp11za_save_settings").css("display" , "none");
                }

                if ($("#setting_api_key").val() == "" && $("#setting_11za_domain").val() != ""){
                    $("#wp11za_btn_trial").css("display" , "none");
                    $("#wp11za_save_settings").css("display" , "inline-block");
                }
            }

            onChangeAPIKey();
            
            $("#engees_11za_setting_form").submit(function(){
                return false;
            })


            $("#setting_api_key").on("change", function(){
                onChangeAPIKey();
            })
            
            $("#setting_api_key").on("keydown", function(){                
                $("#api_key_invalid").css("display", "none");
                onChangeAPIKey();
            })

            $("body").on("click", "#wp11za_btn_trial", function(){
                if ($("#setting_shop_name").val() == "" || $("#setting_email").val() == "" || $("#setting_whatsapp_number").val() == ""){
                    return;
                }
                var data = {
                    action: "engees_11za_set_wordpress_domain_to_integration_service",
                    security: WPVars._nonce,
                    api_key: "",
                    shop_name: $("#setting_shop_name").val(),
                    email: $("#setting_email").val(),
                    whatsapp_number: $("#setting_whatsapp_number").val(),
                }                
				jQuery("#wp11za_loding").css("display", "flex");
                jQuery.post(
                    WPVars.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
                    function (response) {
                        if (response && response.result) {
                            location.href = "";
                        } else {
                            $("#api_key_invalid").css("display", "inline-block");
                        }
                        jQuery("#wp11za_loding").css("display", "none");
                    }
                );
            });

            $("body").on("click", "#wp11za_save_settings", function(){
                if ($("#setting_shop_name").val() == "" || $("#setting_email").val() == "" || $("#setting_whatsapp_number").val() == ""){
                    return;
                }
                var data = {
                    action: "engees_11za_set_wordpress_domain_to_integration_service",
                    security: WPVars._nonce,
                    api_key: $("#setting_api_key").val(),
                    shop_name: $("#setting_shop_name").val(),
                    email: $("#setting_email").val(),
                    whatsapp_number: $("#setting_whatsapp_number").val(),
                }
                jQuery("#wp11za_loding").css("display", "flex");
                jQuery.post(
                    WPVars.ajaxurl, data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
                    function (response) {
                        jQuery("#wp11za_loding").css("display", "none");
                        if (response.data && response.data.result) {
                            location.href = "";
                        } else {
                            $("#api_key_invalid").css("display", "inline-block");
                        }
                    }
                );
            });
        },
    }

    webhookSetting.init();

})(jQuery);