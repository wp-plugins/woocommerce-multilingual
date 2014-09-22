<?php
global $woocommerce_wpml;
if(get_option('wcml_products_to_sync') === false ){
    $woocommerce_wpml->troubleshooting->wcml_sync_variations_update_option();
}

$prod_with_variations = $woocommerce_wpml->troubleshooting->wcml_count_products_with_variations();
$prod_count = $woocommerce_wpml->troubleshooting->wcml_count_products_for_gallery_sync();
$prod_categories_count = $woocommerce_wpml->troubleshooting->wcml_count_product_categories();

$all_products_taxonomies = get_taxonomies(array('object_type'=>array('product')),'objects');
unset($all_products_taxonomies['product_type'],$all_products_taxonomies['product_cat'],$all_products_taxonomies['product_tag']);
?>
<div class="wrap wcml_trblsh">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php _e('Troubleshooting', 'wpml-wcml') ?></h2>
    <div class="wcml_trbl_warning">
        <h3><?php _e('Please make a backup of your database before you start the synchronization', 'wpml-wcml') ?></h3>
    </div>
    <div class="trbl_variables_products">
        <h3><?php _e('Sync variables products', 'wpml-wcml') ?></h3>
        <ul>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_update_product_count" />
                    <?php _e('Update products count:', 'wpml-wcml') ?>
                    <span class="var_status"><?php echo $prod_with_variations; ?></span>&nbsp;<span><?php  _e('products with variations', 'wpml-wcml'); ?></span>
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_product_variations" checked="checked" />
                    <?php _e('Sync products variations:', 'wpml-wcml') ?>
                    <span class="var_status"><?php echo $prod_with_variations; ?></span>&nbsp;<span><?php _e('left', 'wpml-wcml') ?></span>
                </label>

            </li>
            <?php if(defined('WPML_MEDIA_VERSION')): ?>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_gallery_images" />
                    <?php _e('Sync products "gallery images"', 'wpml-wcml') ?>
                    <span class="gallery_status"><?php echo $prod_count; ?></span>&nbsp;<span><?php _e('left', 'wpml-wcml') ?></span>
                </label>
            </li>
            <?php endif; ?>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_categories" />
                    <?php _e('Sync products categories (display type, thumbnail):', 'wpml-wcml') ?>
                    <span class="cat_status"><?php echo $prod_categories_count; ?></span>&nbsp;<span><?php _e('left', 'wpml-wcml') ?></span>
                </label>

            </li>

            <li>
                <label>
                    <input type="checkbox" id="wcml_duplicate_terms" <?php echo !count($all_products_taxonomies)?'disabled="disabled"':''; ?> />
                    <?php _e('Duplicate terms ( please select attribute ):', 'wpml-wcml') ?>
                    <select id="attr_to_duplicate" <?php echo !count($all_products_taxonomies)?'disabled="disabled"':''; ?>>
                        <?php
                        $terms_count = false;
                        if(!count($all_products_taxonomies)){ ?>
                            <option value="0" ><?php _e('none', 'wpml-wcml'); ?></option>
                        <?php }
                        foreach($all_products_taxonomies as $tax_key => $tax):
                            if(!$terms_count) $terms_count = wp_count_terms($tax_key); ?>
                            <option value="<?php echo $tax_key; ?>" rel="<?php echo wp_count_terms($tax_key); ?>"><?php echo ucfirst($tax->labels->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="attr_status"><?php echo $terms_count; ?></span>&nbsp;<span><?php _e('left', 'wpml-wcml') ?></span>
                </label>

            </li>
            <li>
                <button type="button" class="button-secondary" id="wcml_trbl"><?php _e('Start', 'wpml-wcml') ?></button>
        <input id="count_prod_variat" type="hidden" value="<?php echo $prod_with_variations; ?>"/>
        <input id="count_prod" type="hidden" value="<?php echo $prod_count; ?>"/>
        <input id="count_categories" type="hidden" value="<?php echo $prod_categories_count; ?>"/>
        <input id="count_terms" type="hidden" value="<?php echo $terms_count; ?>"/>
        <input id="sync_galerry_page" type="hidden" value="0"/>
        <input id="sync_category_page" type="hidden" value="0"/>
        <span class="spinner"></span>
            </li>
        </ul>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function(){
        //troubleshooting page
        jQuery('#wcml_trbl').on('click',function(){
            var field = jQuery(this);
            field.attr('disabled', 'disabled');
            jQuery('.spinner').css('display','inline-block');

            if(jQuery('#wcml_sync_update_product_count').is(':checked')){
                update_product_count();
            }else if(jQuery('#wcml_sync_product_variations').is(':checked')){
            sync_variations();
            }else if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                sync_product_gallery();
            }else if(jQuery('#wcml_sync_categories').is(':checked')){
                sync_product_categories();
            }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                duplicate_terms();
            }
        });

        jQuery('#attr_to_duplicate').on('change',function(){
           jQuery('.attr_status').html(jQuery(this).find('option:selected').attr('rel'))
           jQuery('#count_terms').val(jQuery(this).find('option:selected').attr('rel'))
        });

        });

    function update_product_count(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_update_count",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_update_count'); ?>"
            },
            success: function(response) {
                    jQuery('.var_status').each(function(){
                        jQuery(this).html(response);
                    })
                    jQuery('#count_prod_variat').val(response);
                    if(jQuery('#wcml_sync_product_variations').is(':checked')){
                    sync_variations();
                    }else if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                        sync_product_gallery();
                    }else if(jQuery('#wcml_sync_categories').is(':checked')){
                        sync_product_categories();
                    }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }
            }
    });
    }

    function sync_variations(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_sync_variations",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_sync_variations'); ?>"
            },
            success: function(response) {
                if(jQuery('#count_prod_variat').val() == 0){
                    jQuery('.var_status').each(function(){
                        jQuery(this).html(0);
                    });
                    if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                        sync_product_gallery();
                    }else if(jQuery('#wcml_sync_categories').is(':checked')){
                        sync_product_categories();
                    }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                        jQuery('#wcml_trbl').removeAttr('disabled');
                        jQuery('.spinner').hide();
                        jQuery('#wcml_trbl').next().fadeOut();
                    }

                }else{
                    var left = jQuery('#count_prod_variat').val()-3;
                    if(left < 0 ){
                        left = 0;
                    }
                    jQuery('.var_status').each(function(){
                        jQuery(this).html(left);
                    });
                    jQuery('#count_prod_variat').val(left);
                    sync_variations();
                }
            }
        });
    }

    function sync_product_gallery(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_gallery_images",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_gallery_images'); ?>",
                page: jQuery('#sync_galerry_page').val()
            },
            success: function(response) {
                if(jQuery('#count_prod').val() == 0){
                    if(jQuery('#wcml_sync_categories').is(':checked')){
                        sync_product_categories();
                    }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                    }
                    jQuery('.gallery_status').html(0);
                }else{
                    var left = jQuery('#count_prod').val()-5;
                    if(left < 0 ){
                        left = 0;
                    }else{
                        jQuery('#sync_galerry_page').val(parseInt(jQuery('#sync_galerry_page').val())+1)
                    }
                    jQuery('.gallery_status').html(left);
                    jQuery('#count_prod').val(left);
                    sync_product_gallery();
                }
            }
        });
    }

    function sync_product_categories(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_sync_categories",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_sync_categories'); ?>",
                page: jQuery('#sync_category_page').val()
            },
            success: function(response) {
                if(jQuery('#count_categories').val() == 0){
                    if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                    }
                    jQuery('.cat_status').html(0);
                }else{
                    var left = jQuery('#count_categories').val()-5;
                    if(left < 0 ){
                        left = 0;
                    }else{
                        jQuery('#sync_category_page').val(parseInt(jQuery('#sync_category_page').val())+1)
                    }
                    jQuery('.cat_status').html(left);
                    jQuery('#count_categories').val(left);
                    sync_product_categories();
                }
            }
        });
    }

    function duplicate_terms(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_duplicate_terms",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_duplicate_terms'); ?>",
                attr: jQuery('#attr_to_duplicate option:selected').val()
            },
            success: function(response) {
                if(jQuery('#count_terms').val() == 0){
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                    jQuery('.attr_status').html(0);
                }else{
                    var left = jQuery('#count_terms').val()-5;
                    if(left < 0 ){
                        left = 0;
                    }
                    jQuery('.attr_status').html(left);
                    jQuery('#count_terms').val(left);

                    duplicate_terms();
                }
            }
        });
    }

</script>