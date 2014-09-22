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

$woocommerce_wpml->settings['first_editor_call'] = false;
$woocommerce_wpml->update_settings();
?>
<h3><?php _e('WooCommerce Products','wpml-wcml'); ?></h3>
<span style="display:none" id="wcml_product_update_button_label"><?php echo $button_labels['update'] ?></span>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <div class="wcml_prod_filters">
    <select class="wcml_product_category">
        <option value="0"><?php _e('Any category', 'wpml-wcml'); ?></option>
        <?php
        $product_categories = $wpdb->get_results($wpdb->prepare("SELECT tt.term_taxonomy_id,tt.term_id,t.name FROM $wpdb->term_taxonomy AS tt LEFT JOIN $wpdb->terms AS t ON tt.term_id = t.term_id LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'product_cat' AND icl.element_type= 'tax_product_cat' AND icl.language_code = %s",$default_language));
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
    unset( $all_statuses['trash'], $all_statuses['auto-draft'], $all_statuses['inherit'], $all_statuses['wc-pending'], $all_statuses['wc-processing'], $all_statuses['wc-on-hold'], $all_statuses['wc-completed'], $all_statuses['wc-cancelled'], $all_statuses['wc-refunded'], $all_statuses['wc-failed'] );
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
    <input type="hidden" id="get_product_data_nonce" value="<?php echo wp_create_nonce('wcml_product_data'); ?>" />

    <table class="widefat fixed wcml_products" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" width="5%"><?php _e('Type', 'wpml-wcml') ?></th>
                <th scope="col" width="20%"><?php _e('Product', 'wpml-wcml') ?></th>
                <th scope="col" width="75%"><?php echo $woocommerce_wpml->products->get_translation_flags($active_languages,$default_language,isset($_GET['slang']) && $_GET['slang'] != "all"?$_GET['slang']:false); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($products)): ?>
                <tr><td colspan="4"><h3 class="wcml_no_found_text"><?php _e('No products found','wpml-wcml'); ?></h3></td></tr>
            <?php else: ?>
            <?php foreach ($products as $product) :
                $product_id = icl_object_id($product->ID,'product',true,$default_language);
                $trid = $sitepress->get_element_trid($product_id,'post_'.$product->post_type);
                $product_translations = $sitepress->get_element_translations($trid,'post_'.$product->post_type,true,true);
            ?>
                <tr>
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
                        <span class="spinner"></span>
                        <a href="#prid_<?php echo $product->ID; ?>" id="wcml_details_<?php echo $product->ID; ?>" class="wcml_details" data-text-opened="<?php _e('Close', 'wpml-wcml') ?>" data-text-closed="<?php _e('Edit translation', 'wpml-wcml') ?>"><?php _e('Edit translation', 'wpml-wcml') ?></a>

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

