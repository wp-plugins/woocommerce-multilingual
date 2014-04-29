<?php

class WCML_Variation_Swatches_and_Photos{

    function __construct(){
        add_action('init', array($this, 'init'),9);
    }

    function init(){
        add_action('wcml_after_duplicate_product_post_meta',array($this,'sync_variation_swatches_and_photos'),10,3);
    }

    function sync_variation_swatches_and_photos($original_product_id, $trnsl_product_id, $data = false){
        global $sitepress, $wpdb;
        
        $atts = maybe_unserialize(get_post_meta($original_product_id, '_swatch_type_options', true));
        $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');
        $tr_atts = $atts;
        
        foreach($atts as $att_name=>$att_opts){
	    	foreach($att_opts['attributes'] as $slug=>$options){
		    	$o_term = get_term_by('slug', $slug, $att_name);
		    	$tr_term_id = icl_object_id($o_term->term_id,$att_name,false,$lang);
		    	if(!is_null($tr_term_id)){			    	
			    	$tr_term = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_term_id, $att_name));
			    	$tr_slug = $tr_term->slug;
			    	
			    	if($tr_slug!=''){
				    	$tr_atts[$att_name]['attributes'][$tr_term->slug]= $atts[$att_name]['attributes'][$slug];
				    	if(isset($options['image'])){
					    	$o_img_id = $options['image'];
					    	$tr_img_id = icl_object_id($o_img_id,'image',false,$lang);
				    	}
				    	unset($tr_atts[$att_name]['attributes'][$slug]);
			    	}
		    	}		    			    	
	    	}  
        }
        update_post_meta($trnsl_product_id,'_swatch_type_options',$tr_atts); // Meta gets overwritten
    }

}
