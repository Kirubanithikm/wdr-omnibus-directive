<?php
namespace WDR_OD\App\Helpers;
use Wdr\App\Controllers\DiscountCalculator;
use Wdr\App\Helpers\Woocommerce;
use Wdr\App\Router;
use WDR_OD\App\Controllers\Admin\OmnibusAddon;
defined('ABSPATH') or exit;

class Helper {

    /**
     * Get and update minimum price
     * @return int|mixed
     */
    public function getAndUpdateMinimumPrice($product, $is_eligible) {
        $product_id = Woocommerce::getProductId($product);
        $price = Woocommerce::getProductPrice($product);
        if ($product->get_type() == 'variable') {
            $discount_price = array();
            $min_discounted_price = false;
            $available_variations = $product->get_variation_prices();
            foreach ($available_variations['regular_price'] as $key => $regular_price) {
                if (function_exists('wc_get_product')) {
                    $product_variation = wc_get_product($key);
                    $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $product_variation, 1, 0, 'discounted_price', true, false);
                    if (isset($discount) && $discount !== false) {
                        $discount_price[] = $discount;
                        $this->updateMinimumPrice($discount, $key, $is_eligible);
                        $min_discounted_price = (min($discount_price));
                    }
                }
            }
            $discount = $min_discounted_price;
        } else {
            $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', $price, $product, 1, 0, 'discounted_price', true, false);
        }
        return $this->updateMinimumPrice($discount, $product_id, $is_eligible);
    }

    /**
     * Update the minimum price
     * @param $discount
     * @param $product_id
     * @param $is_eligible
     * @return mixed|null
     */
    public function updateMinimumPrice($discount, $product_id, $is_eligible) {
        $settings_data = get_option('wdr_omnibus_directive');
        $number_of_days = isset($settings_data['number_of_days']) ? $settings_data['number_of_days'] : 30;
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);
        $wdr_od_price_history = get_post_meta($product_id, '_wdr_od_price_history', true);

        // Update the current price in _wdr_od_price_current meta key
        if ($discount !== false && empty($wdr_od_price_current)) {
            $wdr_od_price_current_update = [
                'price' => $discount,
                'timestamp' => current_time('timestamp', true),
            ];
            update_post_meta($product_id, '_wdr_od_price_current', $wdr_od_price_current_update);
        }

        if(!empty($wdr_od_price_current)) {
            $current_price_time_difference = current_time('timestamp', true) - $wdr_od_price_current['timestamp'];
            if ($current_price_time_difference > $number_of_days * 24 * 60 * 60) {
                delete_post_meta($product_id, '_wdr_od_price_current');
            }
        }

        if(empty($wdr_od_price_history)){
            $wdr_od_price_history = array();
        }

        foreach ($wdr_od_price_history as $key => $wdr_od_price_history_data) {
            $history_price_time_difference = current_time('timestamp', true) - $wdr_od_price_history_data['timestamp'];
            if($history_price_time_difference > $number_of_days * 24 * 60 * 60 ) {
                unset($wdr_od_price_history[$key]);
                update_post_meta($product_id, '_wdr_od_price_history', $wdr_od_price_history);
            }
        }

        // Update the price history in _wdr_od_price_history meta key
        if($discount !== false && isset($wdr_od_price_current['price'])) {
            if ($discount < $wdr_od_price_current['price']) {
                $wdr_od_price_history_update = [
                    'price' => $wdr_od_price_current['price'],
                    'timestamp' => $wdr_od_price_current['timestamp'],
                ];

                $wdr_od_price_current_update = [
                    'price' => $discount,
                    'timestamp' => current_time('timestamp', true),
                ];

                $wdr_od_price_history[] = $wdr_od_price_history_update;
                sort($wdr_od_price_history);

                update_post_meta($product_id, '_wdr_od_price_current', $wdr_od_price_current_update);
                update_post_meta($product_id, '_wdr_od_price_history', $wdr_od_price_history);
            }

            if ($discount > $wdr_od_price_current['price'] && !empty($wdr_od_price_history) && is_array($wdr_od_price_history)) {
                $lowest_history_price = min(array_column($wdr_od_price_history, 'price'));
                if ($wdr_od_price_current['price'] < $lowest_history_price) {

                    $wdr_od_price_history_update = [
                        'price' => $wdr_od_price_current['price'],
                        'timestamp' => $wdr_od_price_current['timestamp'],
                    ];

                    $wdr_od_price_history[] = $wdr_od_price_history_update;
                    sort($wdr_od_price_history);

                    update_post_meta($product_id, '_wdr_od_price_history', $wdr_od_price_history);
                }
            }
        }

        return $this->getMinimumPriceAndDate($product_id, $is_eligible);
    }

    /**
     * Get minimum price and date for display
     * @param $product_id
     * @param $is_eligible
     * @return mixed|null
     */
    public function getMinimumPriceAndDate($product_id, $is_eligible) {
        $display = array();
        $wdr_od_price_history = get_post_meta($product_id, '_wdr_od_price_history', true);
        $wdr_od_price_current = get_post_meta($product_id, '_wdr_od_price_current', true);

        if(!empty($wdr_od_price_history) && is_array($wdr_od_price_history)){
            $prices = array_column($wdr_od_price_history, 'price');
            $display['min_price'] = min($prices);
            foreach ($wdr_od_price_history as $wdr_od_price_history_data) {
                if (isset($display['min_price']) && $wdr_od_price_history_data['price'] == $display['min_price']) {
                    $display['date'] = $wdr_od_price_history_data['timestamp'];
                    break;
                }
            }
        }

        if(empty($wdr_od_price_history) && empty($display) && !empty($wdr_od_price_current) && is_array($wdr_od_price_current)) {
            $display['min_price'] = $wdr_od_price_current['price'];
            $display['date'] = $wdr_od_price_current['timestamp'];
        }

        if(isset($is_eligible) && empty($is_eligible)) {
            if(!empty($display['min_price']) && !empty($wdr_od_price_current) && $display['min_price'] > $wdr_od_price_current['price']){
                $display['min_price'] = $wdr_od_price_current['price'];
                $display['date'] = $wdr_od_price_current['timestamp'];
            }
        }

        return apply_filters('wdr_omnibus_directive_display_price_and_date', isset($display) ? $display : array() );
    }

    /**
     * Get formatted omnibus message
     * @param $min_price
     * @param $lowest_price_date
     * @return array|string|string[]|null
     */
    public function getFormattedOmnibusMessage($min_price, $lowest_price_date) {
        $message = '';
        $settings_data = get_option('wdr_omnibus_directive');
        $is_show_omnibus_message = isset($settings_data['is_show_omnibus_message_option']) ? $settings_data['is_show_omnibus_message_option'] : 0;
        if (!empty($is_show_omnibus_message)) {
            $message = isset($settings_data['message']) && !empty($settings_data['message']) ? $settings_data['message'] : __('Previous lowest price: {{price}}', 'wdr-omnibus-directive');
            $message = __($message, 'wdr-omnibus-directive');
            $message = str_replace('{{price}}', wc_price($min_price), $message);
            $date_format = apply_filters('wdr_omnibus_directive_message_date_format',date_i18n(get_option('date_format'),$lowest_price_date), $lowest_price_date, $min_price);
            $message = str_replace('{{date}}', $date_format, $message);
        }
        return $message;
    }

    /**
     * Get the lowest price for product edit page
     * @param $post_id
     * @return array
     */
    public function getLowestPriceForProductEditPage($post_id) {
        $data_for_product_edit_page = [];
        $price_history = get_post_meta($post_id, '_wdr_od_price_history', true);
        $data_for_product_edit_page['price_lowest'] = 0;
        $data_for_product_edit_page['timestamp'] = 0;
        $wdr_od_price_current = get_post_meta($post_id, '_wdr_od_price_current', true);
        $product = wc_get_product($post_id);
        $is_eligible = $this->checkRuleId($product);

        if(!empty($price_history) && is_array($price_history)){
            $prices = array_column($price_history, 'price');
            $data_for_product_edit_page['price_lowest'] = min($prices);
            foreach ($price_history as $price_history_data) {
                if($price_history_data['price'] == $data_for_product_edit_page['price_lowest']){
                    $data_for_product_edit_page['timestamp'] = $price_history_data['timestamp'];
                }
            }
        }

        if(empty($data_for_product_edit_page['price_lowest']) && empty($data_for_product_edit_page['timestamp'])) {
            if (empty($wdr_od_price_history) && !empty($wdr_od_price_current) && is_array($wdr_od_price_current)) {
                $data_for_product_edit_page['price_lowest'] = $wdr_od_price_current['price'];
                $data_for_product_edit_page['timestamp'] = $wdr_od_price_current['timestamp'];
            }
        }

        if(isset($is_eligible) && empty($is_eligible)) {
            if(isset($data_for_product_edit_page['price_lowest']) && !empty($wdr_od_price_current)){
                $data_for_product_edit_page['price_lowest'] = min($data_for_product_edit_page['price_lowest'], $wdr_od_price_current['price']);
            }
        }

        $settings_data = get_option('wdr_omnibus_directive');
        $data_for_product_edit_page['number_of_days'] = isset($settings_data['number_of_days']) ? $settings_data['number_of_days'] : 30;

        return $data_for_product_edit_page;
    }

    /**
     * Header for lowest price display field in product edit page
     * @param $class
     * @param $space
     * @return void
     */
    public static function headerForShowLowestPriceInProductEditPage($class = '', $space='') {
        printf(
            '<h4%s>%s</h4>',
            empty($class) ? '' : sprintf(' class="%s"', esc_attr($class)),
            esc_html__('Omnibus Directive - From Discount Rules', 'wdr-omnibus-directive')
        );
        echo $space; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        esc_html_e('This data will update dynamically when customer access the product page (As in Discount Rules, the discount applied on run time).', 'wdr-omnibus-directive');
    }

    /**
     * Price input field for show the lowest price of the product in product edit page
     * @param $price_lowest
     * @param $number_of_days
     * @param $configuration
     * @return void
     */
    public static function showLowestPreviewPriceInProductEditPage($price_lowest, $number_of_days, $configuration = array()) {
        woocommerce_wp_text_input(
            wp_parse_args(
                array(
                    'id'                => 'wdr-od-price-history-price',
                    'custom_attributes' => array('disabled' => 'disabled'),
                    'value'             => empty($price_lowest) ? __('no data', 'wdr-omnibus-directive') : $price_lowest,
                    'data_type'         => 'price',
                    'label'             => __('Price','wdr-omnibus-directive').'('.get_woocommerce_currency_symbol().')',
                    'desc_tip'          => true,
                    'description'       => sprintf(
                        __('The lowest price in %d days.','wdr-omnibus-directive'),
                        $number_of_days
                    ),
                ),
                $configuration
            )
        );
    }

    /**
     * Date input field for show the lowest price edit date in product edit page
     * @param $timestamp
     * @param $number_of_days
     * @param $configuration
     * @return void
     */
    public static function showLowestPreviewPriceDateInProductEditPage($timestamp, $number_of_days, $configuration = array()) {
        woocommerce_wp_text_input(
            wp_parse_args(
                array(
                    'id'                => 'wdr-od-price-history-date',
                    'custom_attributes' => array('disabled' => 'disabled'),
                    'value'             => empty($timestamp) ? esc_html__('no data', 'wdr-omnibus-directive') : date_i18n(get_option('date_format'),$timestamp),
                    'data_type'         => 'text',
                    'label'             => __('Date','wdr-omnibus-directive'),
                    'desc_tip'          => true,
                    'description'       => sprintf(
                        __('The date when lowest price in %d days occurred.', 'wdr-omnibus-directive'),
                        $number_of_days
                    ),
                ),
                $configuration
            )
        );
    }

    /**
     * Add omnibus addon in discount rules
     * @param $addons
     * @return mixed
     */
    public static function omnibusAddon($addons) {
        $addons['omnibus_directive'] = new OmnibusAddon();
        return $addons;
    }

    /**
     * Check the rule is eligible or not
     * @return bool
     */
    public static function checkRuleId($product) {
        $discount = false;
        if ($product->get_type() == 'variable') {
            $available_variations = $product->get_variation_prices();
            foreach ($available_variations['regular_price'] as $key => $regular_price) {
                if (function_exists('wc_get_product')) {
                    $product_variation = wc_get_product($key);
                    $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', false, $product_variation, 1, 0, 'all', true, false);
                }
            }
        } else {
            $price = Woocommerce::getProductPrice($product);
            $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price_from_custom_price', $price, $product, 1, 0, 'all', true, false);
        }

        if($discount !== false){
            if(class_exists('\Wdr\App\Controllers\DiscountCalculator')) {
                if(isset($discount['total_discount_details']) && !empty($discount['total_discount_details'])){
                    $rules = DiscountCalculator::$rules;
                    $rule_ids = array_keys($discount['total_discount_details']);
                    foreach ($rule_ids as $rule_id) {
                        if(isset($rules[$rule_id])) {
                            $matched_rule = $rules[$rule_id]->rule; // Here we get the matched rule info
                            if($matched_rule->enabled == 1 && $matched_rule->discount_type == "wdr_simple_discount"){
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check Omnibus plugin active or not
     * @return bool
     */
    public function isOmnibusPluginActive() {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('omnibus/omnibus.php', $active_plugins, false) || array_key_exists('omnibus/omnibus.php', $active_plugins);
    }

    /**
     * Change discount rules price html priority
     * @return void
     */
    public function changeDiscountRulesPriceHtmlPriority() {
        if(class_exists('\Wdr\App\Router')){
            remove_filter('woocommerce_get_price_html', array(Router::$manage_discount, 'getPriceHtml'), 100, 2);
            add_filter('woocommerce_get_price_html', array(Router::$manage_discount, 'getPriceHtml'), 9, 2);
            remove_filter('woocommerce_variable_price_html', array(Router::$manage_discount, 'getVariablePriceHtml'), 100);
            add_filter('woocommerce_variable_price_html', array(Router::$manage_discount, 'getVariablePriceHtml'), 9, 2);
        }
    }

    /**
     * Get settings saved message status
     * @return bool|null
     */
    public function getSettingsSavedStatus() {
        if (isset($_GET['addon']) && $_GET['addon'] == "omnibus_directive" && isset($_GET['saved'])) {
            if($_GET['saved'] == "true") {
                return true;
            } else {
                return false;
            }
        }
        return null;
    }
}