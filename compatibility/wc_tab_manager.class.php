<?php

class WCML_Tab_Manager{

    function __construct(){
        add_action('wcml_after_duplicate_product_post_meta',array($this,'sync_tabs'),10,3);
        add_filter('wcml_product_content_exception',array($this,'is_have_custom_product_tab'),10,2);
        add_filter('wcml_custom_box_html',array($this,'custom_box_html'),10,3);
    }

    function sync_tabs($original_product_id, $trnsl_product_id, $data = false){
        global $wc_tab_manager,$sitepress,$wpdb,$woocommerce;

        $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');
        if(!isset($data['_product_tabs_'.$lang]) && !isset($_POST['icl_post_language']) && $_POST['icl_post_language'] != $lang){
            return;
        }

        $orig_prod_tabs = $wc_tab_manager->get_product_tabs($original_product_id);


        if($orig_prod_tabs){
            $trnsl_product_tabs = array();
            $i = 0;
            foreach($orig_prod_tabs as $key=>$orig_prod_tab){

                switch($orig_prod_tab['type']){
                    case 'core':
                        $default_language = $sitepress->get_default_language();
                        $current_language = $sitepress->get_current_language();
                        $trnsl_product_tabs[$key] = $orig_prod_tabs[$key];
                        if(isset($data['_product_tabs_'.$lang])){
                            $title = $data['_product_tabs_'.$lang]['core_title'][$orig_prod_tab['id']];
                            $heading = $data['_product_tabs_'.$lang]['core_heading'][$orig_prod_tab['id']];
                        }else if(isset($_POST['product_tab_title'][$orig_prod_tab['position']])){
                            $title = $_POST['product_tab_title'][$orig_prod_tab['position']];
                        }else if(isset($_POST['product_tab_heading'][$orig_prod_tab['position']])){
                            $heading = $_POST['product_tab_heading'][$orig_prod_tab['position']];
                        }

                        if($default_language != $lang){
                            unload_textdomain('woocommerce');
                            $sitepress->switch_lang($lang);
                            $woocommerce->load_plugin_textdomain();
                            if(!$title) $title = $orig_prod_tabs[$key]['title'];
                            $title = __( $title, 'woocommerce' );
                            if(!$heading) $heading = $orig_prod_tabs[$key]['heading'];
                            $heading = __( $heading, 'woocommerce' );
                            unload_textdomain('woocommerce');
                            $sitepress->switch_lang($current_language);
                            $woocommerce->load_plugin_textdomain();
                        }

                        $trnsl_product_tabs[$key]['title'] = $title;
                        $trnsl_product_tabs[$key]['heading'] = $heading;
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

                        $tr_tab_id = icl_object_id($orig_prod_tab['id'],'wc_product_tab',false,$lang);
                        $tab_id = false;
                        if(!is_null($tr_tab_id)){
                            $tab_id = $tr_tab_id;
                        }
                        if(isset($data['_product_tabs_'.$lang]['id'][$i])){
                            if(get_post_type($data['_product_tabs_'.$lang]['id'][$i]) == 'wc_product_tab'){
                                $tab_id = $data['_product_tabs_'.$lang]['id'][$i];
                            }
                            $title = $data['_product_tabs_'.$lang]['title'][$i];
                            $content = $data['_product_tabs_'.$lang]['content'][$i];
                        }else{
                            if($_POST['product_tab_id'][$orig_prod_tab['position']]){
                                $tab_id = $_POST['product_tab_id'][$orig_prod_tab['position']];
                            }

                            if(isset($_POST['product_tab_title'][$orig_prod_tab['position']])){
                                $title = $_POST['product_tab_title'][$orig_prod_tab['position']];
                            }else{
                                $title = '';
                            }

                            if(isset($_POST['product_tab_content'][$orig_prod_tab['position']])){
                                $content = $_POST['product_tab_content'][$orig_prod_tab['position']];
                            }else{
                                $content = '';
                            }
                        }

                        if($tab_id){
                            //update existing tab
                            $args = array();
                            $args['post_title'] = $title;
                            $args['post_content'] = $content;
                            $wpdb->update( $wpdb->posts, $args, array( 'ID' => $tab_id ) );
                        }else{
                            //tab not exist creating new
                            $args = array();
                            $args['post_title'] = $title;
                            $args['post_content'] = $content;
                            $args['post_author'] = get_current_user_id();
                            $args['post_name'] = sanitize_title($title);
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
            if(in_array($prod_tab['type'],array('product','core'))){
                $exception = false;
                break;
            }
        }

        return $exception;
    }


    function custom_box_html($html,$template_data,$lang){
        if($template_data['product_content'] == '_product_tabs'){
            global $wc_tab_manager;
                $orig_prod_tabs = $wc_tab_manager->get_product_tabs($template_data['product_id']);

                if($template_data['tr_product_id']){
                    $tr_prod_tabs = $wc_tab_manager->get_product_tabs($template_data['tr_product_id']);

                    if(!is_array($tr_prod_tabs)){
                        return __('Please update original product','wpml-wcml');
                    }

                    foreach($tr_prod_tabs as $key=>$prod_tab){
                        if(in_array($prod_tab['type'],array('product','core'))){
                            if($prod_tab['type'] == 'core'){
                                $template_data['tr_tabs'][$prod_tab['id']]['id'] = $prod_tab['id'];
                                $template_data['tr_tabs'][$prod_tab['id']]['type'] = $prod_tab['type'];
                                $template_data['tr_tabs'][$prod_tab['id']]['title'] = $prod_tab['title'];
                                $template_data['tr_tabs'][$prod_tab['id']]['heading'] = $prod_tab['heading'];
                            }else{
                                $template_data['tr_tabs'][$prod_tab['position']]['id'] = $prod_tab['id'];
                                $template_data['tr_tabs'][$prod_tab['position']]['type'] = $prod_tab['type'];
                            }
                        }
                    }
                }else{
                    global $sitepress,$woocommerce;
                    $current_language = $sitepress->get_current_language();
                    foreach($orig_prod_tabs as $key=>$prod_tab){
                        if($prod_tab['type'] == 'core'){
                            unload_textdomain('woocommerce');
                            $sitepress->switch_lang($lang);
                            $woocommerce->load_plugin_textdomain();
                            $title = __( $prod_tab['title'], 'woocommerce' );
                            if($prod_tab['title'] != $title){
                                $template_data['tr_tabs'][$prod_tab['id']]['title'] = $title;

                            }

                            $heading = __( $prod_tab['heading'], 'woocommerce' );
                            if($prod_tab['heading'] != $heading){
                                $template_data['tr_tabs'][$prod_tab['id']]['heading'] = $heading;
                            }
                            unload_textdomain('woocommerce');
                            $sitepress->switch_lang($current_language);
                            $woocommerce->load_plugin_textdomain();
                        }
                    }
                }

                foreach($orig_prod_tabs as $key=>$prod_tab){
                    if(in_array($prod_tab['type'],array('product','core'))){
                        if($prod_tab['type'] == 'core'){
                            $template_data['orig_tabs'][$prod_tab['id']]['id'] = $prod_tab['id'];
                            $template_data['orig_tabs'][$prod_tab['id']]['type'] = $prod_tab['type'];
                        }else{
                            $template_data['orig_tabs'][$prod_tab['position']]['id'] = $prod_tab['id'];
                            $template_data['orig_tabs'][$prod_tab['position']]['type'] = $prod_tab['type'];
                        }

                    }
                }



             return include WCML_PLUGIN_PATH . '/compatibility/templates/wc_tab_manager_custom_box_html.php';
        }

        return $html;
    }

}
