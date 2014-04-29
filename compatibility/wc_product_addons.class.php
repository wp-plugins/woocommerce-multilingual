<?php

class WCML_Product_Addons{

    function __construct(){

        add_action('init', array($this, 'init'),9);
        add_filter('addons_product_terms',array($this,'addons_product_terms'));
        add_filter('product_addons_fields',array($this,'product_addons_filter'),10,2);

        global $pagenow;
        if($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='product' && isset($_GET['page']) && $_GET['page']=='global_addons' && !isset($_GET['edit'])){
            add_action('admin_notices', array($this, 'inf_translate_strings'));
        }

        add_action( 'addons_panel_start', array( $this, 'inf_translate_strings' ) );
    }

    function init(){
        add_action('after_save_global_addons',array($this,'register_addons_strings'),10,2);
        add_action('after_update_product_addons',array($this,'register_addons_strings'),10,2);
    }


    function register_addons_strings($id,$addons){
        foreach($addons as $addon){
            //register name
            icl_register_string('wc_product_addons_strings', $id.'_addon_'.$addon['type'].'_'.$addon['position'].'_name', $addon['name']);
            //register description
            icl_register_string('wc_product_addons_strings', $id.'_addon_'.$addon['type'].'_'.$addon['position'].'_description', $addon['description']);
            //register options labels
            foreach($addon['options'] as $key=>$option){
                icl_register_string('wc_product_addons_strings', $id.'_addon_'.$addon['type'].'_'.$addon['position'].'_option_label_'.$key, $option['label']);
            }
        }
    }

    function product_addons_filter($addons, $object_id){
        foreach($addons as $add_id => $addon){
            $addons[$add_id]['name'] = icl_t('wc_product_addons_strings', $object_id.'_addon_'.$addon['type'].'_'.$addon['position'].'_name', $addon['name']);
            $addons[$add_id]['description'] = icl_t('wc_product_addons_strings', $object_id.'_addon_'.$addon['type'].'_'.$addon['position'].'_description', $addon['description']);
            foreach($addon['options'] as $key=>$option){
                $addons[$add_id]['options'][$key]['label'] = icl_t('wc_product_addons_strings', $object_id.'_addon_'.$addon['type'].'_'.$addon['position'].'_option_label_'.$key, $option['label']);

                //price filter
                $addons[$add_id]['options'][$key]['price']  = apply_filters('wcml_raw_price_amount', $option['price'],$object_id);
            }
        }

        return $addons;
    }


    function addons_product_terms($product_terms){
        global $sitepress;

        foreach($product_terms as $key => $product_term){
            $product_terms[$key] = icl_object_id($product_term,'product_cat',true,$sitepress->get_default_language());
        }

        return $product_terms;
    }

    function inf_translate_strings(){
        $message = '<div><p class="icl_cyan_box">';
        $message .= sprintf(__('To translate Add-ons strings please save Add-ons and go to the <b><a href="%s">String Translation interface</a></b>', 'wpml-wcml'), 'admin.php?page=wpml-string-translation/menu/string-translation.php&context=wc_product_addons_strings');
        $message .= '</p></div>';

        echo $message;
    }
}
