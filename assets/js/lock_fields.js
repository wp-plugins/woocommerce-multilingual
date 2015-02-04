jQuery(document).ready(function($){
    var i;
    var ids = ['_sku','_virtual','_downloadable','product-type','_stock_status','_backorders','_manage_stock','_stock','_sold_individually','_length','_weight','product_length','_regular_price','_sale_price','_sale_price_dates_from','_sale_price_dates_to','menu_order','comment_status','_tax_status','_tax_class','parent_id','crosssell_ids','upsell_ids'];

    $('.wcml_prod_hidden_notice').prependTo('#woocommerce-product-data');

    for (i = 0; i < ids.length; i++) {
        $('#'+ids[i]).attr('disabled','disabled');
        $('#'+ids[i]).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    }

    var buttons = ['add_variation','link_all_variations','attribute_taxonomy','save_attributes','add_new_attribute','product_attributes .remove_row','add_attribute','select_all_attributes','select_no_attributes'];
    for (i = 0; i < buttons.length; i++) {
        $('.'+buttons[i]).attr('disabled','disabled');
        $('.'+buttons[i]).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show().css('float','right'));
    }

    $('.remove_variation,.attribute_name').each(function(){
        $(this).attr('disabled','disabled');
        $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show().css('float','right'));
    });

    var inpt_names = ['_width','_height'];
    for (i = 0; i < ids.inpt_names; i++) {
        $('input[name="'+inpt_names[i]+'"]').attr('readonly','readonly');
        $('input[name="'+inpt_names[i]+'"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    }

    $('.woocommerce_variation input[type="text"],.woocommerce_variation input[type="number"],.woocommerce_attribute_data td textarea,.attribute_values').each(function(){
       $(this).attr('readonly','readonly');
       $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });

    $('.woocommerce_variation select,#variable_product_options .toolbar select,.woocommerce_variation input[type="checkbox"],.woocommerce_attribute_data input[type="checkbox"]').each(function(){
        $(this).attr('disabled','disabled');
        $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });

    $('form#post input[type="submit"]').click(function(){
        for (i = 0; i < ids.length; i++) {
            $('#'+ids[i]).removeAttr('disabled');
        }
        $('.woocommerce_variation select,#variable_product_options .toolbar select,.woocommerce_variation input[type="checkbox"],.woocommerce_attribute_data input[type="checkbox"]').each(function(){
            $(this).removeAttr('disabled');
        });
    });


    //quick edit fields
    for (i = 0; i < ids.length; i++) {
        $('.inline-edit-product [name="'+ids[i]+'"]').attr('disabled','disabled');
        $('.inline-edit-product [name="'+ids[i]+'"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    }

});

