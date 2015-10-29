<?php

class WCML_gravityforms{

    function __construct(){
        add_filter('gform_formatted_money',array($this,'wcml_convert_price'),10,2);
        add_filter('wcml_multi_currency_is_ajax',array($this,'add_ajax_action'));

        add_filter('wcml_exception_duplicate_products_in_cart', array($this, 'addons_duplicate_exception'),10,2);

        add_action( 'wcml_after_duplicate_product_post_meta', array( $this, 'sync_gf_data'), 10, 3 );
    }
    
    function wcml_convert_price($formatted, $unformatted){
        if ( ! is_admin() ) {
        	$currency = apply_filters('wcml_price_currency', get_woocommerce_currency());
        	$formatted = strip_tags(wc_price(apply_filters('wcml_raw_price_amount', $unformatted), array('currency'=>$currency)));
        }
        return $formatted;    
	}

	
	function add_ajax_action($actions){
		$actions[] = 'get_updated_price';
		return $actions;
	}

    function addons_duplicate_exception( $exclude, $cart_item ) {
        if ( isset( $cart_item[ '_gravity_form_data' ] ) ) {
            $exclude = true;
        }
        return $exclude;
    }

    function sync_gf_data($original_product_id, $trnsl_product_id, $data){
        $orig_gf = maybe_unserialize( get_post_meta( $original_product_id, '_gravity_form_data' , true ) );
        $trnsl_gf = maybe_unserialize( get_post_meta( $trnsl_product_id, '_gravity_form_data' , true ) );

        if( !$trnsl_gf ){
            update_post_meta( $trnsl_product_id, '_gravity_form_data', $orig_gf );
        }else{
            $trnsl_gf['id'] = $orig_gf['id'];
            $trnsl_gf['display_title'] = $orig_gf['display_title'];
            $trnsl_gf['display_description'] = $orig_gf['display_description'];
            $trnsl_gf['disable_woocommerce_price'] = $orig_gf['disable_woocommerce_price'];
            $trnsl_gf['disable_calculations'] = $orig_gf['disable_calculations'];
            $trnsl_gf['disable_label_subtotal'] = $orig_gf['disable_label_subtotal'];
            $trnsl_gf['disable_label_options'] = $orig_gf['disable_label_options'];
            $trnsl_gf['disable_label_total'] = $orig_gf['disable_label_total'];
            $trnsl_gf['disable_anchor'] = $orig_gf['disable_anchor'];

            update_post_meta( $trnsl_product_id, '_gravity_form_data', $trnsl_gf );
        }
    }
   
}
