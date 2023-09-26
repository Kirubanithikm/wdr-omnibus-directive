<?php
namespace WDR_OD\App\Controllers\Admin;
use Wdr\App\Helpers\Rule;
use Wdr\App\Helpers\Woocommerce;
use WDR_OD\App\Helpers\Helper;
defined('ABSPATH') or exit;

class Admin
{
    /**
     * @var Helper
     */
    private static $helper, $check_rule_for_merged_message;

    public function __construct()
    {
        self::$helper = empty( self::$helper) ? new Helper() : self::$helper;
    }

    /**
     * Show minimum price message using omnibus event
     * @param $message
     * @param $price
     * @param $price_lowest
     * @return mixed|string
     */
    public static function mergeOmnibusMessageWithDiscountRule($message, $price, $price_lowest) {

        global $product;
        $is_eligible = self::$helper->checkRuleId($product);
        $product_id = (int)Woocommerce::getProductId($product);
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);
        if(isset($is_eligible) && empty($is_eligible) && empty($wdr_od_price_current)){
            return $message;
        }

        $display = self::$helper->getAndUpdateMinimumPrice($product, $is_eligible);

        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return $message;
        }

        $lowest_price_date = isset($display['date']) && !empty($display['date']) ? $display['date'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);
        $min_price = !empty($display['min_price']) ? $display['min_price'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);

        $message = !empty($display) ? self::$helper->getFormattedOmnibusMessage($min_price, $lowest_price_date) : '';

        return apply_filters('wdr_omnibus_directive_merge_omnibus_message_with_discount_rule', $message, $min_price, $lowest_price_date);
    }

    /**
     * Change omnibus price lowest for woocommerce_get_price_html event
     * @param $price_lowest
     * @param $product
     * @return mixed|null
     */
    public static function changeOmnibusPriceLowest($price_lowest, $product) {

        $is_eligible = self::$helper->checkRuleId($product);
        $product_id = (int)Woocommerce::getProductId($product);
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);
        if(isset($is_eligible) && empty($is_eligible) && empty($wdr_od_price_current)){
            self::$check_rule_for_merged_message = true;
            return $price_lowest;
        }

        $display = self::$helper->getAndUpdateMinimumPrice($product, $is_eligible);

        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return $price_lowest;
        }

        $lowest_price_date = isset($display['date']) && !empty($display['date']) ? $display['date'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);
        $min_price = !empty($display['min_price']) ? $display['min_price'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);

        $price_lowest['price'] = $min_price;
        $price_lowest['timestamp'] = $lowest_price_date;
        $price_lowest['price_including_tax'] = null;

        return apply_filters('wdr_omnibus_directive_change_omnibus_price_lowest', $price_lowest, $min_price, $lowest_price_date);
    }

    /**
     * Change omnibus message for woocommerce_get_price_html template
     * @param $message
     * @return mixed|null
     */
    public static function changeOmnibusMessageTemplate($message) {
        if(self::$check_rule_for_merged_message) {
            return $message;
        }
        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return $message;
        }
        $message = isset($settings_data['message']) && !empty($settings_data['message']) ? $settings_data['message'] : __('Previous lowest price: {{price}}', 'wdr-omnibus-directive');
        $message = __($message, 'wdr-omnibus-directive');
        $message = str_replace('{{price}}', '{price}', $message);
        $message = str_replace('{{date}}', date_i18n(get_option('date_format'),"{timestamp}"), $message);

        return apply_filters('wdr_omnibus_directive_change_omnibus_message_template', $message);
    }

    /**
     * Show message using separate event
     * @return string|void
     */
    public static function separateOmnibusMessageForDiscountRule() {
        global $product;
        $is_eligible = self::$helper->checkRuleId($product);
        $product_id = (int)Woocommerce::getProductId($product);
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);
        if(isset($is_eligible) && empty($is_eligible) && empty($wdr_od_price_current)){
            return '';
        }

        $display = self::$helper->getAndUpdateMinimumPrice($product, $is_eligible);

        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return '';
        }

        $lowest_price_date = isset($display['date']) && !empty($display['date']) ? $display['date'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);
        $min_price = !empty($display['min_price']) ? $display['min_price'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);

        $get_omnibus_message = !empty($display) ? self::$helper->getFormattedOmnibusMessage($min_price, $lowest_price_date) : '';
        $message = !empty($get_omnibus_message) ? '<div class="wdr-od-message">' . $get_omnibus_message . '</div>' : '';

        $message = apply_filters('wdr_omnibus_directive_separate_omnibus_message', $message, $min_price, $lowest_price_date);
        _e($message, 'wdr-omnibus-directive');
    }

    /**
     * Show message using separate event for woocommerce_get_price_html hook
     * @param $price
     * @param $product
     * @return mixed|string
     */
    public static function separateGetPriceHtmlOmnibusMessage($price, $product) {

        if(!is_product()) {
            return $price;
        }
        $product_id = (int)Woocommerce::getProductId($product);
        $is_eligible = self::$helper->checkRuleId($product);
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);
        if(isset($is_eligible) && empty($is_eligible) && empty($wdr_od_price_current)){
            return $price;
        }

        $display = self::$helper->getAndUpdateMinimumPrice($product, $is_eligible);

        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return $price;
        }

        $lowest_price_date = isset($display['date']) && !empty($display['date']) ? $display['date'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);
        $min_price = !empty($display['min_price']) ? $display['min_price'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);

        $get_omnibus_message = !empty($display) ? self::$helper->getFormattedOmnibusMessage($min_price, $lowest_price_date) : '';
        $message = !empty($get_omnibus_message) ? '<div class="wdr-od-message">' . $get_omnibus_message . '</div>' : '';

        $message = apply_filters('wdr_omnibus_directive_separate_get_price_html_message', $message, $min_price, $lowest_price_date);
        return $price . $message;
    }

    /**
     * Show message using separate event for dynamic price html
     * @param $price_html
     * @param $product
     * @param $awdr_request
     * @return mixed|string
     */
    public static function separateDynamicPriceHtmlOmnibusMessage($price_html, $product, $awdr_request) {

        if($product->get_type() != 'variation'){
            return $price_html;
        }
        $product_id = (int)Woocommerce::getProductId($product);
        $is_eligible = self::$helper->checkRuleId($product);
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);
        if(isset($is_eligible) && empty($is_eligible) && empty($wdr_od_price_current)){
            return $price_html;
        }

        $display = self::$helper->getAndUpdateMinimumPrice($product, $is_eligible);

        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return $price_html;
        }

        $lowest_price_date = isset($display['date']) && !empty($display['date']) ? $display['date'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);
        $min_price = !empty($display['min_price']) ? $display['min_price'] : self::$helper->getMinimumPriceAndDate($product_id, $is_eligible);

        $get_omnibus_message = !empty($display) ? self::$helper->getFormattedOmnibusMessage($min_price, $lowest_price_date) : '';
        $message = !empty($get_omnibus_message) ? '<div class="wdr-od-message">' . $get_omnibus_message . '</div>' : '';

        $message = apply_filters('wdr_omnibus_directive_separate_dynamic_price_html_message', $message, $min_price, $lowest_price_date);
        return $price_html . $message;
    }

    /**
     * Discount rule dynamic price html compatibility
     * @param $price_html
     * @param $product
     * @param $awdr_request
     * @return string
     */
    public static function DynamicPriceHtmlForOmnibusCompatible($price_html, $product, $awdr_request) {
        if($product->get_type() != 'variation'){
            return $price_html;
        }
        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if(empty($is_show_omnibus_message)){
            return $price_html;
        }
        $product_id = (int)Woocommerce::getProductId($product);
        if(empty($product_id)) {
            return $price_html;
        }

        ob_start();
        do_action( 'iworks_omnibus_wc_lowest_price_message', $product_id);
        return $price_html . ob_get_clean();
    }

    /**
     * Show the lowest price in product edit page
     * @return void
     */
    public static function showLowestPriceInProductEditPage() {
        global $post;
        $post_id = $post->ID;
        $data_for_product_edit_page = self::$helper->getLowestPriceForProductEditPage($post_id);
        $space = '<br>'.'<br>'.str_repeat('&nbsp;', 2);
        self::$helper->headerForShowLowestPriceInProductEditPage('description', $space);
        self::$helper->showLowestPreviewPriceInProductEditPage($data_for_product_edit_page['price_lowest'], $data_for_product_edit_page['number_of_days']);
        self::$helper->showLowestPreviewPriceDateInProductEditPage($data_for_product_edit_page['timestamp'], $data_for_product_edit_page['number_of_days']);
    }

    /**
     * Show the lowest price in product edit page for variants
     * @param $loop
     * @param $variation_data
     * @param $variation
     * @return void
     */
    public static function showLowestPriceInProductEditPageForVariants($loop, $variation_data, $variation) {
        $post_id = $variation->ID;
        $data_for_product_edit_page = self::$helper->getLowestPriceForProductEditPage($post_id);
        self::$helper->headerForShowLowestPriceInProductEditPage('form-row form-row-full');
        self::$helper->showLowestPreviewPriceInProductEditPage($data_for_product_edit_page['price_lowest'], $data_for_product_edit_page['number_of_days'],['wrapper_class' => 'form-row form-row-first']);
        self::$helper->showLowestPreviewPriceDateInProductEditPage($data_for_product_edit_page['timestamp'], $data_for_product_edit_page['number_of_days'],['wrapper_class' => 'form-row form-row-last']);
    }

    /**
     * Update omnibus add-on setting tab settings data
     * @return void
     */
    public static function saveSettingsData() {
        if(isset($_POST['wdr-od-submit'])) {
            if (wp_verify_nonce($_POST['wdr_od_nonce_name'], 'wdr_od_nonce_action')) {

                $number_of_days = $_POST['wdr_od_number_of_days'];
                $is_show_omnibus_message_option = $_POST['wdr_od_is_show_message_option'];
                $message = $_POST['wdr_od_message'];
                $is_override_omnibus_message = $_POST['wdr_od_is_override_omnibus_message'];
                $position_to_show_message = $_POST['wdr_od_position_to_show_message'];

                $acceptable = array('1','0');
                $settings_data = [
                    'number_of_days' => isset($number_of_days) && is_numeric($number_of_days) && $number_of_days >= 30 ? round($number_of_days) : 30,
                    'is_show_omnibus_message_option' => isset($is_show_omnibus_message_option) && in_array($is_show_omnibus_message_option, $acceptable,true) ? $is_show_omnibus_message_option : 0,
                    'message' => Rule::validateHtmlBeforeSave(isset($message) ? trim(wp_unslash($message)) : null),
                    'is_override_omnibus_message' => isset($is_override_omnibus_message) && in_array($is_override_omnibus_message, $acceptable,true) ? $is_override_omnibus_message : 0,
                    'position_to_show_message' => isset($position_to_show_message) ? sanitize_text_field($position_to_show_message) : 'woocommerce_get_price_html',
                ];
                update_option('wdr_omnibus_directive',$settings_data);
                wp_safe_redirect(add_query_arg('saved', 'true'));
            } else {
                wp_safe_redirect(add_query_arg('saved', 'false'));
            }
            exit();
        }
    }

    /**
     * To load the script files
     * @return void
     */
    public static function scriptFiles() {
        if(isset($_GET['page']) && isset($_GET['addon']) && $_GET['page'] == 'woo_discount_rules' && $_GET['addon'] == 'omnibus_directive') {
            wp_enqueue_script('wdr_od_add_js',trailingslashit(WDR_OD_PLUGIN_URL) . 'assets/Js/index.js',array('jquery'), WDR_OD_VERSION);
        }
    }

    /**
     * Add settings link in plugins page
     * @param $links
     * @return string[]
     */
    public static function wdrOmActionLink($links) {
        $action_links = array(
            'settings' => '<a href="' . esc_url(admin_url('admin.php?page=woo_discount_rules&tab=addons&addon=omnibus_directive&section=settings')) . '">' . __('Settings', 'wdr-omnibus-directive') . '</a>',
        );
        return array_merge($action_links, $links);
    }
}