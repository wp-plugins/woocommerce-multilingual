<?php
$pn = isset($_GET['pn'])?$_GET['pn']:1;
$lm = isset($_GET['lm'])?$_GET['lm']:20;

$search = false;
$pagination_url = 'admin.php?page=wpml-wcml&tab=products&pn=';
if(isset($_GET['s']) && isset($_GET['cat']) && isset($_GET['trst']) && isset($_GET['st']) && isset($_GET['slang'])){
    $products_data = $this->products->get_products_from_filter($_GET['s'],$_GET['cat'],$_GET['trst'],$_GET['st'],$_GET['slang'],$pn,$lm);
    $products = $products_data['products'];
    $products_count = $products_data['count'];
    $search = true;
    $pagination_url = 'admin.php?page=wpml-wcml&tab=products&s='.$_GET['s'].'&cat='.$_GET['cat'].'&trst='.$_GET['trst'].'&st='.$_GET['st'].'&slang='.$_GET['slang'].'&pn=';
}

if(isset($_GET['prid'])){
    $products[] = get_post($_GET['prid']);
    $products_count = 1;
    $pr_edit = true;
}

$current_language = $sitepress->get_current_language();

if(!isset($products)){
    $products = $woocommerce_wpml->products->get_product_list($pn, $lm);
    $products_count = $woocommerce_wpml->products->get_products_count();
}

if($lm){
    $last  = $woocommerce_wpml->products->get_product_last_page($products_count,$lm);
}

$button_labels = array(
    'save'      => esc_attr__('Save', 'wpml-wcml'),
    'update'    => esc_attr__('Update', 'wpml-wcml'),
);
?>
<h3><?php _e('WooCommerce Products','wpml-wcml'); ?></h3>
<span style="display:none" id="wcml_product_update_button_label"><?php echo $button_labels['update'] ?></span>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <div class="wcml_prod_filters">
        <select class="wcml_product_category">
            <option value="0"><?php _e('Any category', 'wpml-wcml'); ?></option>
            <?php
            $product_categories = get_terms('product_cat',array('hide_empty' => 0));
            foreach ($product_categories as $category) {
                $selected = (isset($_GET['cat']) && $_GET['cat'] == $category->term_taxonomy_id)?'selected="selected"':'';
                echo '<option value="'.$category->term_taxonomy_id.'" '.$selected.'>'.$category->name.'</option>';
            }
            ?>
        </select>
        <select class="wcml_translation_status">
            <option value="all"><?php _e('All products', 'wpml-wcml'); ?></option>
            <option value="not" <?php echo (isset($_GET['trst']) && $_GET['trst']=='not')?'selected="selected"':''; ?>><?php _e('Not translated or needs updating', 'wpml-wcml'); ?></option>
            <option value="need_update" <?php echo (isset($_GET['trst']) && $_GET['trst']=='need_update')?'selected="selected"':''; ?>><?php _e('Needs updating', 'wpml-wcml'); ?></option>
            <option value="in_progress" <?php echo (isset($_GET['trst']) && $_GET['trst']=='in_progress')?'selected="selected"':''; ?>><?php _e('Translation in progress', 'wpml-wcml'); ?></option>
            <option value="complete" <?php echo (isset($_GET['trst']) && $_GET['trst']=='complete')?'selected="selected"':''; ?>><?php _e('Translation complete', 'wpml-wcml'); ?></option>
        </select>

        <?php
        $all_statuses = get_post_stati();
        //unset unnecessary statuses
        unset($all_statuses['trash']);
        unset($all_statuses['auto-draft']);
        unset($all_statuses['inherit']);
        ?>
        <select class="wcml_product_status">
            <option value="all"><?php _e('All statuses', 'wpml-wcml'); ?></option>
            <?php foreach($all_statuses as $key=>$status): ?>
                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['st']) && $_GET['st']==$key)?'selected="selected"':''; ?> ><?php echo ucfirst($status); ?></option>
            <?php endforeach; ?>
        </select>

        <select class="wcml_translation_status_lang">
            <option value="all"><?php _e('All languages', 'wpml-wcml') ?></option>
            <?php foreach($active_languages as $lang): ?>
                <?php if($default_language != $lang['code']): ?>
                    <option value="<?php echo $lang['code'] ?>" <?php echo (isset($_GET['slang']) && $_GET['slang']==$lang['code'])?'selected="selected"':''; ?> ><?php echo $lang['display_name'] ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        </div>
        <div>
        <input type="text" class="wcml_product_name" placeholder="<?php _e('Search', 'wpml-wcml'); ?>" value="<?php echo isset($_GET['s'])?$_GET['s']:''; ?>"/>
        <input type="hidden" value="<?php echo admin_url('admin.php?page=wpml-wcml&tab=products'); ?>" class="wcml_products_admin_url" />
        <input type="hidden" value="<?php echo $pagination_url; ?>" class="wcml_pagination_url" />

        <button type="button" value="search" class="wcml_search button-secondary"><?php _e('Search', 'wpml-wcml'); ?></button>
        <?php if($search): ?>
            <button type="button" value="reset" class="button-secondary wcml_reset_search"><?php _e('Reset', 'wpml-wcml'); ?></button>
        <?php endif;?>
        </div>

            <?php if($products): ?>
                <div class="wcml_product_pagination">
                <span class="displaying-num"><?php printf(__('%d products', 'wpml-wcml'), $products_count); ?></span>
                <?php if(!isset($_GET['prid']) && isset($last) && $last > 1): ?>
                    <a class="first-page <?php echo $pn==1?'disabled':''; ?>" href="<?php echo $pagination_url; ?>1" title="<?php _e('Go to the first page', 'wpml-wcml'); ?>">&laquo;</a>
                    <a class="prev-page <?php echo $pn==1?'disabled':''; ?>" href="<?php echo $pagination_url.((int)$pn > 1?$pn - 1:$pn); ?>" title="<?php _e('Go to the previous page', 'wpml-wcml'); ?>">&lsaquo;</a>
                    <input type="text" class="current-page wcml_pagin" value="<?php echo $pn;?>" size="1"/>&nbsp;<span><?php _e('of', 'wpml-wcml'); ?>&nbsp;<?php echo $last; ?><span>
                    <a class="next-page <?php echo $pn==$last?'disabled':''; ?>" href="<?php echo $pagination_url.((int)$pn<$last?$pn + 1:$last); ?>" title="<?php _e('Go to the next page', 'wpml-wcml'); ?>">&rsaquo;</a>
                    <a class="last-page <?php echo $pn==$last?'disabled':''; ?>" href="<?php echo $pagination_url.$last; ?>" title="<?php _e('Go to the last page', 'wpml-wcml'); ?>">&raquo;</a>
                <?php endif; ?>
                <?php if(isset($_GET['prid']) || ($lm && isset($last)) && $last > 1): ?>
                    <a href="<?php echo $pagination_url; ?>1"><?php _e('Show all products', 'wpml-wcml'); ?></a>
                <?php endif; ?>
                </div>
            <?php endif; ?>
            <input type="hidden" class="icl_def_language" value="<?php echo $default_language ?>" />
            <input type="hidden" id="upd_product_nonce" value="<?php echo wp_create_nonce('update_product_actions'); ?>" />            
            <table class="widefat fixed wcml_products" cellspacing="0">
                <thead>
                    <tr>
                        <th scope="col" width="15"><input type="checkbox" value="" class="wcml_check_all"/></th>
                        <th scope="col" width="5%"><?php _e('Type', 'wpml-wcml') ?></th>
                        <th scope="col" width="20%"><?php _e('Product', 'wpml-wcml') ?></th>
                        <th scope="col" width="73%"><?php echo $woocommerce_wpml->products->get_translation_flags($active_languages,$default_language,isset($_GET['slang']) && $_GET['slang'] != "all"?$_GET['slang']:false); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $lang_codes = array();
                    foreach ($active_languages as $language) {
                        if($default_language == $language['code'] || current_user_can('wpml_manage_woocommerce_multilingual') || (wpml_check_user_is_translator($default_language,$language['code']) && !current_user_can('wpml_manage_woocommerce_multilingual')) ){
                            if(!isset($_GET['slang']) || (isset($_GET['slang']) && ($_GET['slang'] == $language['code'] || $default_language == $language['code'] || $_GET['slang'] == 'all')))
                                    $lang_codes[$language['code']] = $language['display_name'];
                            }
                        }
                    $default_language_display_name = $lang_codes[$default_language];
                    unset($lang_codes[$default_language]);
                    $lang_codes = array($default_language => $default_language_display_name)+$lang_codes;
                    ?>
                    <?php if(empty($products)): ?>
                        <tr><td colspan="4"><h3 class="wcml_no_found_text"><?php _e('No products found','wpml-wcml'); ?></h3></td></tr>
                    <?php else: ?>
                    <?php foreach ($products as $product) :
                        $product_id = icl_object_id($product->ID,'product',true,$default_language);
                        $product_images = $woocommerce_wpml->products->product_images_ids($product->ID);
                        $product_contents = $woocommerce_wpml->products->get_product_contents($product_id);
                        $trid = $sitepress->get_element_trid($product_id,'post_'.$product->post_type);
                        $product_translations = $sitepress->get_element_translations($trid,'post_'.$product->post_type,true,true);
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="product[]" value="<?php echo $product_id ?>" />
                            </td>
                            <td>
                                <?php
                                    $prod = get_product( $product->ID );
                                    $icon_class_sufix = $prod->product_type;
                                    if ( $prod -> is_virtual() ) {
                                        $icon_class_sufix = 'virtual';
                                    }
                                    else if ( $prod -> is_downloadable() ) {
                                        $icon_class_sufix = 'downloadable';
                                    }
                                ?>
                                <i class="icon-woo-<?php echo $icon_class_sufix; ?><?php echo ($product->post_parent != 0 && !$search) ? ' children_icon' : ''; ?>" title="<?php echo $icon_class_sufix; ?>"></i>
                            </td>
                            <td>
                                <?php echo $product->post_parent != 0 ? '&#8212; ' : ''; ?>
                                <?php echo $product->post_title;
                                if($product->post_status == 'draft' && ((isset($_GET['st']) && $_GET['st'] != 'draft') || !isset($_GET['st']))): ?>
                                    <span class="wcml_product_status_text"><?php _e(' (draft)','wpml-wcml'); ?></span>
                                <?php endif; ?>
                                <?php if ($search && $product->post_parent != 0): ?>
                                    <span class="prod_parent_text"><?php printf(__(' | Parent product: %s','wpml-wcml'),get_the_title($product->post_parent)); ?><span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="translations_statuses prid_<?php echo $product->ID; ?>">
                                    <?php echo $woocommerce_wpml->products->get_translation_statuses($product_translations,$active_languages,$default_language,isset($_GET['slang']) && $_GET['slang'] != "all" ?$_GET['slang']:false); ?>
                                </div>
                                <a href="#prid_<?php echo $product->ID; ?>" id="wcml_details_<?php echo $product->ID; ?>" class="wcml_details" data-text-opened="<?php _e('Close', 'wpml-wcml') ?>" data-text-closed="<?php _e('Edit translation', 'wpml-wcml') ?>"><?php _e('Edit translation', 'wpml-wcml') ?></a>
                            </td>
                        </tr>
                        <tr class="outer" data-prid="<?php echo $product->ID; ?>">
                            <td colspan="4">
                                <div class="wcml_product_row" id="prid_<?php echo $product->ID; ?>" <?php echo isset($pr_edit) ? 'style="display:block;"':''; ?>>
                                    <div class="inner">
                                        <table class="fixed wcml_products_translation">
                                            <thead>
                                                <tr>
                                                    <th scope="col"><?php _e('Language', 'wpml-wcml') ?></th>
                                                    <?php $product_contents_labels = $woocommerce_wpml->products->get_product_contents_labels($product_id); ?>
                                                    <?php foreach ($product_contents_labels as $product_content) : ?>
                                                        <th scope="col"><?php echo $product_content; ?></th>
                                                    <?php endforeach; ?>
                                                    <?php
                                                    $attributes = $woocommerce_wpml->products->get_product_atributes($product_id);
                                                    foreach($attributes as $key=>$attribute): ?>
                                                        <?php if(!$attribute['is_taxonomy']): ?>
                                                            <th scope="col"><?php echo  $attribute['name']; ?></th>
                                                        <?php else: ?>
                                                            <?php unset($attributes[$key]); ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <?php 
                                                    do_action('wcml_extra_titles',$product_id);
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lang_codes as $key=>$lang) : ?>
                                                    <?php if($key != $default_language && isset($product_translations[$key])
                                                        && get_post_meta($product_translations[$key]->element_id, '_icl_lang_duplicate_of', true) == $product->ID):
                                                        $is_duplicate_product = true; ?>
                                                    <tr class="wcml_duplicate_product_notice">
                                                        <td>&nbsp;</td>
                                                        <td colspan="<?php echo count($product_contents); ?>">
                                                            <span class="js-wcml_duplicate_product_notice_<?php echo $key ?>" >
                                                                <?php printf(__('This product is an exact duplicate of the %s product.', 'wcml-wpml'),
                                                                    $lang_codes[$sitepress->get_default_language()]); ?>&nbsp;
                                                                <a href="#edit-<?php echo $product_id ?>_<?php echo $key ?>"><?php _e('Edit independently.', 'wpml-wcml') ?></a>
                                                            </span>
                                                            <span class="js-wcml_duplicate_product_undo_<?php echo $key ?>" style="display: none;" >
                                                                <a href="#undo-<?php echo $product_id ?>_<?php echo $key ?>"><?php _e('Undo (keep this product as a duplicate)', 'wpml-wcml') ?></a>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php else: $is_duplicate_product = false; ?>
                                                    <?php endif; ?>
                                                    <tr rel="<?php echo $key; ?>">
                                                        <td>
                                                            <?php echo $lang; ?>
                                                            <?php if($default_language == $key){ ?>
                                                                <a class="edit-translation-link" title="<?php __("edit product", "wpml-wcml") ?>" href="<?php echo get_edit_post_link($product_id); ?>"><i class="icon-edit"></i></a>
                                                            <?php }else{ ?>
                                                                <input type="hidden" class="icl_language" value="<?php echo $key ?>" />
                                                                <input type="hidden" name="end_duplication[<?php echo $product_id ?>][<?php echo $key ?>]" value="<?php echo !intval($is_duplicate_product) ?>" />
                                                                <?php $button_label = isset($product_translations[$key]) ? $button_labels['update'] : $button_labels['save'] ;?>
                                                                <input type="submit" name="product#<?php echo $product_id ?>#<?php echo $key ?>" disabled value="<?php echo $button_label ?>" class="button-secondary wcml_update">
                                                                <span class="wcml_spinner spinner"></span>
                                                            <?php } ?>
                                                        </td>
                                                        <?php
                                                        if(isset($product_translations[$key])){
                                                            $tr_status = $wpdb->get_row($wpdb->prepare("SELECT status,translator_id FROM ". $wpdb->prefix ."icl_translation_status WHERE translation_id = %d",$product_translations[$key]->translation_id));
                                                            if(!is_null($tr_status) && get_current_user_id() != $tr_status->translator_id){
                                                                if($tr_status->status == ICL_TM_IN_PROGRESS){ ?>
                                                                        <td><?php _e('Translation in progress', 'wpml-wcml'); ?><br>&nbsp;</td>
                                                                <?php continue;
                                                                }elseif($tr_status->status == ICL_TM_WAITING_FOR_TRANSLATOR){ ?>
                                                                        <td><?php _e('Waiting for translator', 'wpml-wcml'); ?><br>&nbsp;</td>
                                                                <?php continue;
                                                                }
                                                            }
                                                        }
                                                        
                                                        foreach ($product_contents as $product_content) : ?>
                                                            <td>
                                                                <?php

                                                                $trn_contents  = $woocommerce_wpml->products->get_product_content_translation($product_id,$product_content,$key);

                                                                $missing_translation = false;
                                                                if($default_language == $key){
                                                                    $product_fields_values[$product_content] = $trn_contents;
                                                                }else{
                                                                    if(isset($product_fields_values[$product_content]) &&
                                                                        !empty($product_fields_values[$product_content]) &&
                                                                        empty($trn_contents)
                                                                        ){
                                                                            $missing_translation = true;
                                                                        }
                                                                }

                                                                if(!$woocommerce_wpml->products->check_custom_field_is_single_value($product_id,$product_content)){
                                                                    echo $woocommerce_wpml->products->custom_box($product_id,$product_content,$trn_contents,$key,$lang,$is_duplicate_product);
                                                                }else if(is_array($trn_contents)): ?>
                                                                     <?php if(in_array($product_content, array('_file_paths'))): ?>
                                                                        <?php
                                                                        $file_paths = '';
                                                                            foreach($trn_contents as $trn_content){
                                                                                $file_paths = $file_paths ? $file_paths . "\n" .$trn_content : $trn_content;
                                                                            } ?>
                                                                            <?php if($default_language == $key): ?>
                                                                                <textarea value="<?php echo $file_paths; ?>" disabled="disabled"><?php echo $file_paths; ?></textarea>
                                                                            <?php else: ?>
                                                                                <textarea value="<?php echo $file_paths; ?>" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php echo $file_paths; ?></textarea>
                                                                                <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                                                        <?php endif;?>
                                                                    <?php else: ?>
                                                                         <?php foreach ($trn_contents as $tax_key=>$trn_content) : ?>
                                                                            <?php if($default_language == $key): ?>
                                                                                <textarea rows="1" disabled="disabled"><?php echo $trn_content; ?></textarea>
                                                                            <?php else: ?>
                                                                                <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $product_content.'_'.$key.'['.$tax_key.']'; ?>" value="<?php echo $trn_content ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> /><br>
                                                                            <?php endif;?>
                                                                         <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                <?php elseif(in_array($product_content,array('content','excerpt'))): ?>
                                                                    <?php if($default_language == $key): ?>
                                                                        <button type="button" class="button-secondary wcml_edit_conten"><?php _e('Show content', 'wpml-wcml') ?></button>
                                                                    <?php else: ?>
                                                                        <button type="button" class="button-secondary wcml_edit_conten<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Edit translation', 'wpml-wcml') ?></button>
                                                                        <?php if($missing_translation): ?>
                                                                            <span class="wcml_field_translation_<?php echo $product_content ?>_<?php echo $key ?>">
                                                                            <p class="missing-translation">
                                                                                <i class="icon-warning-sign"></i>
                                                                                <?php _e('Translation missing', 'wpml-wcml'); ?>
                                                                            </p>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    <?php endif;?>
                                                                    <div class="wcml_editor">
                                                                        <a class="media-modal-close wcml_close_cross" href="javascript:void(0);" title="<?php esc_attr_e('Close', 'wpml-wcml') ?>"><span class="media-modal-icon"></span></a>
                                                                        <div class="wcml_editor_original">
                                                                            <h3><?php _e('Original content:', 'wpml-wcml') ?></h3>
                                                                            <?php
                                                                            if($product_content == 'content'){
                                                                                $original_content = apply_filters('the_content', $product->post_content);
                                                                            }else{
                                                                                $original_content = apply_filters('the_content', $product->post_excerpt);
                                                                            }
                                                                            ?>
                                                                            <textarea class="wcml_original_content"><?php echo $original_content; ?></textarea>

                                                                        </div>
                                                                        <div class="wcml_line"></div>
                                                                        <div class="wcml_editor_translation">
                                                                            <?php if($default_language != $key): ?>                                                                               
                                                                                <?php
                                                                                $tr_id = icl_object_id($product_id, 'product', true, $key);
                                                                                wp_editor($trn_contents, 'wcmleditor'.$product_content.$tr_id.$key, array('textarea_name'=>$product_content .
                                                                                    '_'.$key,'textarea_rows'=>20,'editor_class'=>'wcml_content_tr')); ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="wcml_editor_buttons">
                                                                        <?php if($default_language == $key): ?>
                                                                            <button type="button" class="button-secondary wcml_popup_close"><?php _e('Close', 'wpml-wcml') ?></button>
                                                                        <?php else: ?>
                                                                                <h3><?php printf(__('%s translation', 'wpml-wcml'),$lang); ?></h3>
                                                                            <button type="button" class="button-secondary wcml_popup_cancel"><?php _e('Cancel', 'wpml-wcml') ?></button>
                                                                            <button type="button" class="button-secondary wcml_popup_ok"><?php _e('Ok', 'wpml-wcml') ?></button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    </div>
                                                                <?php elseif(in_array($product_content,array('images'))):
                                                                    echo $woocommerce_wpml->products->product_images_box($product_id,$key,$is_duplicate_product); ?>
                                                                <?php elseif(in_array($product_content,array('variations'))):
                                                                    echo $woocommerce_wpml->products->product_variations_box($product_id,$key,$is_duplicate_product); ?>
                                                                <?php elseif($product_content == '_file_paths'): ?>
                                                                    <textarea placeholder="<?php esc_attr_e('Upload file', 'wpml-wcml') ?>" value="" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>></textarea>
                                                                    <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                                                 <?php else: ?>
                                                                    <?php if($default_language == $key): ?>
                                                                        <textarea rows="1" disabled="disabled"><?php echo $trn_contents; ?></textarea><br>                                                                   
                                                                    <?php elseif(in_array($product_content,array('_purchase_note'))): ?>
                                                                        <textarea class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" name="<?php echo $product_content.'_'.$key; ?>" rows="2" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> ><?php echo $trn_contents; ?></textarea>
                                                                    <?php else: ?>
                                                                        <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $product_content.'_'.$key; ?>" value="<?php echo $trn_contents; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> /><br>
                                                                    <?php endif;?>
                                                                <?php endif; ?>
                                                        </td>
                                                        <?php endforeach; ?>
                                                        <?php do_action('wcml_gui_additional_box',$product_id,$key,$is_duplicate_product); ?>
                                                        <?php
                                                        foreach ($attributes as $attr_key=>$attribute):  ?>
                                                            <td>
                                                                <?php $trn_attribute = $woocommerce_wpml->products->get_custom_attribute_translation($product_id, $attr_key, $attribute, $key); ?>
                                                                <label class="custom_attr_label"><?php _e('name','wpml-wcml'); ?></label>
                                                                <br>
                                                                <?php if (!$trn_attribute): ?>
                                                                    <input type="text" name="<?php echo $attr_key . '_name_' . $key ; ?>" value="" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                                                <?php else: ?>
                                                                                <?php if($default_language == $key): ?>
                                                                        <textarea rows="1" disabled="disabled"><?php echo $trn_attribute['name']; ?></textarea>
                                                                                <?php else: ?>
                                                                        <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_name_' . $key; ?>" value="<?php echo $trn_attribute['name']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                                                                <?php endif;?>
                                                                            <?php endif;?>
                                                                            <br>
                                                                <label class="custom_attr_label"><?php _e('values','wpml-wcml'); ?></label>
                                                                <br>
                                                                <?php if (!$trn_attribute): ?>
                                                                    <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_' . $key ; ?>" value="" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>/>
                                                                    <?php else: ?>
                                                                        <?php if($default_language == $key): ?>
                                                                        <textarea rows="1" disabled="disabled"><?php echo $trn_attribute['value']; ?></textarea>
                                                                        <?php else: ?>
                                                                        <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_' . $key; ?>" value="<?php echo $trn_attribute['value']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

                    <div class="wcml_fade"></div>

                    <?php if($products): ?>
                        <div class="wcml_product_pagination">
            <span class="displaying-num"><?php printf(__('%d products', 'wpml-wcml'), $products_count); ?></span>
                            <?php if(!isset($_GET['prid']) && isset($last) && $last > 1): ?>
                <a class="first-page <?php echo $pn==1?'disabled':''; ?>" href="<?php echo $pagination_url; ?>1" title="<?php _e('Go to the first page', 'wpml-wcml'); ?>">&laquo;</a>
                <a class="prev-page <?php echo $pn==1?'disabled':''; ?>" href="<?php echo $pagination_url.((int)$pn > 1?$pn - 1:$pn); ?>" title="<?php _e('Go to the previous page', 'wpml-wcml'); ?>">&lsaquo;</a>
                                <span><?php echo $pn;?>&nbsp;<?php _e('of', 'wpml-wcml'); ?>&nbsp;<?php echo $last; ?><span>
                <a class="next-page <?php echo $pn==$last?'disabled':''; ?>" href="<?php echo $pagination_url.((int)$pn<$last?$pn + 1:$last); ?>" title="<?php _e('Go to the next page', 'wpml-wcml'); ?>">&rsaquo;</a>
                <a class="last-page <?php echo $pn==$last?'disabled':''; ?>" href="<?php echo $pagination_url.$last; ?>" title="<?php _e('Go to the last page', 'wpml-wcml'); ?>">&raquo;</a>
                            <?php endif; ?>
                            <?php if(isset($_GET['prid']) || ($lm && isset($last)) && $last > 1): ?>
                <a href="<?php echo $pagination_url; ?>1"><?php _e('Show all products', 'wpml-wcml'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="clr"></div>
                    <?php endif;?>

                </form>                