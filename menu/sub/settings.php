<div class="wcml-section">
    <div class="wcml-section-header">
        <h3>
            <?php _e('Plugins Status','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Check required plugins', 'wpml-wcml') ?>" data-content="<?php _e('WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>
    <div class="wcml-section-content">
        <ul>         
            <?php if (defined('ICL_SITEPRESS_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML</strong>'); ?></li>            
            <?php endif; ?>
            <?php if (defined('WPML_MEDIA_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML Media</strong>'); ?></li>            
            <?php endif; ?>
            <?php if (defined('WPML_TM_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML Translation Management</strong>'); ?></li>            
            <?php endif; ?>
            <?php if (defined('WPML_ST_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML String Translation</strong>'); ?></li>
            <?php endif; ?>
            <?php
            global $woocommerce;
            if (class_exists('Woocommerce')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WooCommerce</strong>'); ?></li>           
        <?php endif; ?>
        </ul>
    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->
<?php global $sitepress_settings;  ?>
<?php if($sitepress->get_default_language() != 'en' && $sitepress_settings['st']['strings_language'] != 'en' || !empty($woocommerce_wpml->dependencies->xml_config_errors)): ?>
<div class="wcml-section">
    <div class="wcml-section-header">
        <h3>
            <?php _e('Configuration warnings','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Configuration warnings', 'wpml-wcml') ?>" data-content="<?php _e('Reporting miscelaneous configuration issues that can make WooCommerce Multilingual not run normally', 'wpml-wcml') ?>"></i>
        </h3>
    </div>
    
    <div class="wcml-section-content">        
        <?php if($sitepress->get_default_language() != 'en' && $sitepress_settings['st']['strings_language'] != 'en'): ?>
        <p><i class="icon-warning-sign"></i><strong><?php _e('Attention required: probable problem with URLs in different languages', 'wpml-wcml') ?></strong></p>
        
        <p><?php _e("Your site's default language is not English and the strings language is also not English. This may lead to problems with your site's URLs in different languages.", 'wpml-wcml') ?></p>
        
        <ul>
            <li>&raquo;&nbsp;<?php _e('Change the strings language to English', 'wpml-wcml') ?></li>
            <li>&raquo;&nbsp;<?php _e('Re-scan strings', 'wpml-wcml') ?></li>
        </ul>
        
        <p class="submit">
            <input type="hidden" id="wcml_fix_strings_language_nonce" value="<?php echo wp_create_nonce('wcml_fix_strings_language') ?>" />
            <input id="wcml_fix_strings_language" type="button" class="button-primary" value="<?php esc_attr_e('Run fix', 'wpml-wcml') ?>" />
        </p>
        <?php endif; ?>
        
        <?php if(!empty($woocommerce_wpml->dependencies->xml_config_errors)): ?>
        <p><i class="icon-warning-sign"></i><strong><?php _e('Some settings from the WooCommerce Multilingual wpml-config.xml file have been overwritten', 'wpml-wcml') ?></strong></p>
        <ul>
            <?php foreach($woocommerce_wpml->dependencies->xml_config_errors as $error): ?>
            <li><?php echo $error ?></li>
            <?php endforeach; ?>
        </ul>
        
        
        <?php endif; ?>
    
    </div>
        
</div>        
<?php endif; ?>


<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('WooCommerce Store Pages','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Translated store pages', 'wpml-wcml') ?>" data-content="<?php _e('To run a multilingual e-commerce site, you need to have the WooCommerce shop pages translated in all the site\'s languages. Once all the pages are installed you can add the translations for them from this menu.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">

        <?php $miss_lang = $woocommerce_wpml->store->get_missing_store_pages(); ?>
        <?php if($miss_lang == 'non_exist'): ?>
            <ul>
                <li>
                    <i class="icon-warning-sign"></i><span><?php _e("One or more WooCommerce pages have not been created.",'wpml-wcml'); ?></span>
                </li>
                <li><a href="<?php echo version_compare($woocommerce->version, '2.1', '<') ? admin_url('admin.php?page=woocommerce_settings&tab=pages') : admin_url('admin.php?page=wc-status&tab=tools'); ?>"><?php _e('Install WooCommerce Pages') ?></a></li>
            </ul>
        <?php elseif($miss_lang): ?>
            <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                <?php wp_nonce_field('create_pages', 'wcml_nonce'); ?>
                <input type="hidden" name="create_missing_pages" value="1"/>
                <div class="wcml_miss_lang">
                    </p>
                        <i class="icon-warning-sign"></i>
                        <?php
                        if(count($miss_lang['codes']) == 1){
                            _e("WooCommerce store pages don't exist for this language:",'wpml-wcml');
                        }else{
                            _e("WooCommerce store pages don't exist for these languages:",'wpml-wcml');
                        } ?>
                    </p>
                    <p>
                        <strong><?php echo $miss_lang['lang'] ?></strong>
                        <input class="button" type="submit" name="create_pages" value="<?php esc_attr(_e('Create missing translations.', 'wpml-wcml')) ?>" />
                        <a id="wcmp_hide" class="wcmp_lang_hide" href="javascript:void(0);"><?php _e('Hide this message', 'wpml-wcml') ?></a>
                    </p>
                </div>
                <p>
                    <a id="wcmp_show" class="none" href="javascript:void(0);"><?php _e('Show details about missing translations', 'wpml-wcml') ?></a>
                </p>
            </form>
        <?php else: ?>
            <p>
                <i class="icon-ok"></i><span><?php _e("WooCommerce store pages are translated to all the site's languages.",'wpml-wcml'); ?></span>
            </p>
        <?php endif; ?>

    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('Taxonomies missing translations','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php esc_attr_e('Taxonomies without translation', 'wpml-wcml') ?>" data-content="<?php esc_attr_e('To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content js-tax-translation">
        <input type="hidden" id="wcml_ingore_taxonomy_translation_nonce" value="<?php echo wp_create_nonce('wcml_ingore_taxonomy_translation_nonce') ?>" />
        <?php
        global $wp_taxonomies;
        $taxonomies = array();

        //don't use get_taxonomies for product, because when one more post type registered for product taxonomy functions returned taxonomies only for product type
        foreach($wp_taxonomies as $key=>$taxonomy){
            if((in_array('product',$taxonomy->object_type) || in_array('product_variation',$taxonomy->object_type) ) && !in_array($key,$taxonomies)){
                $taxonomies[] = $key;
            }
        }

        ?>
        
        <ul>         
        <?php
        $no_tax_to_update = true;
        foreach($taxonomies as $taxonomy): ?>
            <?php if($taxonomy == 'product_type' || WCML_Terms::get_untranslated_terms_number($taxonomy) == 0){
                continue;
            }else{
                $no_tax_to_update = false;
            }?>
            <li class="js-tax-translation-<?php echo $taxonomy ?>">
            <?php if($untranslated = WCML_Terms::get_untranslated_terms_number($taxonomy)): ?>
                <?php if(WCML_Terms::is_fully_translated($taxonomy)): // covers the 'ignore' case' ?>
                <i class="icon-ok"></i> <?php printf(__('%s do not require translation.', 'wpml-wcml'), get_taxonomy($taxonomy)->labels->name); ?>
                <div class="actions">
                    <a href="#unignore-<?php echo $taxonomy ?>" title="<?php esc_attr_e('This taxonomy requires translation.', 'wpml-wcml') ?>"><?php _e('Change', 'wpml-wcml') ?></a> 
                </div>
                <?php else: ?>
                <i class="icon-warning-sign"></i> <?php printf(__('Some %s are missing translations (%d translations missing).', 'wpml-wcml'), get_taxonomy($taxonomy)->labels->name, $untranslated); ?>
                <div class="actions">
                    <a href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy) ?>"><?php _e('Translate now', 'wpml-wcml') ?></a> | 
                    <a href="#ignore-<?php echo $taxonomy ?>" title="<?php esc_attr_e('This taxonomy does not require translation.', 'wpml-wcml') ?>"><?php _e('Ignore', 'wpml-wcml') ?></a> 
                </div>
                <?php endif; ?>
            <?php else: ?>
            <i class="icon-ok"></i> <?php printf(__('All %s are translated.', 'wpml-wcml'), get_taxonomy($taxonomy)->labels->name); ?>
            <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if($no_tax_to_update): ?>
            <li><i class="icon-ok"></i> <?php _e('Right now, there are no taxonomy terms needing translation.', 'wpml-wcml'); ?></li>
        <?php endif; ?>
        </ul>         
        
    
    </div>
    
</div>

<div class="wcml-section">
    <div class="wcml-section-header">
        <h3>
            <?php _e('Product Translation Interface','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Product translation interface', 'wpml-wcml') ?>" data-content="<?php _e('The recommended way to translate products is using the products translation table in the WooCommerce Multilingual admin. Choose to go to the native WooCommerce interface, if your products include custom sections that require direct access.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>
    <div class="wcml-section-content">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <?php wp_nonce_field('wcml_trsl_interface_table', 'wcml_nonce'); ?>
            <ul>
                <li>
                    <p><?php _e('Choose what to do when clicking on the translation controls for products:','wpml-wcml'); ?></p>
                </li>
                <li>
                    <input type="radio" name="trnsl_interface" value="1" <?php echo $woocommerce_wpml->settings['trnsl_interface'] == '1'?'checked':''; ?> id="wcml_trsl_interface_wcml" />
                    <label for="wcml_trsl_interface_wcml"><?php _e('Go to the product translation table in WooCommerce Multilingual', 'wpml-wcml'); ?></label>
                </li>
                <li>
                    <input type="radio" name="trnsl_interface" value="0" <?php echo $woocommerce_wpml->settings['trnsl_interface'] == '0'?'checked':''; ?> id="wcml_trsl_interface_native" />
                    <label for="wcml_trsl_interface_native"><?php _e('Go to the native WooCommerce product editing screen', 'wpml-wcml'); ?></label>
                </li>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="wcml_trsl_interface_table" value='<?php esc_attr(_e('Save', 'wpml-wcml')); ?>' class='button-secondary' />
            </p>
        </form>
    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('Products synchronization', 'wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Products synchronization', 'wpml-wcml') ?>" data-content="<?php _e('Configure specific product properties that should be synced to translations.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>
    
    <div class="wcml-section-content">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <?php wp_nonce_field('wcml_products_sync_prop', 'wcml_nonce'); ?>
            <ul>
                <li>
                    <input type="checkbox" name="products_sync_date" value="1" <?php echo checked(1, $woocommerce_wpml->settings['products_sync_date']) ?> id="wcml_products_sync_date" />
                    <label for="wcml_products_sync_date"><?php _e('Sync publishing date for translated products.', 'wpml-wcml'); ?></label>
                </li>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="wcml_products_sync_prop" value='<?php esc_attr(_e('Save', 'wpml-wcml')); ?>' class='button-secondary' />
            </p>
        </form>
    </div>

</div>


<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('File Paths Synchronization ', 'wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Synchronization for download paths', 'wpml-wcml') ?>" data-content="<?php _e('If you are using downloadable products, you can choose to have their paths synchronized, or seperate for each language.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <?php wp_nonce_field('wcml_file_path_options_table', 'wcml_nonce'); ?>
            <ul>
                <li>
                    <input type="radio" name="wcml_file_path_sync" value="1" <?php echo $woocommerce_wpml->settings['file_path_sync'] == '1'?'checked':''; ?> id="wcml_file_path_sync_auto" />
                    <label for="wcml_file_path_sync_auto"><?php _e('Use the same file paths in all languages', 'wpml-wcml'); ?></label>
                </li>
                <li>
                    <input type="radio" name="wcml_file_path_sync" value="0" <?php echo $woocommerce_wpml->settings['file_path_sync'] == '0'?'checked':''; ?> id="wcml_file_path_sync_self" />
                    <label for="wcml_file_path_sync_self"><?php _e('Different file paths for each language', 'wpml-wcml'); ?></label>
                </li>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="wcml_file_path_options_table" value='<?php esc_attr(_e('Save', 'wpml-wcml')); ?>' class='button-secondary' />
            </p>
        </form>

    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->


    <div class="wcml-section">

        <div class="wcml-section-header">
            <h3>
                <?php _e('Manage Currencies', 'wpml-wcml'); ?>
                <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Currency management', 'wpml-wcml') ?>" data-content="<?php _e('This will let you enable the multi-currency mode where users can see prices according to their currency preference and configured exchange rate.', 'wpml-wcml') ?>"></i>
            </h3>
        </div>

    <div class="wcml-section-content">

        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="general_options">
            <?php wp_nonce_field('general_options', 'wcml_nonce'); ?>
            
            <ul id="general_options">
            
                <li>
                    <ul id="multi_currency_option_select">
                        <li>
                            <input type="radio" name="multi_currency" id="multi_currency_disabled" value="<?php echo WCML_MULTI_CURRENCIES_DISABLED ?>" <?php 
                                echo checked($woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_DISABLED) ?> />
                            <label for="multi_currency_disabled"><?php _e("No multi-currency",'wpml-wcml'); ?></label>
                        </li>
                        <li>
                            <input type="radio" name="multi_currency" id="multi_currency_independent" value="<?php echo WCML_MULTI_CURRENCIES_INDEPENDENT ?>" <?php 
                                echo checked($woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_INDEPENDENT) ?> />
                            <label for="multi_currency_independent">                                
                                <?php _e("Multiple currencies, independent of languages",'wpml-wcml'); ?>                                
                                <strong>[<?php _e('BETA', 'wpl-wcml') ?>]</strong>&nbsp;
                                <a href="http://wpml.org/?p=290080"><?php _e('Learn more', 'wpl-wcml') ?></a>.
                            </label>  
                        </li>
                    </ul>
                </li>
            </ul>

        
            
        
            
        <div id="multi-currency-per-language-details" <?php if ( $woocommerce_wpml->settings['enable_multi_currency'] != WCML_MULTI_CURRENCIES_INDEPENDENT ):?>style="display:none"<?php endif;?>>
            <?php
            $wc_currencies = get_woocommerce_currencies();
            $wc_currency = get_option('woocommerce_currency');
            $active_languages = $sitepress->get_active_languages();
            ?>
            <p>
                <?php printf(__('Your store\'s base currency is %s (%s). To change it, go to the %s page.', 'wpml-wcml'),$wc_currencies[$wc_currency],get_woocommerce_currency_symbol($wc_currency),'<a href="'. admin_url('admin.php?page=woocommerce_settings&tab=general') .'">WooCommerce settings</a>'); ?>
            </p>
            <input type="hidden" id="update_currency_lang_nonce" value="<?php echo wp_create_nonce('wcml_update_currency_lang'); ?>"/>
            <table class="widefat currency_table" id="currency-table">
                <thead>
                    <tr>
                        <th><?php _e('Currency', 'wpml-wcml'); ?></th>                     
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` ASC", OBJECT);
                    $exists_codes = $wpdb->get_col("SELECT code FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` ASC"); ?>
                    <tr>
                        <td class="currency_code">
                            <span class="code_val"><?php echo $wc_currencies[$wc_currency]; ?><?php printf(__(' (%s)','wpml-wcml'),get_woocommerce_currency_symbol($wc_currency)); ?></span>
                            <select>
                                <option value="<?php echo $wc_currency; ?>" selected="selected"><?php echo $wc_currency; ?></option>
                            </select>
                            <div class="currency_value">
                            <span><?php _e( 'default', 'wpml-wcml' ); ?></span>
                            </div>
                        </td>

                        <td class="currency-actions"></td>
                    </tr>
                    <?php
                    unset($wc_currencies[$wc_currency]);                    
                    foreach ($currencies as $key => $currency) : ?>
                        <tr>                            
                            <td class="currency_code" data-message="<?php _e( 'Please fill field', 'wpml-wcml' ); ?>">                                
                                <span class="code_val"><?php echo $wc_currencies[$currency->code]; ?><?php printf(__(' (%s)','wpml-wcml'),get_woocommerce_currency_symbol($currency->code)); ?></span>                                
                                <input type="hidden" value="<?php echo $currency->id; ?>" class="currency_id" />
                                <select>
                                    <?php foreach($wc_currencies as $key=>$currency_name): ?>
                                        <?php if(!in_array($key,$exists_codes) || $currency->code==$key): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $currency->code==$key?'selected="selected"':''; ?>><?php echo $currency_name; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="currency_value" data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>">
                                    <span><?php printf('1 %s = ',$wc_currency); ?>
                                        <span class="curr_val"><?php echo $currency->value; ?></span>
                                <input type="text" value="<?php echo $currency->value; ?>"/>
                                        <span class="curr_val_code"><?php echo $currency->code; ?></span>
                                    </span>
                                </div>
                                <span class="currency_changed">(<?php echo date('d/m/Y',strtotime($currency->changed)); ?>)</span>
                            </td>
                            <td class="currency-actions">
                                <div class="currency_action_update">
                                    <a href="javascript:void(0);" title="<?php esc_attr(_e('Edit', 'wpml-wcml')); ?>" class="edit_currency">
                                        <i class="icon-edit" title="<?php esc_attr(_e('Edit', 'wpml-wcml')); ?>"></i>
                                    </a>
                                    <i class="icon-ok-circle save_currency"></i>
                                </div>
                                <div class="currency_action_delete">
                                    <a href="javascript:void(0);" title="<?php esc_attr(_e('Delete', 'wpml-wcml')); ?>" class="delete_currency">
                                        <i class="icon-trash" title="<?php esc_attr(_e('Delete', 'wpml-wcml')); ?>"></i>
                                    </a>
                                    <i class="icon-remove-circle cancel_currency"></i>
                                </div>
                            </td>
                        </tr>
                   <?php endforeach; ?>
                    <tr class="default_currency">
                        <td colspan="2">
                            <span class="cur_label"><?php _e('Default currency', 'wpml-wcml'); ?></span>
                            <span class="inf_message"><?php _e('Switch to this currency when switching language in the front-end', 'wpml-wcml'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="currency_wrap">
                <div class="currency_inner">
                    <table class="widefat currency_lang_table" id="currency-lang-table">
                        <thead>
                            <tr>
                                <?php foreach($active_languages as $language): ?>
                                    <th>
                                        <img src="<?php echo ICL_PLUGIN_URL ?>/res/flags/<?php echo $language['code'] ?>.png" width="18" height="12" />
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php foreach($active_languages as $language): ?>
                                    <td class="currency_languages">
                                        <div class="wcml_onof_buttons">
                                        <ul>
                                            <li <?php echo $woocommerce_wpml->settings['currencies_languages'][$wc_currency][$language['code']] == 0 ?'class="on"':''; ?> ><a class="off_btn" href="javascript:void(0);" rel="<?php echo $language['code']; ?>"><?php _e( 'OFF', 'wpml-wcml' ); ?></a></li>
                                            <li <?php echo $woocommerce_wpml->settings['currencies_languages'][$wc_currency][$language['code']] == 1 ?'class="on"':''; ?> ><a class="on_btn" href="javascript:void(0);" rel="<?php echo $language['code']; ?>"><?php _e( 'ON', 'wpml-wcml' ); ?></a></li>
                                        </ul>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php foreach ($currencies as $key => $currency) : ?>
                                <tr>
                                    <?php foreach($active_languages as $language): ?>
                                        <td class="currency_languages">
                                            <div class="wcml_onof_buttons">
                                            <ul>
                                                <li <?php echo $woocommerce_wpml->settings['currencies_languages'][$currency->code][$language['code']] == 0 ?'class="on"':''; ?> ><a class="off_btn" href="javascript:void(0);" rel="<?php echo $language['code']; ?>"><?php _e( 'OFF', 'wpml-wcml' ); ?></a></li>
                                                <li <?php echo $woocommerce_wpml->settings['currencies_languages'][$currency->code][$language['code']] == 1 ?'class="on"':''; ?> ><a class="on_btn" href="javascript:void(0);" rel="<?php echo $language['code']; ?>"><?php _e( 'ON', 'wpml-wcml' ); ?></a></li>
                                            </ul>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="default_currency">
                                <input type="hidden" id="wcml_update_default_currency_nonce" value="<?php echo wp_create_nonce('wcml_update_default_currency'); ?>"/>
                                <?php foreach($active_languages as $language): ?>
                                    <td class="currency_languages">
                                        <select rel="<?php echo $language['code']; ?>">
                                            <option value="0" <?php echo $woocommerce_wpml->settings['default_currencies'][$language['code']] == false ?'selected="selected"':''; ?>><?php _e('Keep', 'wpml-wcml'); ?></option>
                                            <?php if($woocommerce_wpml->settings['currencies_languages'][$wc_currency][$language['code']] == 1): ?>
                                                <option value="<?php echo $wc_currency; ?>" <?php echo $woocommerce_wpml->settings['default_currencies'][$language['code']] == $wc_currency ?'selected="selected"':''; ?>><?php echo $wc_currency; ?></option>
                                            <?php endif; ?>
                                            <?php foreach($exists_codes as $code): ?>
                                                <?php if($woocommerce_wpml->settings['currencies_languages'][$code][$language['code']] == 1): ?>
                                                    <option value="<?php echo $code; ?>" <?php echo $woocommerce_wpml->settings['default_currencies'][$language['code']] == $code ?'selected="selected"':''; ?>><?php echo $code; ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                </tbody>
            </table>
                </div>
            </div>

            <?php // this is a template for scripts.js : jQuery('.wcml_add_currency button').click(function(); ?>
            <table class="hidden js-table-row-wrapper">
                <tr class="edit-mode js-table-row">                    
                    <td class="currency_code" data-message="<?php _e( 'Please fill field', 'wpml-wcml' ); ?>">                        
                        <span class="code_val"></span>
                        <input type="hidden" value="" class="currency_id" />
                        <select style="display:block">
                            <?php foreach($wc_currencies as $key=>$currency_name): ?>
                                <?php if(!in_array($key,$exists_codes)): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $currency_name; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="currency_value" data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>">
                            <span>
                                <?php printf('1 %s = ',$wc_currency); ?>
                                <span class="curr_val"></span>
                                <input type="text" value="" style="display: inline-block;">
                                <span class="curr_val_code"></span>
                            </span>
                        </div>
                        <span class="currency_changed"></span>
                    </td>
                    <td class="currency-actions">
                        <div class="currency_action_update">
                            <a href="javascript:void(0);" title="Edit" class="edit_currency" style="display:none">
                                <i class="icon-edit" title="Edit"></i>
                            </a>
                            <i class="icon-ok-circle save_currency" style="display:inline"></i>
                        </div>
                        <div class="currency_action_delete">
                            <a href="javascript:void(0);" title="Delete" class="delete_currency" style="display:none">
                                <i class="icon-trash" alt="Delete"></i>
                            </a>
                            <i class="icon-remove-circle cancel_currency" style="display:inline"></i>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="hidden js-currency_lang_table">
                <tr>
                    <?php foreach($active_languages as $language): ?>
                        <td class="currency_languages">
                            <div class="wcml_onof_buttons">
                            <ul>
                                <li><a class="off_btn" href="javascript:void(0);" rel="<?php echo $language['code']; ?>"><?php _e( 'OFF', 'wpml-wcml' ); ?></a></li>
                                <li class="on"><a class="on_btn" href="javascript:void(0);" rel="<?php echo $language['code']; ?>"><?php _e( 'ON', 'wpml-wcml' ); ?></a></li>
                            </ul>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </table>

            <input type="hidden" value="<?php echo WCML_PLUGIN_URL; ?>" class="wcml_plugin_url" />
            <input type="hidden" id="upd_currency_nonce" value="<?php echo wp_create_nonce('wcml_update_currency'); ?>" />
            <input type="hidden" id="del_currency_nonce" value="<?php echo wp_create_nonce('wcml_delete_currency'); ?>" />            

            <p class="wcml_add_currency button-wrap">
                <button type="button" class="button-secondary">
                    <i class="icon-plus"></i>
                    <?php _e('Add currency','wpml-wcml'); ?>
                </button>
            </p>

            <?php // backward compatibility ?>
            <?php 
                $posts = $wpdb->get_results($wpdb->prepare("
                    SELECT m.post_id, m.meta_value, p.post_title 
                    FROM {$wpdb->postmeta} m 
                        JOIN {$wpdb->posts} p ON p.ID = m.post_id
                        JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                    WHERE m.meta_key='_custom_conversion_rate' AND t.language_code = %s
                    ORDER BY m.post_id desc
                ", $sitepress->get_default_language())); 
            
                if($posts){
                    echo "<script>
                    function wcml_remove_custom_rates(post_id, el){
                        jQuery.ajax({
                            type: 'post',
                            dataType: 'json',
                            url: ajaxurl,
                            data: {action: 'legacy_remove_custom_rates', 'post_id': post_id},
                            success: function(){
                                el.parent().parent().fadeOut(function(){ jQuery(this).remove()});
                            }
                        })
                        return false;
                    }";                    
                    echo '</script>';
                    echo '<p>' . __('Products using custom currency rates as they were migrated from the previous versions - option to support different prices per language', 'wpml-wcml') . '</p>';
                    echo '<form method="post" id="wcml_custom_exchange_rates">';
                    echo '<input type="hidden" name="action" value="legacy_update_custom_rates">';
                    echo '<table class="widefat currency_table" >';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th rowspan="2">' . __('Product', 'wpml-wcml') . '</th>';
                    echo '<th colspan="' . count($currencies) . '">_price</th>';
                    echo '<th colspan="' . count($currencies) . '">_sale_price</th>';
                    echo '<th rowspan="2">&nbsp;</th>';
                    echo '</tr>';
                    echo '<tr>';
                    foreach($currencies as $currency){
                        echo '<th>' . $currency->code . '</th>';
                    }
                    foreach($currencies as $currency){
                        echo '<th>' . $currency->code . '</th>';
                    }
                    echo '</tr>';                    
                    echo '</thead>';
                    echo '<tbody>';
                    foreach($posts as $post){
                        $rates = unserialize($post->meta_value);    
                        echo '<tr>';
                        echo '<td><a href="' . get_edit_post_link($post->post_id) . '">' . apply_filters('the_title', $post->post_title) . '</a></td>';
                        
                        foreach($currencies as $currency){
                            echo '<td>';
                            if(isset($rates['_price'][$currency->code])){
                                echo '<input name="posts[' . $post->post_id . '][_price][' . $currency->code . ']" size="3" value="' . round($rates['_price'][$currency->code],3) . '">';
                            }else{
                                _e('n/a', 'wpml-wcml');
                            }
                            echo '</td>';
                        }
                        
                        foreach($currencies as $currency){
                            echo '<td>';
                            if(isset($rates['_sale_price'][$currency->code])){
                                echo '<input name="posts[' . $post->post_id . '][_sale_price][' . $currency->code . ']" size="3" value="' . round($rates['_sale_price'][$currency->code],3) . '">';
                            }else{
                                _e('n/a', 'wpml-wcml');
                            }
                            echo '</td>';
                        }
                                                
                        echo '<td align="right"><a href="#" onclick=" if(confirm(\'' . esc_js(__('Are you sure?', 'wpml-wcml') ) . '\')) wcml_remove_custom_rates(' . $post->post_id . ', jQuery(this));return false;"><i class="icon-trash" title="' . __('Delete', 'wpml-wcml') . '"></i></a></td>';
                        echo '<tr>';
                        
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '<p class="button-wrap"><input class="button-secondary" type="submit" value="' . esc_attr__('Update', 'wpml-wcml') . '" /></p>';
                    echo '</form>';
                    
                    
                }
            ?>
            
            
            
            
        </div>
        <p class="button-wrap">
            <input type='submit' name="general_options" value='<?php _e('Save', 'wpml-wcml'); ?>' class='button-secondary' />
            <?php wp_nonce_field('general_options', 'general_options_nonce'); ?>
        </p>


        </form>

    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->
<input type="hidden" id="wcml_warn_message" value="<?php esc_attr_e('The changes you made will be lost if you navigate away from this page.','wpml-wcml');?>"/>
<div class="troubleshoot_link_block">
    <a href="admin.php?page=<?php echo basename(WCML_PLUGIN_PATH) ?>/menu/sub/troubleshooting.php"><?php  _e('Troubleshooting page','wpml-wcml'); ?></a>
</div>
<div class="clear"></div>
