<?php

class WCML_Tab_Manager{

    function __construct(){

        add_action('init', array($this, 'init'),9);
    }

    function init(){
        add_action('wcml_after_duplicate_product_post_meta',array($this,'sync_tabs'),10,3);
        add_filter('wcml_product_content_exception',array($this,'is_have_custom_product_tab'),10,2);
        add_filter('wcml_custom_box_html',array($this,'custom_box_html'),10,2);

    }

    function sync_tabs($original_product_id, $trnsl_product_id, $data = false){
        global $wc_tab_manager,$sitepress,$wpdb;

        $orig_prod_tabs = $wc_tab_manager->get_product_tabs($original_product_id);
        $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');

        if($orig_prod_tabs){
            $trnsl_product_tabs = array();
            $i = 0;
            foreach($orig_prod_tabs as $key=>$orig_prod_tab){
                switch($orig_prod_tab['type']){
                    case 'core':
                        $trnsl_product_tabs[$key] = $orig_prod_tabs[$key];
                        break;
                    case 'global':
                        $tr_tab_id = icl_object_id($orig_prod_tab['id'],'wc_product_tab',true,$lang);
                        $trnsl_product_tabs[$orig_prod_tab['type'].'_tab_'.$tr_tab_id] = array(
                            'position' => $orig_prod_tab['position'],
                            'type'     => $orig_prod_tab['type'],
                            'id'       => $tr_tab_id,
                            'name'     => get_post($tr_tab_id)->post_name
                        );
                        break;
                    case 'product':
                        if(isset($data['_product_tabs_'.$lang]['id'][$i])){
                            $tr_tab_id = icl_object_id($orig_prod_tab['id'],'wc_product_tab',false,$lang);
                            if(!is_null($tr_tab_id) || get_post_type($data['_product_tabs_'.$lang]['id'][$i]) == 'wc_product_tab'){
                                $tab_id = !is_null($tr_tab_id)?$tr_tab_id:$data['_product_tabs_'.$lang]['id'][$i];
                                //update existing tab
                                $args = array();
                                $args['post_title'] = $data['_product_tabs_'.$lang]['title'][$i];
                                $args['post_content'] = $data['_product_tabs_'.$lang]['content'][$i];
                                $wpdb->update( $wpdb->posts, $args, array( 'ID' => $tab_id ) );
                            }else{
                                //tab not exist creating new
                                $args = array();
                                $args['post_title'] = $data['_product_tabs_'.$lang]['title'][$i];
                                $args['post_content'] = $data['_product_tabs_'.$lang]['content'][$i];
                                $args['post_author'] = get_current_user_id();
                                $args['post_name'] = sanitize_title($data['_product_tabs_'.$lang]['title'][$i]);
                                $args['post_type'] = 'wc_product_tab';
                                $args['post_parent'] = $trnsl_product_id;
                                $args['post_status'] = 'publish';
                                $wpdb->insert( $wpdb->posts, $args );

                                $tab_id = $wpdb->insert_id;
                                $tab_trid = $sitepress->get_element_trid($orig_prod_tab['id'], 'post_wc_product_tab');
                                if(!$tab_trid){
                                    $sitepress->set_element_language_details($orig_prod_tab['id'], 'post_wc_product_tab', false,$sitepress->get_default_language());
                                    $tab_trid = $sitepress->get_element_trid($orig_prod_tab['id'], 'post_wc_product_tab');
                                }
                                $sitepress->set_element_language_details($tab_id, 'post_wc_product_tab', $tab_trid, $lang);
                            }

                            $trnsl_product_tabs[$orig_prod_tab['type'].'_tab_'.$tab_id] = array(
                                'position' => $orig_prod_tab['position'],
                                'type'     => $orig_prod_tab['type'],
                                'id'       => $tab_id,
                                'name'     => get_post($tab_id)->post_name
                            );
                        }
                        $i++;
                        break;
                }
            }

            update_post_meta($trnsl_product_id,'_product_tabs',$trnsl_product_tabs);
        }
    }

    function is_have_custom_product_tab($exception,$product_id){
        $prod_tabs = maybe_unserialize(get_post_meta($product_id,'_product_tabs',true));
        foreach($prod_tabs as $prod_tab){
            if($prod_tab['type'] == 'product'){
                $exception = false;
                break;
            }
        }

        return $exception;
    }


    function custom_box_html($html,$template_data){
        if($template_data['product_content'] == '_product_tabs'){
            global $wc_tab_manager;

                if($template_data['tr_product_id']){
                    $tr_prod_tabs = $wc_tab_manager->get_product_tabs($template_data['tr_product_id']);

                if(!is_array($tr_prod_tabs)){
                    return __('Please update original product','wpml-wcml');
                }

                    foreach($tr_prod_tabs as $key=>$prod_tab){
                        if($prod_tab['type'] == 'product'){
                            $template_data['tr_tabs']['ids'][] = $prod_tab['id'];
                        }
                    }
                }

                $orig_prod_tabs = $wc_tab_manager->get_product_tabs($template_data['product_id']);

                foreach($orig_prod_tabs as $key=>$prod_tab){
                    if($prod_tab['type'] == 'product'){
                        $template_data['orig_tabs']['ids'][] = $prod_tab['id'];
                    }
                }

            ob_start();

            include WCML_PLUGIN_PATH . '/compatibility/templates/wc_tab_manager_custom_box_html.php';

            $html =  ob_get_clean();

        }

        return $html;
    }

}
