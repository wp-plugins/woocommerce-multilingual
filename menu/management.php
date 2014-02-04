<?php
//actions
global $woocommerce_wpml, $sitepress,$wpdb,$wp_taxonomies;

$default_language = $sitepress->get_default_language();
$active_languages = $sitepress->get_active_languages();

$all_products_taxonomies = array();
$products_and_variation_taxonomies = get_taxonomies(array('object_type'=>array('product','product_variation')),'objects');

//don't use get_taxonomies for product, because when one more post type registered for product taxonomy functions returned taxonomies only for product type
foreach($wp_taxonomies as $key=>$taxonomy){
    if(in_array('product',$taxonomy->object_type) && !array_key_exists($key,$products_and_variation_taxonomies)){
        $all_products_taxonomies[$key] = $taxonomy;
    }
}



if(isset($_GET['tab'])){
    $current_tab = $_GET['tab'];
    if($current_tab == 'settings' && !current_user_can('wpml_manage_woocommerce_multilingual')){
        $current_tab = 'products';
    }
}else{
    $current_tab = current_user_can('wpml_manage_woocommerce_multilingual') ? 'settings' : 'products';
}


?>

<div class="wrap">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php _e('WooCommerce Multilingual', 'wpml-wcml') ?></h2>

    <?php if(current_user_can('wpml_manage_woocommerce_multilingual')): ?>
    <a class="nav-tab <?php echo $current_tab == 'settings' ?'nav-tab-active':''; ?>" href="admin.php?page=wpml-wcml"><?php _e('General settings', 'wpml-wcml') ?></a>
    <?php endif; ?>
    <a class="nav-tab <?php echo $current_tab == 'products' ? 'nav-tab-active' : ''; ?>" href="admin.php?page=wpml-wcml&tab=products"><?php _e('Products', 'wpml-wcml') ?></a>
    <?php foreach($products_and_variation_taxonomies as $tax_key => $tax): if(!$sitepress->is_translated_taxonomy($tax_key)) continue; ?>
        <a class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == $tax_key)?'nav-tab-active':''; ?>" href="admin.php?page=wpml-wcml&tab=<?php echo $tax_key; ?>"><?php echo $tax->labels->name ?></a>
    <?php endforeach; ?>
    <?php foreach($all_products_taxonomies as $tax_key => $tax): if(!$sitepress->is_translated_taxonomy($tax_key) || $tax_key == 'product_type') continue; ?>
    <a class="js-tax-tab-<?php echo $tax_key ?> nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == $tax_key)?'nav-tab-active':''; ?>" href="admin.php?page=wpml-wcml&tab=<?php echo $tax_key; ?>" <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>title="<?php esc_attr_e('You have untranslated terms!', 'wpml-wcml'); ?>"<?php endif;?>>
        <?php echo $tax->labels->name ?>
        <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>
        &nbsp;<i class="icon-warning-sign"></i>
        <?php endif; ?>
    </a>
    <input type="hidden" id="wcml_update_term_translated_warnings_nonce" value="<?php echo wp_create_nonce('wcml_update_term_translated_warnings_nonce') ?>" />
    
    <?php endforeach; ?>
    <div class="wcml_wrap">
        <?php if(!isset($_GET['tab']) && current_user_can('wpml_manage_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/settings.php'; ?>
        <?php elseif(isset($all_products_taxonomies[$current_tab]) || isset($products_and_variation_taxonomies[$current_tab])): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/product-taxonomy.php'; ?>
        <?php elseif((isset($_GET['tab']) && $_GET['tab'] == 'products') || !current_user_can('wpml_manage_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/products.php'; ?>
        <?php endif; ?>
    </div>
</div>

