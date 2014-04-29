<?php

class WCML_Product_Bundles{

    function __construct(){
        add_action('init', array($this, 'init'),9);
        add_action('wcml_gui_additional_box',array($this,'product_bundles_box'),10,3);
    }

    function init(){
    	global $sitepress;
        add_action('wcml_after_duplicate_product_post_meta',array($this,'sync_bundled_ids'),10,3);
        add_action('wcml_extra_titles',array($this,'product_bundles_title'),10,1);
        add_action('wcml_update_extra_fields',array($this,'bundle_update'),10,2);

        add_filter('wcml_cart_contents', array($this, 'cart_bundle_update_lang_switch'), 10, 4);
        add_filter('wcml_update_cart_contents_lang_switch', array($this, 'cart_contents_bundle_update_lang_switch'), 10, 4);
    }
    
    // Sync Bundled product meta with translated values
    function sync_bundled_ids($original_product_id, $trnsl_product_id, $data = false){
        global $sitepress, $wpdb;
        
        $custom_fields = get_post_custom($original_product_id);
        $atts = maybe_unserialize(get_post_meta($original_product_id, '_bundled_ids', true));
        $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');
        $tr_ids = array();
        
        foreach($atts as $key=>$id){
        	$tr_id = icl_object_id($id,'product',true,$lang);
        	$tr_ids[] = $tr_id;

        	// Get original bundle settings
        	$filter_variations = $custom_fields['filter_variations_'.$id][0] ?: 0;
        	$filter_variations = $custom_fields['filter_variations_'.$id][0];
			$override_defaults = $custom_fields['override_defaults_'.$id][0];
			$bundle_quantity = $custom_fields['bundle_quantity_'.$id][0];
			$bundle_discount = $custom_fields['bundle_discount_'.$id][0];
			$hide_thumbnail = $custom_fields['hide_thumbnail_'.$id][0];
			$override_title = $custom_fields['override_title_'.$id][0];
			$product_title = $custom_fields['product_title_'.$id][0];
			$override_description = $custom_fields['override_description_'.$id][0];
			$product_description = $custom_fields['product_description_'.$id][0];
			$hide_filtered_variations = $custom_fields['hide_filtered_variations_'.$id][0];
			$visibility = $custom_fields['visibility_'.$id][0];
			
			// Delete original bundle settings
			delete_post_meta( $trnsl_product_id, 'filter_variations_'.$id );
			delete_post_meta( $trnsl_product_id, 'override_defaults_'.$id );
			delete_post_meta( $trnsl_product_id, 'bundle_quantity_'.$id );
			delete_post_meta( $trnsl_product_id, 'bundle_discount_'.$id );
			delete_post_meta( $trnsl_product_id, 'hide_thumbnail_'.$id );
			delete_post_meta( $trnsl_product_id, 'override_title_'.$id );
			delete_post_meta( $trnsl_product_id, 'product_title_'.$id );
			delete_post_meta( $trnsl_product_id, 'override_description_'.$id );
			delete_post_meta( $trnsl_product_id, 'product_description_'.$id );
			delete_post_meta( $trnsl_product_id, 'hide_filtered_variations_'.$id );
			delete_post_meta( $trnsl_product_id, 'visibility_'.$id );
			
			// Duplicate translated bundle settings
			update_post_meta( $trnsl_product_id, 'filter_variations_'.$tr_id, $filter_variations );
			update_post_meta( $trnsl_product_id, 'override_defaults_'.$tr_id, $override_defaults );
			update_post_meta( $trnsl_product_id, 'bundle_quantity_'.$tr_id, $bundle_quantity );
			update_post_meta( $trnsl_product_id, 'bundle_discount_'.$tr_id, $bundle_discount );
			update_post_meta( $trnsl_product_id, 'hide_thumbnail_'.$tr_id, $hide_thumbnail );
			update_post_meta( $trnsl_product_id, 'override_title_'.$tr_id, $override_title );
			update_post_meta( $trnsl_product_id, 'product_title_'.$tr_id, $product_title );
			update_post_meta( $trnsl_product_id, 'override_description_'.$tr_id, $override_description );
			update_post_meta( $trnsl_product_id, 'product_description_'.$tr_id, $product_description );
			update_post_meta( $trnsl_product_id, 'hide_filtered_variations_'.$tr_id, $hide_filtered_variations );
			update_post_meta( $trnsl_product_id, 'visibility_'.$tr_id, $hide_filtered_variations );
			
        }
        
        // Update bundle products ids
        update_post_meta($trnsl_product_id,'_bundled_ids',serialize($tr_ids)); 
        
        // Update _allowed_variations
        $tr_allowed_variations = array();
        $allowed_variations = maybe_unserialize(get_post_meta($original_product_id, '_allowed_variations', true));
        foreach($allowed_variations as $prod_id => $allowed_ids){
        	$trans_prod_id = icl_object_id($prod_id, 'product', false, $lang);
        	foreach($allowed_ids as $key => $var_id){
	        	$trans_id = icl_object_id($var_id, 'product_variation', false, $lang);
	        	$tr_allowed_variations[$trans_prod_id][] = $trans_id;
        	}
        }
        update_post_meta($trnsl_product_id,'_allowed_variations',$tr_allowed_variations); 
        
        
        // Update _bundle_defaults
        $tr_bundle_defaults = array();
        $bundle_defaults = maybe_unserialize(get_post_meta($original_product_id, '_bundle_defaults', true));
        foreach($bundle_defaults as $prod_id => $allowed_ids){
        	$trans_prod_id = icl_object_id($prod_id, 'product', false, $lang);
        	$tr_bundle_defaults[$trans_prod_id]=array();
        	foreach($allowed_ids as $key => $var_id){
	        	$trans_id = icl_object_id($var_id, 'product_variation', false, $lang);
	        	$tr_bundle_defaults[$trans_prod_id][] = $trans_id;
        	}
        }
        update_post_meta($trnsl_product_id,'_bundle_defaults',$tr_bundle_defaults);  
        
    }

    // Update Bundle title and descritpion
    function bundle_update($tr_id, $data){
    	if(!empty($data['bundles'])){
	    	foreach($data['bundles'] as $bundle_id => $bundle_data){
				update_post_meta( $tr_id, 'product_title_'.$bundle_id, $bundle_data['bundle_title'] ); 
				update_post_meta( $tr_id, 'product_description_'.$bundle_id, $bundle_data['bundle_desc'] );    	
	    	}
    	}
	    
    }
    
    // Add 'Product Bundles' title to the WCML Product GUI if the current product is a bundled product
    function product_bundles_title($product_id){
	    $bundles = get_post_meta($product_id, '_bundled_ids', true);
        if(!empty($bundles)){ ?>
            <th scope="col">Product Bundles</th>
        <?php } 
    }
    
    // Add Bundles Box to WCML Translation GUI
    function product_bundles_box($product_id,$lang, $is_duplicate_product = false ) {
        global $sitepress;
        $default_language = $sitepress->get_default_language();
        if($default_language != $lang){
            $product_id = icl_object_id($product_id, 'product', false, $lang);
        }
        $template_data = array();

        if($default_language == $lang){
            $template_data['original'] = true;
        }else{
            $template_data['original'] = false;
        }
       
        if (!is_null($product_id)) {
            $product_bundles = maybe_unserialize(get_post_meta($product_id,'_bundled_ids', true));
            $template_data['product_bundles'] = $product_bundles;
            if (empty($product_bundles)) {
                $template_data['empty_bundles'] = true;
            } else {
                if ($default_language == $lang) {
                    $template_data['product_bundles'] = $product_bundles;
                }
                foreach ($product_bundles as $key=>$bundle_id) {
                    $bundle_title = get_post_meta($product_id,'product_title_'.$bundle_id, true);
                    $bundle_desc = get_post_meta($product_id,'product_description_'.$bundle_id, true);
                    $bundles_texts = array();
                    $bundle_name = get_the_title($bundle_id);
                    $template_data['bundles_data'][$bundle_name]['bundle_title'] = $bundle_title;
                    $template_data['bundles_data'][$bundle_name]['bundle_desc'] = $bundle_desc;
                }
            }
        } else {
            $template_data['empty_translation'] = true;
        }
        include WCML_PLUGIN_PATH . '/compatibility/templates/bundles_box.php';
    }

    // Update bundled_by cart keys and stamp when language is switched
    function cart_contents_bundle_update_lang_switch($cart_contents,$key,$new_key,$current_language){
        $exist_ids_translations = array();

        if(isset($cart_contents[$key]['bundled_items'])){
            foreach($cart_contents[$key]['bundled_items'] as $index=>$bundled_key){
                if($cart_contents[$bundled_key]['bundled_by'] == $key){
                    $cart_contents[$bundled_key]['bundled_by'] = $new_key;
                }
            }
        }

        if(isset($cart_contents[$key]['stamp'])){
            global $woocommerce_wpml;
            $new_stamp = array();
        	foreach( $cart_contents[$key]['stamp'] as $st_prd => $st_prod_data){
	        	$tr_st_p_id = icl_object_id($st_prd,'product',false,$current_language);

                if(isset($st_prod_data['variation_id'])){
                    if(array_key_exists($st_prod_data['variation_id'],$exist_ids_translations)){
                        $tr_st_v_id = $exist_ids_translations[$st_prod_data['variation_id']];
                    }else{
                        $tr_st_v_id = icl_object_id($st_prod_data['variation_id'],'product_variation',false,$current_language);
                    }

                    if(!is_null($tr_st_v_id)){
                        $st_prod_data['variation_id'] = $tr_st_v_id;
                        $exist_ids_translations[$st_prod_data['variation_id']] = $tr_st_v_id;
                    }

                    foreach($st_prod_data['attributes'] as $taxonomy=>$attribute){
                        if (substr($taxonomy, 0, 10) == 'attribute_') {
                            $tax = substr($taxonomy, 10);
                        }else{
                            $tax = $taxonomy;
                        }
                        $st_prod_data['attributes'][$taxonomy] = $woocommerce_wpml->products->get_cart_attribute_translation($tax,$attribute,$st_prod_data['product_id'],$tr_st_p_id,$current_language);
                    }

                }

                if(!is_null($tr_st_p_id)){
                    $st_prod_data['product_id'] = $tr_st_p_id;
                    $new_stamp[$tr_st_p_id] = $st_prod_data;
                    $exist_ids_translations[$st_prd] = $tr_st_p_id;
                }
        	}

            $cart_contents[$key]['stamp'] = $new_stamp;
        }

        return $cart_contents;
    }

    // Update bundled items cart keys when language is switched
    function cart_bundle_update_lang_switch($new_cart_data,$cart_contents, $key, $new_key){

        if(isset($cart_contents[$key]['bundled_by']) && isset($new_cart_data[$cart_contents[$key]['bundled_by']])){
            global $woocommerce;
            $buff = $new_cart_data[$new_key];
            $key_item = $woocommerce->cart->generate_cart_id( $new_cart_data[$new_key]['product_id'], $new_cart_data[$new_key]['variation_id'], $new_cart_data[$new_key]['variation'], array( 'bundled_item_id' => $new_cart_data[$new_key]['product_id'], 'bundled_by' => $new_cart_data[$new_key]['bundled_by'], 'stamp' => $new_cart_data[$new_key][ 'stamp' ], 'dynamic_pricing_allowed' => 'no' ) );

            foreach($new_cart_data[$cart_contents[$key]['bundled_by']]['bundled_items'] as  $index => $item){
                if($item == $key){
                    $new_cart_data[$cart_contents[$key]['bundled_by']]['bundled_items'][$index] = $key_item;
                }
            }

            unset($new_cart_data[$new_key]);
            $new_cart_data[$key_item] = $buff;
        }

        return $new_cart_data;
    }

}

