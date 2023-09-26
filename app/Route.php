<?php
namespace WDR_OD\App;
use Wdr\App\Controllers\Configuration;
use WDR_OD\App\Controllers\Admin\Admin;
use WDR_OD\App\Helpers\Helper;
defined('ABSPATH') or exit;

class Route
{

    private static $admin,$helper;

    /**
     * Init the hooks
     * @return void
     */
    public function hooks() {
        self::$admin = empty(self::$admin) ? new Admin() : self::$admin;
        self::$helper = empty(self::$helper) ? new Helper() : self::$helper;

        $settings_data = get_option('wdr_omnibus_directive');
        $is_omnibus_plugin_active = self::$helper->isOmnibusPluginActive();
        $is_override_omnibus_message = isset($settings_data['is_override_omnibus_message']) ? $settings_data['is_override_omnibus_message'] : 0;
        $is_omnibus_plugin_active = isset($is_omnibus_plugin_active) ? $is_omnibus_plugin_active : 0;
        $position_to_show_message_for_omnibus = get_option('_iwo_price_lowest_where');
        $config = new Configuration();
        $price_display_condition = $config->getConfig("show_strikeout_when", 'show_when_matched');
        $position_to_show_message = isset($settings_data['position_to_show_message']) && is_string($settings_data['position_to_show_message']) ? $settings_data['position_to_show_message'] : "woocommerce_get_price_html";
        $position_to_show_message = apply_filters('wdr_omnibus_directive_show_message_position', $position_to_show_message);

        if($is_override_omnibus_message == 1 && $is_omnibus_plugin_active == 1){
            if($position_to_show_message_for_omnibus == 'woocommerce_get_price_html') {
                add_filter('iworks_omnibus_integration_woocommerce_price_lowest', array(self::$admin, 'changeOmnibusPriceLowest'), 10, 2);
                add_filter('iworks_omnibus_message_template', array(self::$admin, 'changeOmnibusMessageTemplate'), 10, 1);
            } else {
                add_filter('iworks_omnibus_message_template', array(self::$admin, 'mergeOmnibusMessageWithDiscountRule'), 10, 3);
            }
        } else {
            if($position_to_show_message == 'woocommerce_get_price_html') {
                add_filter('woocommerce_get_price_html', array(self::$admin, 'separateGetPriceHtmlOmnibusMessage'), 100, 2);
                if(isset($price_display_condition) && $price_display_condition == 'show_dynamically') {
                    add_filter('advanced_woo_discount_rules_dynamic_get_price_html',array(self::$admin, 'separateDynamicPriceHtmlOmnibusMessage'), 100, 3);
                }
            } else {
                add_action($position_to_show_message, array(self::$admin, 'separateOmnibusMessageForDiscountRule'));
            }
        }

        if($position_to_show_message_for_omnibus == 'woocommerce_get_price_html' || $position_to_show_message == 'woocommerce_get_price_html'){
            add_action('wp_loaded', array(self::$helper, 'changeDiscountRulesPriceHtmlPriority'));
        }

        if($is_omnibus_plugin_active == 1) {
            if(isset($price_display_condition) && $price_display_condition == 'show_dynamically' && $position_to_show_message_for_omnibus == 'woocommerce_get_price_html') {
                add_filter('advanced_woo_discount_rules_dynamic_get_price_html',array(self::$admin, 'DynamicPriceHtmlForOmnibusCompatible'), 10, 3);
            }
        }

        if(is_admin()) {
            add_action('woocommerce_product_options_pricing', array(self::$admin, 'showLowestPriceInProductEditPage'), 1);
            add_action('woocommerce_variation_options_pricing', array(self::$admin, 'showLowestPriceInProductEditPageForVariants'), 10, 3);
            add_filter('plugin_action_links_' . WDR_OD_PLUGIN_BASENAME, array(self::$admin, 'wdrOmActionLink'));
            add_filter('advanced_woo_discount_rules_page_addons', array(self::$helper, 'omnibusAddon'));
            add_action('admin_init', array(self::$admin, 'saveSettingsData'));
            add_action('admin_enqueue_scripts', array(self::$admin, 'scriptFiles'));
        }
    }
}