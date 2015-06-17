<?php

class WCML_Product_Bundles{

    function __construct(){
        add_action('wcml_gui_additional_box',array($this,'product_bundles_box'),10,3);
        add_action('wcml_after_duplicate_product_post_meta',array($this,'sync_bundled_ids'),10,3);
        add_action('wcml_extra_titles',array($this,'product_bundles_title'),10,1);
        add_action('wcml_update_extra_fields',array($this,'bundle_update'),10,2);
        add_filter('wcml_cart_contents', array($this, 'cart_bundle_update_lang_switch'), 10, 4);
        add_filter('wcml_update_cart_contents_lang_switch', array($this, 'cart_contents_bundle_update_lang_switch'), 10, 4);
        add_filter('wcml_filter_cart_item_data', array($this, 'filter_cart_item_data') );
        add_filter('wcml_exception_duplicate_products_in_cart', array($this, 'check_on_bundle_product_in_cart'), 10, 2 );
    }
    
    // Sync Bundled product '_bundle_data' with translated values when the product is duplicated
    function sync_bundled_ids($original_product_id, $trnsl_product_id, $data = false){
        global $sitepress;

        $atts = maybe_unserialize(get_post_meta($original_product_id, '_bundle_data', true));

        if( $atts ){
            $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');
            $tr_ids = array();
            $i = 2;
            foreach($atts as $id=>$bundle_data){
                $tr_id = apply_filters( 'translate_object_id',$id,get_post_type($id),true,$lang);

                if(isset($tr_bundle[$tr_id])){
                    $bundle_key = $tr_id.'_'.$i;
                    $i++;
                }else{
                    $bundle_key = $tr_id;
                }

                $tr_bundle[$bundle_key] = $bundle_data;

                $tr_bundle[$bundle_key]['product_id'] = $tr_id;

                if(isset($bundle_data['product_title'])){
                    if($bundle_data['override_title']=='yes'){
                        $tr_bundle[$bundle_key]['product_title'] =  '';
                    }else{
                        $tr_title= get_the_title($tr_id);
                        $tr_bundle[$bundle_key]['product_title'] =  $tr_title;
                    }
                }

                if(isset($bundle_data['product_description'])){
                    if($bundle_data['override_description']=='yes'){
                        $tr_bundle[$bundle_key]['product_description'] =  '';
                    }else{
                        $tr_prod = get_post($tr_id);
                        $tr_desc = $tr_prod->post_excerpt;
                        $tr_bundle[$bundle_key]['product_description'] =  $tr_desc;
                    }
                }

                if(isset($bundle_data['filter_variations']) && $bundle_data['filter_variations']=='yes'){
                    $allowed_var = $bundle_data['allowed_variations'];
                    foreach($allowed_var as $key=>$var_id){
                        $tr_var_id = apply_filters( 'translate_object_id',$var_id,get_post_type($var_id),true,$lang);
                        $tr_bundle[$bundle_key]['allowed_variations'][$key] =  $tr_var_id;
                    }
                }

                if(isset($bundle_data['bundle_defaults']) && !empty($bundle_data['bundle_defaults'])){
                    foreach($bundle_data['bundle_defaults'] as $tax=>$term_slug){

                        global $woocommerce_wpml;
                        $term_id = $woocommerce_wpml->products->wcml_get_term_id_by_slug( $tax, $term_slug );

                        if( $term_id ){
                            // Global Attribute
                            $tr_def_id = apply_filters( 'translate_object_id',$term_id,$tax,true,$lang);
                            $tr_term = $woocommerce_wpml->products->wcml_get_term_by_id( $tr_def_id, $tax );
                            $tr_bundle[$bundle_key]['bundle_defaults'][$tax] =  $tr_term->slug;
                        }else{
                            // Custom Attribute
                            $args = array( 'post_type' => 'product_variation', 'meta_key' => 'attribute_'.$tax,  'meta_value' => $term_slug, 'meta_compare' => '=');
                            $variationloop = new WP_Query( $args );
                            while ( $variationloop->have_posts() ) : $variationloop->the_post();
                                $tr_var_id = apply_filters( 'translate_object_id',get_the_ID(),'product_variation',true,$lang);
                                $tr_meta = get_post_meta($tr_var_id, 'attribute_'.$tax , true);
                                $tr_bundle[$bundle_key]['bundle_defaults'][$tax] =  $tr_meta;
                            endwhile;
                        }

                    }
                }


            }

            update_post_meta($trnsl_product_id,'_bundle_data',$tr_bundle);
        }

    }

    // Update Bundled products title and descritpion after saving the translation
    function bundle_update($tr_id, $data){
    	
    	global $sitepress;
    	$tr_bundle_data = array();
    	$tr_bundle_data = maybe_unserialize(get_post_meta($tr_id,'_bundle_data', true));    	
    	
    	if(!empty($data['bundles'])){
	    	foreach($data['bundles'] as $bundle_id => $bundle_data){
	    		if(isset($tr_bundle_data[$bundle_id])){
	    			$tr_bundle_data[$bundle_id]['product_title'] = $bundle_data['bundle_title'];
	    			$tr_bundle_data[$bundle_id]['product_description'] = $bundle_data['bundle_desc'];
	    		}
	    	}
		    update_post_meta( $tr_id, '_bundle_data', $tr_bundle_data ); 
		    $tr_bundle_data = array();
    	}
    	
    }
    
    // Add 'Product Bundles' title to the WCML Product GUI if the current product is a bundled product
    function product_bundles_title($product_id){
    	$bundle_data = maybe_unserialize(get_post_meta($product_id,'_bundle_data', true));
    	if(!empty($bundle_data) && $bundle_data!=false){ ?>
	        <th scope="col"><?php _e('Product Bundles', 'wcml_product_bundles'); ?></th>
        <?php }
    }
    
    // Add Bundles Box to WCML Translation GUI
    function product_bundles_box($product_id,$lang, $is_duplicate_product = false ) {
        global $sitepress, $woocommerce_wpml;
        $isbundle = true;
        $translated = true;
        $template_data = array();
        
        $default_language = $woocommerce_wpml->products->get_original_product_language( $product_id );
        
        
        if($default_language != $lang){
            $tr_product_id = apply_filters( 'translate_object_id',$product_id, 'product', true, $lang);
            if($tr_product_id == $product_id){
	            $translated = false;
            }else{
	            $product_id = $tr_product_id;
            }
        }
        
        $bundle_data = maybe_unserialize(get_post_meta($product_id,'_bundle_data', true));
        
        if(empty($bundle_data) || $bundle_data==false){
	        $isbundle = false;
        }
                        
        if(!$isbundle){
	        return;
        }
        
        if($default_language == $lang){
            $template_data['original'] = true;
        }else{
            $template_data['original'] = false;
        }

        if (!$translated ) {
        	$template_data['empty_translation'] = true;
            $template_data['product_bundles'] = array();
        }else{
            $product_bundles = array_keys($bundle_data);
            $k = 0;
			foreach($product_bundles as $original_id){
				$tr_bundles_ids[$k] = apply_filters( 'translate_object_id',$original_id,'product',false,$lang);
				$k++;
			}
			
			$template_data['product_bundles'] = $tr_bundles_ids;
			$tr_bundles_ids = $template_data['product_bundles']; 
			
            if (empty($tr_bundles_ids)) {
                $template_data['empty_bundles'] = true;
                $template_data['product_bundles'] = array();
            } else {
                if ($default_language == $lang) {
                    $template_data['product_bundles'] = $tr_bundles_ids;
                }
                
                foreach ($product_bundles as $bundle_id) {
                	$bundles_texts = array();
                    $bundle_name = get_the_title($bundle_id);
                    
                    if(isset($bundle_data[$bundle_id]['override_title']) && $bundle_data[$bundle_id]['override_title']=='yes'){
                    	$bundle_title = $bundle_data[$bundle_id]['product_title'];
                    	$template_data['bundles_data'][$bundle_name]['override_bundle_title'] = 'yes';
                    }else{
	                    $bundle_title = get_the_title($bundle_id);
                    }
                    
                    if(isset($bundle_data[$bundle_id]['override_description']) && $bundle_data[$bundle_id]['override_description']=='yes'){
                    	$bundle_desc = $bundle_data[$bundle_id]['product_description'];
                    	$template_data['bundles_data'][$bundle_name]['override_bundle_desc'] = 'yes';
                    }else{
                    	$bundle_prod = get_post($bundle_id);
					    $bundle_desc = $bundle_prod->post_excerpt; 
                    }
                    
                    $template_data['bundles_data'][$bundle_name]['bundle_title'] = $bundle_title;
                    $template_data['bundles_data'][$bundle_name]['bundle_desc'] = $bundle_desc;
                }
                
            }
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
	        	$tr_st_p_id = apply_filters( 'translate_object_id',$st_prd,'product',false,$current_language);

                if(isset($st_prod_data['variation_id'])){
                    if(array_key_exists($st_prod_data['variation_id'],$exist_ids_translations)){
                        $tr_st_v_id = $exist_ids_translations[$st_prod_data['variation_id']];
                    }else{
                        $tr_st_v_id = apply_filters( 'translate_object_id',$st_prod_data['variation_id'],'product_variation',false,$current_language);
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

    function filter_cart_item_data ( $cart_contents ){
        unset( $cart_contents['bundled_items'] );

        return $cart_contents;
    }

    function check_on_bundle_product_in_cart( $flag, $cart_item ){
        if( isset( $cart_item['bundled_by'] ) ){
            return true;
        }

        return false;
    }


}

