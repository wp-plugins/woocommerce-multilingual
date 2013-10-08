<div class="wcml-section">
    <div class="wcml-section-header">
        <h3>
            <?php _e('Plugins Status','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Check required plugins', 'wpml-wcml') ?>" data-content="<?php _e('WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>
    <div class="wcml-section-content">
        <ul>         
            <?php if (defined('ICL_SITEPRESS_VERSION') && version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')) : ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('WooCommerce Multilingual is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'wpml-wcml'), 'http://wpml.org/'); ?> <a href="http://wpml.org/shop/account/" target="_blank"><?php _e('Update WPML', 'wpml-wcml'); ?></a></li>
            <?php elseif (defined('ICL_SITEPRESS_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML</strong>'); ?></li>
            <?php else : ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.', 'wpml-wcml'), '<strong>WPML</strong>'); ?> <a href="http://wpml.org" target="_blank"><?php _e('Get WPML', 'wpml-wcml'); ?></a></li>
            <?php endif; ?>
            <?php if (defined('WPML_MEDIA_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML Media</strong>'); ?></li>
            <?php else : ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.', 'wpml-wcml'), '<strong>WPML Media</strong>'); ?> <a href="http://wpml.org" target="_blank"><?php _e('Get WPML Media', 'wpml-wcml'); ?></a></li>
            <?php endif; ?>
            <?php if (defined('WPML_TM_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML Translation Management</strong>'); ?></li>
            <?php else : ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.', 'wpml-wcml'), '<strong>WPML Translation Management</strong>'); ?> <a href="http://wpml.org" target="_blank"><?php _e('Get WPML Translation Management', 'wpml-wcml'); ?></a></li>
            <?php endif; ?>
            <?php if (defined('WPML_ST_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WPML String Translation</strong>'); ?></li>
            <?php else : ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.', 'wpml-wcml'), '<strong>WPML String Translation</strong>'); ?> <a href="http://wpml.org" target="_blank"><?php _e('Get WPML String Translation', 'wpml-wcml'); ?></a></li>
            <?php endif; ?>
            <?php
            global $woocommerce;
            if (class_exists('Woocommerce') && $woocommerce && isset($woocommerce->version) && version_compare($woocommerce->version, '2.0', '<')) :
                ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('%1$s  is installed, but with incorrect version. You need %1$s %2$s or higher. ', 'wpml-wcml'), '<strong>WooCommerce</strong>', '2.0'); ?> <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank"><?php _e('Download WooCommerce', 'wpml-wcml'); ?></a></li>
            <?php elseif (class_exists('Woocommerce')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and activated.', 'wpml-wcml'), '<strong>WooCommerce</strong>'); ?></li>
            <?php else : ?>
                <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not activated.', 'wpml-wcml'), '<strong>WooCommerce</strong>'); ?> <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank"><?php _e('Download WooCommerce', 'wpml-wcml'); ?></a></li>
        <?php endif; ?>
        </ul>
    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('WooCommerce Store Pages','wpml-wcml'); ?>
            <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Translated store pages', 'wpml-wcml') ?>" data-content="<?php _e('To run a multilingual e-commerce site, you need to have several WooCommerce shop pages in all the site\'s language. If they are missing, we will create them automatically for you.', 'wpml-wcml') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">

        <?php $miss_lang = $woocommerce_wpml->store->get_missing_store_pages(); ?>
        <?php if($miss_lang == 'non_exist'): ?>
            <ul>
                <li>
                    <i class="icon-warning-sign"></i><span><?php _e("One or more WooCommerce pages have not been created",'wpml-wcml'); ?></span>
                </li>
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
                        <input class="button" type="submit" name="create_pages" value="<?php esc_attr(_e('Create missing store pages', 'wpml-wcml')) ?>" />
                        <a id="wcmp_hide" class="wcmp_lang_hide" href="javascript:void(0);"><?php _e('Hide this message', 'wpml-wcml') ?></a>
                    </p>
                </div>
                <p>
                    <a id="wcmp_show" class="none" href="javascript:void(0);"><?php _e('Show missing languages', 'wpml-wcml') ?></a>
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
        <?php $taxonomies = array_unique(get_object_taxonomies('product') + get_object_taxonomies('product_variation')); ?>
        
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
                <i class="icon-question-sign js-display-tooltip" data-header="<?php _e('Currency management', 'wpml-wcml') ?>" data-content="<?php _e('You can use different currencies and amounts for products in different languages. Once enabled, add currencies and choose a currency for each language.', 'wpml-wcml') ?>"></i>
            </h3>
        </div>

    <div class="wcml-section-content">

        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="general_options">
            <?php wp_nonce_field('general_options', 'wcml_nonce'); ?>
            <ul id="general_options">
                <p>
                    <input type="checkbox" name="multi_currency" id="multi_currency" <?php echo $woocommerce_wpml->settings['enable_multi_currency'] == 'yes'?'checked':''; ?> />
                    <label for="multi_currency"><?php _e("Enable multi-currency",'wpml-wcml'); ?></label>
                </p>
                <?php if($woocommerce_wpml->settings['enable_multi_currency'] == 'yes'): ?>
                <li>
                    <input type="radio" name="currency_converting_option[]" id="currency_converting_option" value="1" <?php echo $woocommerce_wpml->settings['currency_converting_option'] == '1'?'checked':''; ?>>
                    <label for="currency_converting_option"><?php _e('Automatically calculate pricing in different currencies, based on the exchange rate', 'wpml-wcml'); ?></label>
                </li>
                <li>
                    <input type="radio" name="currency_converting_option[]" id="currency_converting_option_2" value="2" <?php echo $woocommerce_wpml->settings['currency_converting_option'] == '2'?'checked':''; ?>>
                    <label for="currency_converting_option_2"><?php _e('I will manage the pricing in each currency myself', 'wpml-wcml'); ?></label>
                </li>
                <?php endif; ?>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="general_options" value='<?php _e('Save', 'wpml-wcml'); ?>' class='button-secondary' />
                <?php wp_nonce_field('general_options', 'general_options_nonce'); ?>
            </p>
        </form>

        <?php if ( $woocommerce_wpml->settings['enable_multi_currency'] == 'yes' ): ?>

            <?php $auto_currency = $woocommerce_wpml->settings['currency_converting_option']; ?>

            <p>
                <?php printf(__('Your store\'s base currency is %s. To change it, go to the %s page.', 'wpml-wcml'),get_option('woocommerce_currency'),'<a href="'. admin_url('admin.php?page=woocommerce_settings&tab=general') .'">WooCommerce settings</a>'); ?>
            </p>

            <table class="widefat currency_table" id="currency-table">
                <thead>
                    <tr>
                        <th><?php _e('Currency code', 'wpml-wcml'); ?></th>
                        <th><?php printf(__('Exchange rate to %s', 'wpml-wcml'),get_option('woocommerce_currency').($auto_currency==2?' (*)':'')); ?></th>
                        <th><?php _e('Changed', 'wpml-wcml'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php _e('Currency code', 'wpml-wcml'); ?></th>
                        <th><?php printf(__('Exchange rate to %s', 'wpml-wcml'),get_option('woocommerce_currency').($auto_currency==2?' (*)':'')); ?></th>
                        <th><?php _e('Changed', 'wpml-wcml'); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC", OBJECT);

                    foreach ($currencies as $key => $currency) : ?>
                        <tr>
                            <input type="hidden" value="<?php echo $currency->id; ?>" class="currency_id" />
                            <td class="currency_code" data-message="<?php _e( 'Please fill field', 'wpml-wcml' ); ?>">
                                <span><?php echo $currency->code; ?></span>
                                <input type="text" value="<?php echo $currency->code; ?>"/>
                            </td>
                            <td class="currency_value" data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>">
                                <span><?php echo $currency->value; ?></span>
                                <input type="text" value="<?php echo $currency->value; ?>"/>
                            </td>
                            <td class="currency_changed">
                                <span><?php echo date('d/m/Y',strtotime($currency->changed)); ?></span>
                            </td>
                            <td class="currency-actions">
                                <div class="currency_action_update">
                                    <a href="javascript:void(0);" title="<?php esc_attr(_e('Edit', 'wpml-wcml')); ?>" class="edit_currency">
                                        <i class="icon-edit" title="<?php esc_attr(_e('Edit', 'wpml-wcml')); ?>"></i>
                                    </a>
                                    <button type="button" class="button-secondary cancel_currency"><?php _e('Cancel','wpml-wcml'); ?></button>
                                </div>
                                <div class="currency_action_delete">
                                    <a href="javascript:void(0);" title="<?php esc_attr(_e('Delete', 'wpml-wcml')); ?>" class="delete_currency">
                                        <i class="icon-trash" title="<?php esc_attr(_e('Delete', 'wpml-wcml')); ?>"></i>
                                    </a>
                                   <button type="button" class="button-secondary save_currency"><?php _e('Save','wpml-wcml'); ?></button>
                                </div>
                            </td>
                        </tr>
                   <?php endforeach; ?>

                </tbody>
            </table>

            <?php // this is a template for scripts.js : jQuery('.wcml_add_currency button').click(function(); ?>
            <table class="hidden js-table-row-wrapper">
                <tr class="edit-mode js-table-row">
                    <input type="hidden" value="" class="currency_id" />
                    <td class="currency_code" data-message="<?php _e( 'Please fill field', 'wpml-wcml' ); ?>">
                        <span></span>
                        <input type="text" value="" style="display:block">
                    </td>
                    <td class="currency_value" data-message="<?php _e( 'Only numeric', 'wpml-wcml' ); ?>">
                        <span></span>
                        <input type="text" value="" style="display:block">
                    </td>
                    <td class="currency_changed">
                        <span></span>
                    </td>
                    <td class="currency-actions">
                        <div class="currency_action_update">
                            <a href="javascript:void(0);" title="Edit" class="edit_currency" style="display:none">
                                <i class="icon-edit" title="Edit"></i>
                            </a>
                            <button type="button" class="button-secondary cancel_currency" style="display:inline-block">Cancel</button>
                        </div>
                        <div class="currency_action_delete">
                            <a href="javascript:void(0);" title="Delete" class="delete_currency" style="display:none">
                                <i class="icon-trash" alt="Delete"></i>
                            </a>
                            <button type="button" class="button-secondary save_currency" style="display:inline-block">Save</button>
                        </div>
                    </td>
                </tr>
            </table>

            <input type="hidden" value="<?php echo WCML_PLUGIN_URL; ?>" class="wcml_plugin_url" />
            <input type="hidden" id="upd_currency_nonce" value="<?php echo wp_create_nonce('wcml_update_currency'); ?>" />
            <input type="hidden" id="del_currency_nonce" value="<?php echo wp_create_nonce('wcml_delete_currency'); ?>" />            
            <?php if($auto_currency ==2): ?>
                <p><?php _e('(*) This exchange rate is not applied automatically because you chose to set pricing manually in different currencies.','wpml-wcml'); ?></p>
            <?php endif; ?>

            <p class="wcml_add_currency button-wrap">
                <button type="button" class="button-secondary">
                    <i class="icon-plus"></i>
                    <?php _e('Add currency','wpml-wcml'); ?>
                </button>
            </p>

            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="languages_currencies_table">
                <?php wp_nonce_field('wcml_update_languages_currencies', 'wcml_nonce'); ?>
                <table class="widefat wcml_languages_currency">
                    <thead>
                        <tr>
                           <th><?php _e('Language', 'wpml-wcml'); ?></th>
                           <th><?php _e('Currency', 'wpml-wcml'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $woocommerce_wpml->currencies->echo_languages_currencies() ?>
                    </tbody>
                </table>
                <p class="button-wrap">
                    <input type="submit" name="wcml_update_languages_currencies" class="button-secondary" value="<?php _e('Update','wpml-wcml'); ?>" />
                </p>
            </form>

        <?php endif; ?>

    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="troubleshoot_link_block">
    <a href="admin.php?page=<?php echo basename(WCML_PLUGIN_PATH) ?>/menu/sub/troubleshooting.php"><?php  _e('Troubleshooting page','wpml-wcml'); ?></a>
</div>
<div class="clear"></div>
