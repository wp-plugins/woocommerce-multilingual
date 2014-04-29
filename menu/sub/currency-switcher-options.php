<?php
global $woocommerce_wpml;
$settings = $woocommerce_wpml->get_settings();
$currency_switcher_style = isset($settings['currency_switcher_style'])?$settings['currency_switcher_style']:false;
?>

<div id="currency-switcher" <?php if ( $settings['enable_multi_currency'] != WCML_MULTI_CURRENCIES_INDEPENDENT ):?>style="display:none"<?php endif;?>>

    <div class="wcml-section-header">
        <h3>
            <?php _e('Currency switcher options', 'wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Currency switcher', 'wpml-wcml') ?>" data-content="<?php _e('You can customize currency switcher on front-end', 'wpml-wcml') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">
        <div class="wcml-section-content-inner">
            <h4><?php _e('Currency order', 'wpml-wcml'); ?></h4>
            <?php
            $wc_currencies = get_woocommerce_currencies();

            if(!isset($settings['currencies_order'])){
                $currencies = $woocommerce_wpml->multi_currency_support->get_currency_codes();
            }else{
                $currencies = $settings['currencies_order'];
            }
             ?>
            <ul id="wcml_currencies_order">
                <?php foreach($currencies as $currency): ?>
                    <li class="wcml_currencies_order_<?php echo $currency ?>" ><?php echo $wc_currencies[$currency].' ('.get_woocommerce_currency_symbol($currency).')'; ?></li>
                <?php endforeach; ?>
            </ul>
            <span style="display:none;" class="wcml_currencies_order_ajx_resp"></span>
            <input type="hidden" id="wcml_currencies_order_order_nonce" value="<?php echo wp_create_nonce('set_currencies_order_nonce') ?>" />
            <p class="explanation-text"><?php _e('Drag the currencies to change their order', 'wpml-wcml') ?></p>
        </div>
        <div class="wcml-section-content-inner">
            <h4><?php _e('Currency switcher style', 'wpml-wcml'); ?></h4>
            <ul class="wcml_curr_style">
                <li>
                    <label for="wcml_curr_sel_stype">
                        <input type="radio" name="currency_switcher_style" value="dropdown" <?php if(!$currency_switcher_style || $currency_switcher_style == 'dropdown'):?>checked="checked"<?php endif?> />
                        <?php echo __('Drop-down menu', 'wpml-wcml') ?>
                    </label>
                </li>
                <li>
                    <label for="wcml_curr_sel_orientation">
                        <input type="radio" name="currency_switcher_style" value="list" <?php if($currency_switcher_style == 'list'):?>checked="checked"<?php endif?> />
                        <?php echo __('List of currencies', 'wpml-wcml') ?>
                    </label>
                    <select id="wcml_curr_sel_orientation" name="wcml_curr_sel_orientation" <?php if($currency_switcher_style != 'list'): ?>style="display: none;"<?php endif;?>>
                        <option value="vertical"><?php _e('Vertical', 'wpml-wcml') ?></option>
                        <option value="horizontal" <?php if(isset($settings['wcml_curr_sel_orientation']) && $settings['wcml_curr_sel_orientation'] == 'horizontal'): ?>selected="selected"<?php endif;?>><?php _e('Horizontal', 'wpml-wcml') ?></option>
                    </select>
                </li>
            </ul>
        </div>
        <div class="wcml-section-content-inner">
            <h4><?php _e('Available parameters', 'wpml-wcml'); ?></h4>
            <span class="explanation-text"><?php _e('%name%, %symbol%, %code%', 'wpml-wcml'); ?></span>
            <h4><?php _e('Template for currency switcher', 'wpml-wcml'); ?></h4>
            <input type="text" name="wcml_curr_template" value="<?php echo isset($settings['wcml_curr_template'])?$settings['wcml_curr_template']:''; ?>" />
            <span class="explanation-text"><?php _e('Default: %name% (%symbol%) - %code%', 'wpml-wcml'); ?></span>
            <input type="hidden" id="currency_switcher_default" value="%name% (%symbol%) - %code%" />
            <div id="wcml_curr_sel_preview_wrap">
                <p><strong><?php _e('Currency switcher preview', 'wpml-wcml')?></strong></p>
                <input type="hidden" id="wcml_currencies_switcher_preview_nonce" value="<?php echo wp_create_nonce('wcml_currencies_switcher_preview') ?>" />
                <div id="wcml_curr_sel_preview">
                    <?php echo $woocommerce_wpml->multi_currency_support->currency_switcher(); ?>
                </div>
            </div>
        </div>
    </div>
    <p class="button-wrap general_option_btn">
        <input type='submit' name="currency_switcher_options" value='<?php _e('Save', 'wpml-wcml'); ?>' class='button-secondary' />
        <?php wp_nonce_field('currency_switcher_options', 'currency_switcher_options_nonce'); ?>
    </p>

</div>
