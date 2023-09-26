<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    #wdr-od-configuration-form table tbody tr > td:first-child {
        width: 400px;
    }
</style>
<br>

<div id="wpbody-content" class="awdr-container">
    <?php if(isset($settings_saved_status)) {
        $status = !empty($settings_saved_status) ? 'success' : 'error';
        $notice_message = !empty($settings_saved_status) ? __('Saved successfully.', 'wdr-omnibus-directive') : __('Error occurred.', 'wdr-omnibus-directive');
        if (!empty($notice_message)) { ?>
            <div class="notice notice-<?php echo esc_attr($status); ?>">
                <p><?php echo esc_html($notice_message); ?></p>
            </div>
            <div class="clear"></div>
        <?php } ?>
    <?php } ?>

    <div id="wpbody-content" class="awdr-container">
        <div class="awdr-configuration-form">
            <form id="wdr-od-configuration-form" method="post">
                <h1><?php esc_attr_e('Omnibus Directive : General settings', 'wdr-omnibus-directive') ?></h1>
                <div class="notice notice-info">
                    <p><?php echo esc_html('Currently, we support only product adjustment rules.'); ?></p>
                </div>
                <table class="wdr-general-setting form-table">
                    <tbody style="background-color: #fff;">
                    <tr>
                        <td>
                            <label class="awdr-left-align"><?php esc_attr_e('Number of days', 'wdr-omnibus-directive') ?></label>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php esc_attr_e('Record and display number of days after sale was started', 'wdr-omnibus-directive'); ?></span>
                        </td>
                        <td>
                            <input type="number" name="wdr_od_number_of_days" value="<?php echo esc_attr($number_of_days);?>" title="Number of days" size="4" min="30" max="" step="1" >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="awdr-left-align"><?php esc_attr_e('Show Omnibus message on product page', 'wdr-omnibus-directive') ?></label>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php esc_attr_e('Display Omnibus message on product page', 'wdr-omnibus-directive'); ?></span>
                        </td>
                        <td>
                            <input type="radio" data-name="hide_table_position" name="wdr_od_is_show_message_option"
                                   id="is_show_omnibus_message_option_1" value="1"
                                <?php echo(!empty($is_show_omnibus_message_option) ? 'checked' : '')  ?>><label
                                    for="is_show_omnibus_message_option_1"><?php esc_attr_e('Yes', 'wdr-omnibus-directive'); ?></label>

                            <input type="radio" data-name="hide_table_position" name="wdr_od_is_show_message_option"
                                   id="is_show_omnibus_message_option" value="0"
                                <?php echo(empty($is_show_omnibus_message_option) ? 'checked' : '') ?>><label
                                    for="is_show_omnibus_message_option"><?php esc_attr_e('No', 'wdr-omnibus-directive'); ?></label>
                        </td>
                    </tr>
                    <tr class="hide_table_position" id="wdr_od_omnibus_message" style="<?php echo empty($is_show_omnibus_message_option) ? 'display:none' : ''; ?>">
                        <td>
                            <label class="awdr-left-align"><?php esc_attr_e('Omnibus Message', 'wdr-omnibus-directive') ?></label>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php esc_attr_e('You can use the following shortcode', 'wdr-omnibus-directive'); ?></span>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php esc_attr_e('{{price}} -> Replace the lowest price', 'wdr-omnibus-directive'); ?></span>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php esc_attr_e('{{date}} -> Display the day when was lowest price', 'wdr-omnibus-directive'); ?></span>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php _e('<strong>Eg</strong>:') . esc_html_e('Previous lowest price: {{price}}', 'wdr-omnibus-directive'); ?></span>
                        </td>
                        <td>
                            <textarea name="wdr_od_message" rows="5"  cols="30" > <?php esc_attr_e($message, 'wdr-omnibus-directive'); ?> </textarea>
                        </td>
                    </tr>
                    <tr class="hide_table_position" id="<?php echo empty($is_omnibus_plugin_active) ? 'wdr_od_override_omnibus_message_hide' : 'wdr_od_override_omnibus_message_show'; ?>" style="<?php echo empty($is_show_omnibus_message_option) || empty($is_omnibus_plugin_active) ? 'display:none' : ''; ?>" >
                        <td>
                        </td>
                        <td>
                            <input type="checkbox" name="wdr_od_is_override_omnibus_message" id="wdr_od_is_override_omnibus_message" value="1" <?php echo ( $is_override_omnibus_message == 1 ? 'checked' : '') ?>>
                            <label for="wdr_od_is_override_omnibus_message"><?php esc_attr_e('Override the message displayed by Omnibus plugin', 'wdr-omnibus-directive'); ?></label>
                        </td>
                    </tr>
                    <tr class="hide_table_position" id="wdr_od_select_message_position" style="<?php echo  (empty($is_show_omnibus_message_option) || !empty($is_omnibus_plugin_active)) && (empty($is_show_omnibus_message_option) || !empty($is_override_omnibus_message)) ? 'display:none' : ''; ?>" >
                        <td>
                            <label class="awdr-left-align"><?php esc_attr_e('Position to show message', 'wdr-omnibus-directive') ?></label>
                            <span class="wdr_settings_desc_text awdr-clear-both"><?php esc_attr_e('Position to show message on product page', 'wdr-omnibus-directive'); ?></span>
                        </td>
                        <td>
                            <select name="wdr_od_position_to_show_message">
                                <option value="woocommerce_get_price_html" <?php echo $position_to_show_message == 'woocommerce_get_price_html' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce get price html', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_single_product_summary" <?php echo $position_to_show_message == 'woocommerce_single_product_summary' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce single product summary', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_before_add_to_cart_form" <?php echo $position_to_show_message == 'woocommerce_before_add_to_cart_form' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce before add to cart form', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_product_meta_end" <?php echo $position_to_show_message == 'woocommerce_product_meta_end' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce product meta end', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_product_meta_start" <?php echo $position_to_show_message == 'woocommerce_product_meta_start' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce product meta start', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_before_add_to_cart_button" <?php echo $position_to_show_message == 'woocommerce_before_add_to_cart_button' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce before add to cart button', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_after_add_to_cart_quantity" <?php echo $position_to_show_message == 'woocommerce_after_add_to_cart_quantity' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce after add to cart quantity', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_after_add_to_cart_form" <?php echo $position_to_show_message == 'woocommerce_after_add_to_cart_form' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce after add to cart form', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_after_single_product" <?php echo $position_to_show_message == 'woocommerce_after_single_product' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce after single product', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_before_single_product" <?php echo $position_to_show_message == 'woocommerce_before_single_product' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce before single product', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_after_single_product_summary" <?php echo $position_to_show_message == 'woocommerce_after_single_product_summary' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce after single product summary', 'wdr-omnibus-directive'); ?></option>
                                <option value="woocommerce_before_single_product_summary" <?php echo $position_to_show_message == 'woocommerce_before_single_product_summary' ? 'selected' : ''; ?>><?php esc_attr_e('WooCommerce before single product summary', 'wdr-omnibus-directive'); ?></option>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <br>
                <?php wp_nonce_field('wdr_od_nonce_action', 'wdr_od_nonce_name'); ?>
                <input class="button button-primary" type="submit" name="wdr-od-submit" value="Submit">
            </form>
        </div>
    </div>