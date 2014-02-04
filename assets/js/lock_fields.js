jQuery(document).ready(function($){
    var i;
    var ids = ['_sku','_virtual','_downloadable','product-type','_stock_status','_backorders','_manage_stock','_stock','_sold_individually','_weight','product_length','_regular_price','_sale_price','_sale_price_dates_from','_sale_price_dates_to','menu_order','comment_status','_tax_status','_tax_class'];

    for (i = 0; i < ids.length; i++) {
        $('#'+ids[i]).attr('disabled','disabled');
        $('#'+ids[i]).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    }

    $('input[name="_width"]').attr('readonly','readonly');
    $('input[name="_width"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    $('input[name="_height"]').attr('readonly','readonly');
    $('input[name="_height"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    $('.woocommerce_variation input[type="text"],.woocommerce_variation input[type="number"]').each(function(){
       $(this).attr('readonly','readonly');
       $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });

    $('.woocommerce_variation select').each(function(){
        $(this).attr('disabled','disabled');
        $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
    });

    $('form#post input[type="submit"]').click(function(){
        for (i = 0; i < ids.length; i++) {
            $('#'+ids[i]).removeAttr('disabled');
        }
    });

});

