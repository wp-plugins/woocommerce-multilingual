<?php

class WCML_Products{

    private $not_display_fields_for_variables_product = array('_regular_price','_sale_price','_price','_min_variation_price','_max_variation_price','_min_variation_regular_price','_max_variation_regular_price','_min_variation_sale_price','_max_variation_sale_price');

    function __construct(){

        add_action('init', array($this, 'init'));

    }

    function init(){

        if(is_admin()){
            add_action('wp_ajax_wcml_update_product', array($this,'update_product_actions'));            
            
            add_filter('wpml_post_edit_page_link_to_translation',array($this,'_filter_link_to_translation'));
            add_action('admin_init', array($this, 'restrict_admin_with_redirect'));

            //quick edit hook
            //add_action( 'quick_edit_custom_box', array($this,'hide_quick_edit_link'), 10, 2 );

            add_action('admin_init', array($this, 'make_new_attributes_translatable'));

            // filters to sync variable products
            add_action('save_post', array($this, 'sync_variations'), 11, 2); // After WPML
            //when save new attachment duplicate product gallery
            add_action('wpml_media_create_duplicate_attachment', array($this,'sync_product_gallery_duplicate_attachment'),11,2);
            
            add_filter('icl_before_make_duplicate', array($this, 'icl_before_make_duplicate_actions'), 10, 2);
            
            add_filter('icl_make_duplicate', array($this, 'sync_variations_for_duplicates'), 11, 4);

            //remove media sync on product page
            add_action('admin_head', array($this,'remove_language_options'),11);

            add_action('admin_print_scripts', array($this,'preselect_product_type_in_admin_screen'), 11);

            add_filter('woocommerce_json_search_found_products', array($this, 'woocommerce_json_search_found_products'));
        }

        add_filter('wpml_link_to_translation',array($this,'_filter_link_to_translation'));

        add_filter('woocommerce_json_search_found_products', array($this, 'filter_found_products_by_language'));
        add_filter('woocommerce_upsell_crosssell_search_products', array($this, 'filter_woocommerce_upsell_crosssell_posts_by_language'));

        add_filter('icl_post_alternative_languages', array($this, 'hide_post_translation_links'));

        add_action('woocommerce_reduce_order_stock', array($this, 'sync_product_stocks'));

        add_action('updated_post_meta', array($this,'register_product_name_and_attribute_strings'), 100, 4);
        add_action('added_post_meta', array($this,'register_product_name_and_attribute_strings'), 100, 4);

        //add translation manager filters
        add_filter('wpml_tm_save_post_trid_value', array($this,'wpml_tm_save_post_trid_value'),10,2);
        add_filter('wpml_tm_save_post_lang_value', array($this,'wpml_tm_save_post_lang_value'),10,2);

        add_action('icl_pro_translation_completed', array($this,'icl_pro_translation_completed'));

        //add sitepress filters
        add_filter('wpml_save_post_trid_value',array($this,'wpml_save_post_trid_value'),10,3);
        add_filter('wpml_save_post_lang',array($this,'wpml_save_post_lang_value'),10);
        //add filter when add term on product page
        add_filter('wpml_create_term_lang',array($this,'product_page_add_language_info_to_term'));


        //save taxonomy in WPML interface
        add_action('wp_ajax_wpml_tt_save_term_translation', array($this, 'update_taxonomy_in_variations'),7);

        // Hooks for translating product attribute values
        add_filter('woocommerce_variation_option_name', array($this, 'translate_variation_term_name'));
        add_filter('woocommerce_attribute', array($this, 'translate_attribute_terms'));
        add_action('wp_ajax_woocommerce_remove_variation', array($this,'remove_variation_ajax'),9);
        //translate attribute label
        add_filter('woocommerce_attribute_label',array($this,'translated_attribute_label'),10,2);
        add_filter('woocommerce_in_cart_product_title',array($this,'translated_cart_product_title'),10,3);
        add_filter('woocommerce_checkout_product_title',array($this,'translated_checkout_product_title'),10,2);


        // cart functions
        add_action('woocommerce_get_cart_item_from_session', array($this, 'translate_cart_contents'), 10, 3);
        add_action('woocommerce_cart_loaded_from_session', array($this, 'translate_cart_subtotal'));

        if(isset($_POST['action'])){
            if(isset($_POST['product']) && $_POST['action'] == 'apply' && wp_verify_nonce($_POST['wcml_nonce'], 'wcml_test_actions')){
                if($_POST['test_action']!='to_translation'){
                    $this->product_test_content_action($_POST['product'],$_POST['test_action']);
                }
            }
        }

        if(isset($_POST['action_bottom']) && wp_verify_nonce($_POST['wcml_nonce'], 'wcml_test_actions')){
            if(isset($_POST['product']) && $_POST['action_bottom'] == 'apply'){
                if($_POST['test_action_bottom']!='to_translation'){
                    $this->product_test_content_action($_POST['product'],$_POST['test_action_bottom']);
                }
            }
        }

    }

    function get_product($product_id) {
        if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
            // WC 2.0
               return get_product( $product_id);
        } else {
            return new WC_Product($product_id);
        }
    }

    /*
     * get list of products
     * $page - number of page;
     * $limit - limit product on one page;
     * if($page = 0 && $limit=0) return all products;
     * return array;
    */
    function get_product_list($page = 1,$limit = 20){
        $args = array();
        $args['post_type'] = 'product';
        $args['post_status'] = 'publish';
        $args['suppress_filters'] = false;

        if((int)$limit>0){
            $args['posts_per_page'] = $limit;
        }else{
            $args['posts_per_page'] = -1;
        }

        if((int)$page>0){
            $args['paged'] = (int)$page;
        }

        return get_posts($args);
    }

    function preselect_product_type_in_admin_screen(){
        global $pagenow, $wpdb, $sitepress;
        if('post-new.php' == $pagenow){
            if(isset($_GET['post_type']) && $_GET['post_type'] == 'product' && isset($_GET['trid'])){
                $translations = $sitepress->get_element_translations($_GET['trid'], 'post_product_type');
                $source_lang = isset($_GET['source_lang'])?$_GET['source_lang']:$sitepress->get_default_language();
                $terms = get_the_terms($translations[$source_lang]->element_id, 'product_type');
                echo '<script type="text/javascript">';
                echo PHP_EOL . '// <![CDATA[' . PHP_EOL;
                echo 'addLoadEvent(function(){'. PHP_EOL;
                echo "jQuery('#product-type option').removeAttr('selected');" . PHP_EOL;
                echo "jQuery('#product-type option[value=\"" . $terms[0]->slug . "\"]').attr('selected', 'selected');" . PHP_EOL;
                echo '});'. PHP_EOL;
                echo PHP_EOL . '// ]]>' . PHP_EOL;
                echo '</script>';
            }
        }
    }

    /*
     * get pages count
     * $limit - limit product on one page;
     */
    function get_product_last_page($count,$limit){
        $last = ceil((int)$count/(int)$limit);
        return (int)$last;
    }

    /*
     * get products count
     */
    function get_products_count(){
        global $sitepress,$wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT count(p.id) FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = p.id WHERE p.post_type = 'product' AND p.post_status = 'publish' AND icl.element_type= 'post_product' AND icl.language_code = %s",$sitepress->get_current_language()));
        return (int)$count;
    }

    /*
     * product test content action
     * $products - array of products
     * $action - content action (duplicate or clean content)
     */
    function product_test_content_action($products,$action,$lang = false,$no_redirect = false){
        global $sitepress,$wpdb,$sitepress_settings,$current_user,$iclTranslationManagement;
        $languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();

        foreach($products as $product_id){
            foreach($languages as $language){
                if($lang && $lang != $language['code']){
                    continue;
                }
                $args = array();
                switch ($action){
                    case 'duplicate':
                        if($language['code'] != $default_language && is_null(icl_object_id($product_id, 'product', false, $language['code']))){
                            $orig_product = get_post($product_id);
                            //duplicate product
                            $args['post_title'] = $orig_product->post_title;
                            $args['post_type'] = $orig_product->post_type;
                            $args['post_content'] = $orig_product->post_content;
                            $args['post_excerpt'] = $orig_product->post_excerpt;
                            $args['post_status'] = $orig_product->post_status;
                            $args['menu_order'] = $orig_product->menu_order;
                            $args['ping_status'] = $orig_product->ping_status;
                            $args['comment_status'] = $orig_product->comment_status;
                            $product_parent = icl_object_id($orig_product->post_parent, 'product', false, $language['code']);
                            $args['post_parent'] = is_null($product_parent)?0:$product_parent;
                            $tr_product_id = wp_insert_post($args);

                            $trid = $sitepress->get_element_trid($product_id, 'post_' . $orig_product->post_type);
                            $sitepress->set_element_language_details($tr_product_id, 'post_' . $orig_product->post_type, $trid, $language['code']);

                            //create translation package
                            $translation_id = $wpdb->get_var($wpdb->prepare("
                                SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code='%s'
                            ", $trid, $language['code']));

                            $md5 = $iclTranslationManagement->post_md5($orig_product);
                            $translation_package = $iclTranslationManagement->create_translation_package($product_id);

                            get_currentuserinfo();
                            $user_id = $current_user->ID;

                            list($rid, $update) = $iclTranslationManagement->update_translation_status(array(
                                'translation_id'        => $translation_id,
                                'status'                => ICL_TM_DUPLICATE,
                                'translator_id'         => $user_id,
                                'needs_update'          => 0,
                                'md5'                   => $md5,
                                'translation_service'   => 'local',
                                'translation_package'   => serialize($translation_package)
                            ));

                            if(!$update){
                                $job_id = $iclTranslationManagement->add_translation_job($rid, $user_id , $translation_package);
                            }

                            add_post_meta($tr_product_id,'_wcml_duplicate_of_original',true,true);

                            //duplicate product attrs
                            $orig_product_attrs = $this->get_product_atributes($product_id);
                            add_post_meta($tr_product_id,'_product_attributes',$orig_product_attrs);

                            $this->duplicate_product_post_meta($product_id,$tr_product_id);

                            //sync media
                            $this->sync_thumbnail_id($product_id, $tr_product_id,$language['code']);
                            $this->sync_product_gallery($product_id);

                            //sync taxonomies
                            $this->sync_product_taxonomies($product_id,$tr_product_id,$language['code']);

                            //duplicate variations
                            $this->sync_product_variations($product_id,$tr_product_id,$language['code']);
                        }
                        break;
                    case 'clean':
                        if($language['code'] != $default_language && !is_null(icl_object_id($product_id, 'product', false, $language['code']))){
                            $tr_product_id = icl_object_id($product_id, 'product', false, $language['code']);
                            if(get_post_meta($tr_product_id,'_wcml_duplicate_of_original',true)){
                                wp_delete_post($tr_product_id,true);

                                //delete taxonomies
                                $taxonomies = get_object_taxonomies('product');
                                foreach ($taxonomies as $tax) {
                                    $terms = $wpdb->get_results($wpdb->prepare("SELECT tt.term_id FROM $wpdb->term_taxonomy AS tt LEFT JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE t.name LIKE '%s' AND tt.taxonomy = %s AND tt.count = 0", '%@'.$language['code'], $tax));
                                    foreach($terms as $term){
                                        wp_delete_term($term->term_id,$tax);
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        if(!$no_redirect){
        wp_redirect(admin_url('admin.php?page=wpml-wcml&tab=products')); exit;
    }
    }


    /*
     * get products from search
     * $title - product name
     * $category - product category
     */
    function get_products_from_filter($title,$category,$translation_status,$translation_status_lang,$page,$limit){
       global $wpdb,$sitepress;

       $current_language = $sitepress->get_current_language();
       $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts AS p";

       if($category){
           $sql .= " LEFT JOIN $wpdb->term_relationships AS tx ON tx.object_id = p.id";
       }

       $sql .= " LEFT JOIN {$wpdb->prefix}icl_translations AS t ON t.element_id = p.id";

        if(in_array($translation_status,array('not','need_update','in_progress','complete'))){
            if($translation_status_lang != 'all'){
                $sql .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt
                        ON iclt.trid=t.trid AND iclt.language_code='{$translation_status_lang}'\n";
                $sql  .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts ON iclts.translation_id=iclt.translation_id\n";
            }else{
                foreach($sitepress->get_active_languages() as $lang){
                    if($lang['code'] == $current_language) continue;
                    $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                    $sql .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt_{$tbl_alias_suffix}
                                ON iclt_{$tbl_alias_suffix}.trid=t.trid AND iclt_{$tbl_alias_suffix}.language_code='{$lang['code']}'\n";
                    $sql   .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts_{$tbl_alias_suffix}
                                ON iclts_{$tbl_alias_suffix}.translation_id=iclt_{$tbl_alias_suffix}.translation_id\n";
                }
            }
        }

       $sql .= " WHERE p.post_title LIKE '%s' AND p.post_type = 'product' AND p.post_status = 'publish' AND t.element_type= 'post_product' AND t.language_code = %s";

       if($category){
           $sql .= " AND tx.term_taxonomy_id = %d ";
       }

        if(in_array($translation_status,array('not','need_update','in_progress','complete'))){
            if($translation_status_lang != 'all'){
                if($translation_status == 'not'){
                    $sql .= " AND (iclts.status IS NULL OR iclts.status = ".ICL_TM_WAITING_FOR_TRANSLATOR." OR iclts.needs_update = 1)\n";
                }elseif($translation_status == 'need_update'){
                    $sql .= " AND iclts.needs_update = 1\n";
                }elseif($translation_status == 'in_progress'){
                    $sql .= " AND iclts.status = ".ICL_TM_IN_PROGRESS." AND iclts.needs_update = 0\n";
                }elseif($translation_status == 'complete'){
                    $sql .= " AND iclts.status = ".ICL_TM_COMPLETE." AND iclts.needs_update = 0\n";
                }
            }else{
                switch($translation_status){
                    case 'not':
                        $sql .= " AND (";
                        $wheres = array();
                        foreach($sitepress->get_active_languages() as $lang){
                            if($lang['code'] == $current_language) continue;
                            $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                            $wheres[] = "iclts_{$tbl_alias_suffix}.status IS NULL OR iclts_{$tbl_alias_suffix}.status = ".ICL_TM_WAITING_FOR_TRANSLATOR." OR iclts_{$tbl_alias_suffix}.needs_update = 1\n";
                        }
                        $sql .= join(' OR ', $wheres) . ")";
                        break;
                    case 'need_update':
                        $sql .= " AND (";
                        $wheres = array();
                        foreach($sitepress->get_active_languages() as $lang){
                            if($lang['code'] == $current_language) continue;
                            $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                            $wheres[] = "iclts_{$tbl_alias_suffix}.needs_update = 1\n";
                        }
                        $sql .= join(' OR ', $wheres) . ")";
                        break;
                    case 'in_progress':
                        $sql .= " AND (";
                        $wheres = array();
                        foreach($sitepress->get_active_languages() as $lang){
                            if($lang['code'] == $current_language) continue;
                            $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                            $wheres[] = "iclts_{$tbl_alias_suffix}.status = ".ICL_TM_IN_PROGRESS."\n";
                        }
                        $sql .= join(' OR ', $wheres)  . ")";
                        break;
                    case 'complete':
                        foreach($sitepress->get_active_languages() as $lang){
                            if($lang['code'] == $current_language) continue;
                            $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                            $sql .= " AND iclts_{$tbl_alias_suffix}.status = ".ICL_TM_COMPLETE." AND iclts_{$tbl_alias_suffix}.needs_update = 0\n";
                        }
                        break;
                }
            }
        }

        $sql .= " ORDER BY p.id DESC LIMIT ".($page-1)*$limit.",".$limit;
        

        $data = array();
        $data['products'] = $wpdb->get_results($wpdb->prepare($sql,'%'.$title.'%',$current_language,$category?$category:''));
        $data['count'] = $wpdb->get_var("SELECT FOUND_ROWS()");

       return $data;
    }

    //update product "AJAX"
    function update_product_actions() {
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'update_product_actions')){
            echo json_encode(array('error' => __('Invalid nonce', 'wpml-wcml')));
            die();
        }
        global $woocommerce_wpml,$sitepress, $wpdb,$sitepress_settings;

        //get post values
        $data = array();
        $records = $_POST['records'];
        parse_str($records, $data);

        $original_product_id = $_POST['product_id'];
        $language = $_POST['language'];
        $orig_product = get_post($original_product_id);

        if (!$data['title_' . $language]) {
            echo json_encode(array('error' => __('Title missing', 'wpml-wcml')));
            die();
        }

        $languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();
        
        $product_trid = $sitepress->get_element_trid($original_product_id, 'post_' . $orig_product->post_type);
        $tr_product_id = icl_object_id($original_product_id, 'product', false, $language);
        
        if (is_null($tr_product_id)) {
            
            //insert new post
            $args = array();
            $args['post_title'] = $data['title_' . $language];
            $args['post_type'] = $orig_product->post_type;
            $args['post_content'] = $data['content_' . $language];
            $args['post_excerpt'] = $data['excerpt_' . $language];
            $args['post_status'] = $orig_product->post_status;
            $args['menu_order'] = $orig_product->menu_order;
            $args['ping_status'] = $orig_product->ping_status;
            $args['comment_status'] = $orig_product->comment_status;
            $product_parent = icl_object_id($orig_product->post_parent, 'product', false, $language);
            $args['post_parent'] = is_null($product_parent) ? 0 : $product_parent;
            $_POST['to_lang'] = $language;
            $tr_product_id = wp_insert_post($args);

            $sitepress->set_element_language_details($tr_product_id, 'post_' . $orig_product->post_type, $product_trid, $language);

            $this->duplicate_product_post_meta($original_product_id, $tr_product_id, $data, true);
            
            
        }else{
            
            //update post
            $args = array();
            $args['ID'] = $tr_product_id;
            $args['post_title'] = $data['title_' . $language];
            $args['post_content'] = $data['content_' . $language];
            $args['post_excerpt'] = $data['excerpt_' . $language];
            $_POST['to_lang'] = $language;
            wp_update_post($args);

            $sitepress->set_element_language_details($tr_product_id, 'post_' . $orig_product->post_type, $product_trid, $language);
            $this->duplicate_product_post_meta($original_product_id, $tr_product_id, $data);
            
        }
        
        //get "_product_attributes" from original product
        $orig_product_attrs = $this->get_product_atributes($original_product_id);
        $trnsl_labels = get_option('wcml_custom_attr_translations');
        foreach ($orig_product_attrs as $key => $orig_product_attr) {
            if (isset($data[$key . '_' . $language]) && !is_array($data[$key . '_' . $language])) {
                //get translation values from $data
                $trnsl_labels[$language][$key] = $data[$key . '_name_' . $language];
                $orig_product_attrs[$key]['value'] = $data[$key . '_' . $language];
            } else {
                $orig_product_attrs[$key]['value'] = '';
            }
        }
        update_option('wcml_custom_attr_translations', $trnsl_labels);

        //update "_product_attributes"
        update_post_meta($tr_product_id, '_product_attributes', $orig_product_attrs);

        $this->sync_default_product_attr($original_product_id, $tr_product_id, $language);

        //sync media
        $this->sync_thumbnail_id($original_product_id, $tr_product_id, $language);
        $this->sync_product_gallery($original_product_id);

        //sync taxonomies
        $this->sync_product_taxonomies($original_product_id, $tr_product_id, $language);

        // synchronize post variations
        $this->sync_product_variations($original_product_id, $tr_product_id, $language, $data);


        //save prices
        if($woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
            foreach(wp_get_post_terms($tr_product_id, 'product_type', array("fields" => "names")) as $type){
                $product_type = $type;
            }

            $price_keys = array();
            $price_keys[]='regular_price';
            $price_keys[]='sale_price';

            if($product_type == 'simple'  || $product_type == 'external'){
                foreach($price_keys as $price_key){
                    update_post_meta($tr_product_id, '_' . $price_key, $data[$price_key.'_'.$language]);
                }
            }
        }        
        
        
        //save images texts
        if(isset($data['images'])){
            foreach($data['images'] as $key=>$image){
                //update image texts
                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_title' => $image['title'],
                        'post_content' => $image['description'],
                        'post_excerpt' => $image['caption']
                    ),
                    array( 'id' => $key )
                );
            }
        }

        
        $translations = $sitepress->get_element_translations($product_trid,'post_product');
        ob_clean();
        ob_start();
        $return = array();

        $this->get_translation_statuses($translations,$languages,$default_language);
        $return['status'] =  ob_get_clean();       


        ob_start();
        $this->product_images_box($tr_product_id,$language);
        $return['images'][$language] =  ob_get_clean();


        $is_variable_product = $this->is_variable_product($original_product_id);

        if($is_variable_product){
            if($woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
                ob_start();
                $this->product_variations_box($tr_product_id,$language);
                $return['variations'][$language] =  ob_get_clean();
             }
        }
        
        // no longer a duplicate
        if(!empty($data['end_duplication'][$original_product_id][$language])){
            delete_post_meta($tr_product_id, '_icl_lang_duplicate_of', $original_product_id);
            
        }
        
        echo json_encode($return);
        die();
      
    }

    function is_variable_product($product_id){
        global $wpdb;
        $get_variation_term_taxonomy_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable'");
        $is_variable_product = $wpdb->get_var($wpdb->prepare("SELECT count(object_id) FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d ",$product_id,$get_variation_term_taxonomy_id));

        return $is_variable_product;
    }


    function sync_product_taxonomies($original_product_id,$tr_product_id,$lang){
        global $sitepress;
        $taxonomies = get_object_taxonomies('product');
        foreach ($taxonomies as $tax) {
            $terms = get_the_terms($original_product_id, $tax);
            $saved_terms = array();
            if ($terms) {

                foreach ($terms as $term) {

                    if($term->taxonomy == "product_type"){
                        wp_set_object_terms($tr_product_id, $term->name, $tax);
                        continue;
                    }
                    $trid = $sitepress->get_element_trid($term->term_taxonomy_id, 'tax_' . $tax);
                    if ($trid) {
                        $translations = $sitepress->get_element_translations($trid, 'tax_' . $tax);
                        if (isset($translations[$lang])) {
                            //update tax

                            $saved_terms[] = $translations[$lang]->element_id;

                            if(!has_term($translations[$lang]->element_id,$tax,$tr_product_id)){
                                wp_set_object_terms($tr_product_id, $translations[$lang]->name, $tax,true);
                            }
                        }
                    } else {
                        wp_set_object_terms($tr_product_id, $term->name, $tax);
                    }
                }
            }
        }
    }

    function get_product_atributes($product_id){
        return get_post_meta($product_id,'_product_attributes',true);
    }

    //duplicate product post meta
    function duplicate_product_post_meta($original_product_id, $trnsl_product_id, $data = false , $add = false ){
        global $sitepress;
        $settings = $sitepress->get_settings();
        $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');

        $all_meta = get_post_meta($original_product_id);

        unset($all_meta['_thumbnail_id']);

        foreach(wp_get_post_terms($original_product_id, 'product_type', array("fields" => "names")) as $type){
            $product_type = $type;
        }

        foreach ($all_meta as $key => $meta) {
            foreach ($meta as $meta_value) {
                if($data){
                    if(isset($data[$key.'_'.$lang]) && isset($settings['translation-management']['custom_fields_translation'][$key]) && $settings['translation-management']['custom_fields_translation'][$key] == 2){
                        if($key == '_file_paths'){
                            $file_paths = explode("\n",$data[$key.'_'.$lang]);
                            $file_paths_array = array();
                            foreach($file_paths as $file_path){
                                $file_paths_array[md5($file_path)] = $file_path;
                            }
                            $meta_value = $file_paths_array;
                        }else{
                            $meta_value = $data[$key.'_'.$lang];
                        }
                    }else{
                        if($key == '_file_paths'){
                            $meta_value = maybe_unserialize($meta_value);
                        }
                    }
                    if(isset($data['regular_price_'.$lang]) && isset($data['sale_price_'.$lang]) && $product_type == 'variable'){
                       switch($key){
                           case '_min_variation_sale_price':
                               $meta_value = count(array_filter($data['sale_price_'.$lang]))?min(array_filter($data['sale_price_'.$lang])):'';
                               break;
                           case '_max_variation_sale_price':
                               $meta_value = count(array_filter($data['sale_price_'.$lang]))?max(array_filter($data['sale_price_'.$lang])):'';
                               break;
                           case '_min_variation_regular_price':
                               $meta_value = count(array_filter($data['regular_price_'.$lang]))?min(array_filter($data['regular_price_'.$lang])):'';
                               break;
                           case '_max_variation_regular_price':
                               $meta_value = count(array_filter($data['regular_price_'.$lang]))?max(array_filter($data['regular_price_'.$lang])):'';
                               break;
                           case '_min_variation_price':
                               if(count(array_filter($data['sale_price_'.$lang])) && min(array_filter($data['sale_price_'.$lang]))<min(array_filter($data['regular_price_'.$lang]))){
                                   $meta_value = min(array_filter($data['sale_price_'.$lang]));
                               }elseif(count(array_filter($data['regular_price_'.$lang]))){
                                   $meta_value = min(array_filter($data['regular_price_'.$lang]));
                               }else{
                                   $meta_value = '';
                               }
                               break;
                           case '_max_variation_price':
                               if(count(array_filter($data['sale_price_'.$lang])) && max(array_filter($data['sale_price_'.$lang]))>max(array_filter($data['regular_price_'.$lang]))){
                                   $meta_value = max(array_filter($data['sale_price_'.$lang]));
                               }elseif(count(array_filter($data['regular_price_'.$lang]))){
                                   $meta_value = max(array_filter($data['regular_price_'.$lang]));
                               }else{
                                   $meta_value = '';
                               }
                               break;
                           case '_price':
                               if(count(array_filter($data['sale_price_'.$lang])) && min(array_filter($data['sale_price_'.$lang]))<min(array_filter($data['regular_price_'.$lang]))){
                                   $meta_value = min(array_filter($data['sale_price_'.$lang]));
                               }elseif(count(array_filter($data['regular_price_'.$lang]))){
                                   $meta_value = min(array_filter($data['regular_price_'.$lang]));
                               }else{
                                   $meta_value = '';
                               }
                               break;
                       }

                    }else{
                        if($key == '_price' && isset($data['sale_price_'.$lang]) && isset($data['regular_price_'.$lang])){
                            if($data['sale_price_'.$lang]){
                                $meta_value = $data['sale_price_'.$lang];
                            }else{
                                $meta_value = $data['regular_price_'.$lang];
                            }
                        }
                    }

                    if($add){
                        add_post_meta($trnsl_product_id, $key, $meta_value, true);
                    }else{
                        update_post_meta($trnsl_product_id,$key,$meta_value);
                    }
                }else{
                    add_post_meta($trnsl_product_id, $key, $meta_value, true);
                }
            }
        }
    }

    function sync_product_gallery($product_id){
        if(!defined('WPML_MEDIA_VERSION')){
            return;
        }
        global $wpdb,$sitepress;

        $product_gallery = get_post_meta($product_id,'_product_image_gallery',true);
        $gallery_ids = explode(',',$product_gallery);

        $trid = $sitepress->get_element_trid($product_id,'post_product');
        $translations = $sitepress->get_element_translations($trid,'post_product',true);
        foreach($translations as $translation){
            $duplicated_ids = '';
            if (!$translation->original) {
                foreach($gallery_ids as $image_id){
                    $duplicated_id = icl_object_id($image_id,'attachment',false,$translation->language_code);
                    if(!is_null($duplicated_id)){
            $duplicated_ids .= $duplicated_id.',';
        }
                }
        $duplicated_ids = substr($duplicated_ids,0,strlen($duplicated_ids)-1);
                update_post_meta($translation->element_id,'_product_image_gallery',$duplicated_ids);
            }
        }
    }

    function get_translation_flags($active_languages,$default_language){
        foreach($active_languages as $language){
            if ($default_language != $language['code'] && (current_user_can('manage_options') || wpml_check_user_is_translator($default_language,$language['code'])) && (!isset($_POST['translation_status_lang']) || (isset($_POST['translation_status_lang']) && ($_POST['translation_status_lang'] == $language['code']) || $_POST['translation_status_lang']==''))){
                echo '<img src="'. ICL_PLUGIN_URL .'/res/flags/'. $language['code'] .'.png" width="18" height="12" class="flag_img" />';
            }
        }
    }


    function get_translation_statuses($product_translations,$active_languages,$default_language){
        global $wpdb;

        foreach ($active_languages as $language) {
            if ($default_language != $language['code'] && (current_user_can('manage_options') || wpml_check_user_is_translator($default_language,$language['code'])) && (!isset($_POST['translation_status_lang']) || (isset($_POST['translation_status_lang']) && ($_POST['translation_status_lang'] == $language['code']) || $_POST['translation_status_lang']==''))) {
                if (isset($product_translations[$language['code']])) {
                    $tr_status = $wpdb->get_row($wpdb->prepare("SELECT status,needs_update FROM " . $wpdb->prefix . "icl_translation_status WHERE translation_id = %d", $product_translations[$language['code']]->translation_id));
                        if(!$tr_status){
                                $alt = __('Not translated','wpml-wcml');
                                echo '<i title="'. $alt .'" class="stat_img icon-warning-sign"></i>';
                        }elseif($tr_status->needs_update){
		                $alt = __('Not translated - needs update','wpml-wcml');
				echo '<i title="'. $alt .'" class="stat_img icon-repeat"></i>';
			}elseif($tr_status->status != ICL_TM_COMPLETE && $tr_status->status != ICL_TM_DUPLICATE) {
				$alt = __('In progress','wpml-wcml');
				echo '<i title="'. $alt .'" class="stat_img icon-spinner"></i>';
			}elseif($tr_status->status == ICL_TM_COMPLETE || $tr_status->status == ICL_TM_DUPLICATE){
				$alt = __('Complete','wpml-wcml');
				echo '<i title="'. $alt .'" class="stat_img icon-ok"></i>';
			}
                } else {
                    $alt = __('Not translated','wpml-wcml');
                    echo '<i title="'. $alt .'" class="stat_img icon-warning-sign"></i>';
                }
            }
        }
    }

    /*
     * sync product variations
     * $product_id - original product id
     * $tr_product_id - translated product id
     * $lang - trnsl language
     * $data - array of values (when we save original product this array is empty, but when we update translation in this array we have price values and etc.)     *
     * */

    //sync product variations
    function sync_product_variations($product_id,$tr_product_id,$lang,$data = false,$trbl = false){
        global $wpdb,$sitepress,$sitepress_settings, $woocommerce_wpml;

        $is_variable_product = $this->is_variable_product($product_id);

        if($is_variable_product){
            $get_all_post_variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts
                            WHERE post_status IN ('publish','private') AND post_type = 'product_variation' AND post_parent = %d ORDER BY ID",$product_id));

            $duplicated_post_variation_ids = array();
            foreach($get_all_post_variations as $k => $post_data){
                $duplicated_post_variation_ids[] = $post_data->ID;
            }

            foreach ($get_all_post_variations as $k => $post_data) {

                // Find if this has already been duplicated
                $variation_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta AS pm
                                    JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = pm.post_id
                                    WHERE tr.element_type = 'post_product_variation' AND tr.language_code = %s AND pm.meta_key = '_wcml_duplicate_of_variation' AND pm.meta_value = %d",$lang,$post_data->ID));
                $trid = $sitepress->get_element_trid($post_data->ID, 'post_product_variation');
                if (!empty($variation_id) && !is_null($variation_id)) {
                    // Update variation
                    wp_update_post(array(
                        'ID' => $variation_id,
                        'post_author' => $post_data->post_author,
                        'post_date_gmt' => $post_data->post_date_gmt,
                        'post_content' => $post_data->post_content,
                        'post_title' => $post_data->post_title,
                        'post_excerpt' => $post_data->post_excerpt,
                        'post_status' => $post_data->post_status,
                        'comment_status' => $post_data->comment_status,
                        'ping_status' => $post_data->ping_status,
                        'post_password' => $post_data->post_password,
                        'post_name' => $post_data->post_name,
                        'to_ping' => $post_data->to_ping,
                        'pinged' => $post_data->pinged,
                        'post_modified' => $post_data->post_modified,
                        'post_modified_gmt' => $post_data->post_modified_gmt,
                        'post_content_filtered' => $post_data->post_content_filtered,
                        'post_parent' => $tr_product_id, // current post ID
                        'menu_order' => $post_data->menu_order,
                        'post_type' => $post_data->post_type,
                        'post_mime_type' => $post_data->post_mime_type,
                        'comment_count' => $post_data->comment_count
                    ));
                } else {
                    // Add new variation
                    $guid = $post_data->guid;
                    $replaced_guid = str_replace($product_id, $tr_product_id, $guid);
                    $slug = $post_data->post_name;
                    $replaced_slug = str_replace($product_id, $tr_product_id, $slug);
                    $variation_id = wp_insert_post(array(
                        'post_author' => $post_data->post_author,
                        'post_date_gmt' => $post_data->post_date_gmt,
                        'post_content' => $post_data->post_content,
                        'post_title' => $post_data->post_title,
                        'post_excerpt' => $post_data->post_excerpt,
                        'post_status' => $post_data->post_status,
                        'comment_status' => $post_data->comment_status,
                        'ping_status' => $post_data->ping_status,
                        'post_password' => $post_data->post_password,
                        'post_name' => $replaced_slug,
                        'to_ping' => $post_data->to_ping,
                        'pinged' => $post_data->pinged,
                        'post_modified' => $post_data->post_modified,
                        'post_modified_gmt' => $post_data->post_modified_gmt,
                        'post_content_filtered' => $post_data->post_content_filtered,
                        'post_parent' => $tr_product_id, // current post ID
                        'guid' => $replaced_guid,
                        'menu_order' => $post_data->menu_order,
                        'post_type' => $post_data->post_type,
                        'post_mime_type' => $post_data->post_mime_type,
                        'comment_count' => $post_data->comment_count
                            ));
                    add_post_meta($variation_id, '_wcml_duplicate_of_variation', $post_data->ID);

                    $sitepress->set_element_language_details($variation_id, 'post_product_variation', $trid, $lang);
                }

                //sync media
                $this->sync_thumbnail_id($post_data->ID,$variation_id,$lang);

                //sync file_paths
                if(!$woocommerce_wpml->settings['file_path_sync']  && isset($data['variations_file_paths'][$variation_id])){
                    $file_paths = explode("\n",$data['variations_file_paths'][$variation_id]);
                    $file_paths_array = array();
                    foreach($file_paths as $file_path){
                        $file_paths_array[md5($file_path)] = $file_path;
                    }
                    update_post_meta($variation_id,'_file_paths',$file_paths_array);
                }

            }

            $get_current_post_variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts
                            WHERE post_status IN ('publish','private') AND post_type = 'product_variation' AND post_parent = %d ORDER BY ID",$tr_product_id));

            // Delete variations that no longer exist
            foreach ($get_current_post_variations as $key=>$post_data) {
                $variation_id = get_post_meta($post_data->ID, '_wcml_duplicate_of_variation', true);
                if (!in_array($variation_id, $duplicated_post_variation_ids)) {
                    wp_delete_post($post_data->ID, true);
                    unset($get_current_post_variations[$key]);
                }
            }

            // custom fields to copy
            $cf = (array)$sitepress_settings['translation-management']['custom_fields_translation'];

            // synchronize post variations post meta
            $current_post_variation_ids = array();
            foreach($get_current_post_variations as $k => $post_data){
                $current_post_variation_ids[] = $post_data->ID;
            }
            //update product variations option
            update_option('_transient_wc_product_children_ids_'.$tr_product_id,$current_post_variation_ids);

            $original_product_attr = get_post_meta($product_id,'_product_attributes',true);
            $tr_product_attr = get_post_meta($tr_product_id,'_product_attributes',true);


            foreach($duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id){
                $get_all_post_meta = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d",$duplicated_post_variation_id));

                //delete non exists attributes
                $get_all_variation_attributes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'attribute_%%' ",$current_post_variation_ids[$dp_key]));
                foreach($get_all_variation_attributes as $variation_attribute){
                    $attribute_name = substr($variation_attribute->meta_key, 10);
                    if(!isset($original_product_attr[$attribute_name])){
                        delete_post_meta($current_post_variation_ids[$dp_key],$variation_attribute->meta_key);
                    }
                }

                foreach($get_all_post_meta as $k => $post_meta){

                    $meta_key = $post_meta->meta_key;
                    $meta_value = maybe_unserialize($post_meta->meta_value);

                    // adjust the global attribute slug in the custom field
                    $attid = null;

                    if (substr($meta_key, 0, 10) == 'attribute_') {
                        $tax = substr($meta_key, 10);

                        if (taxonomy_exists($tax)) {
                            $att_term = get_term_by('slug', $meta_value, $tax);
                            $attid = $att_term?$att_term->term_taxonomy_id:false;
                            if($attid){
                                $trid = $sitepress->get_element_trid($attid, 'tax_' . $tax);
                                if ($trid) {
                                    $translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
                                    if (isset($translations[$lang])) {
                                        $meta_value = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $wpdb->terms WHERE term_id = %s", $translations[$lang]->term_id));
                                    }else{
                                        $meta_value = $meta_value.'_'.$lang;
                                    }
                                }
                            }
                        }else{
                            if(isset($original_product_attr[$tax])){
                                if(isset($tr_product_attr[$tax])){
                                    $values_arrs = explode('|',$original_product_attr[$tax]['value']);
                                    $values_arrs_tr = explode('|',$tr_product_attr[$tax]['value']);
                                    foreach($values_arrs as $key=>$value){
                                        $value = str_replace(' ','-',trim($value));
                                        $value = lcfirst($value);
                                        if($value == $meta_value && isset($values_arrs_tr[$key])){
                                            $meta_value = trim($values_arrs_tr[$key]);
                                            $meta_value = str_replace(' ','-',$meta_value);
                                        }
                                    }
                                }else{
                                    $meta_value = $meta_value.'_'.$lang;
                                }

                            }
                        }
                    }
                    // update current post variations meta

                    if ((substr($meta_key, 0, 10) == 'attribute_' || isset($cf[$meta_key]) && $cf[$meta_key] == 1)) {
                        update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
                    }

                    //sync variation prices
                    if(($woocommerce_wpml->settings['enable_multi_currency'] == 'yes' || $trbl) && in_array($meta_key,array('_sale_price','_regular_price','_price'))){
                        if(!$trbl && $woocommerce_wpml->settings['currency_converting_option'] == '2'){
                            $meta_value = get_post_meta($current_post_variation_ids[$dp_key],$meta_key,true);
                        switch ($meta_key){
                            case '_sale_price':
                                if(isset($data['sale_price_'.$lang][$current_post_variation_ids[$dp_key]])){
                                    $meta_value =  $data['sale_price_'.$lang][$current_post_variation_ids[$dp_key]];
                                }
                                break;
                            case '_regular_price':
                                if(isset($data['regular_price_'.$lang][$current_post_variation_ids[$dp_key]])){
                                    $meta_value =  $data['regular_price_'.$lang][$current_post_variation_ids[$dp_key]];
                                }
                                break;
                            default:
                                if(isset($data['sale_price_'.$lang][$current_post_variation_ids[$dp_key]]) && !empty($data['sale_price_'.$lang][$current_post_variation_ids[$dp_key]]) && $data['sale_price_'.$lang][$current_post_variation_ids[$dp_key]]<$data['regular_price_'.$lang][$current_post_variation_ids[$dp_key]]){
                                    $meta_value = $data['sale_price_'.$lang][$current_post_variation_ids[$dp_key]];
                                }elseif(isset($data['regular_price_'.$lang][$current_post_variation_ids[$dp_key]])){

                                    $meta_value = $data['regular_price_'.$lang][$current_post_variation_ids[$dp_key]];
                                }

                                break;
                        }

                        }else{
                            $meta_value = get_post_meta($duplicated_post_variation_ids[$dp_key],$meta_key,true);
                        }

                        update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
                    }
            }

            }

        }
    }

    function sync_thumbnail_id($orig_post_id,$trnsl_post_id,$lang){
        if(defined('WPML_MEDIA_VERSION')){
            $thumbnail_id = get_post_meta($orig_post_id,'_thumbnail_id',true);
            $trnsl_thumbnail = icl_object_id($thumbnail_id,'attachment',false,$lang);
            if(!is_null($trnsl_thumbnail)){
                update_post_meta($trnsl_post_id,'_thumbnail_id',$trnsl_thumbnail);
            }else{
                update_post_meta($trnsl_post_id,'_thumbnail_id','');
            }
            update_post_meta($orig_post_id,'_wpml_media_duplicate',1);
            update_post_meta($orig_post_id,'_wpml_media_featured',1);
        }
    }


    function _filter_link_to_translation($link){
        global $id,$woocommerce_wpml;

        if(!$woocommerce_wpml->settings['trnsl_interface']){
            return $link;
        }
        if(isset($_GET['post'])){
            $prod_id = $_GET['post'];
        }else{
            $prod_id = $id;
        }

        if((isset($_GET['post_type']) && $_GET['post_type'] == 'product') || (isset($_GET['post']) && get_post_type($_GET['post']) == 'product')){
            $link = admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$prod_id);
        }
        return $link;
    }

    function restrict_admin_with_redirect() {
        global $sitepress,$pagenow,$woocommerce_wpml;

        $default_lang = $sitepress->get_default_language();
        $current_lang = $sitepress->get_current_language();
        if($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type']=='product' && $default_lang!=$current_lang){
            add_action('admin_notices', array($this, 'warn_creating_product_in_non_default_lang'));
        }


        if(($pagenow == 'post.php' && isset($_GET['post'])) || ($pagenow == 'admin.php' && isset($_GET['action']) && $_GET['action'] == 'duplicate_product' && isset($_GET['post']))){
            $prod_lang = $sitepress->get_language_for_element($_GET['post'],'post_product');
        }

        if(!$woocommerce_wpml->settings['trnsl_interface'] && $pagenow == 'post.php' && isset($_GET['post']) && $default_lang!=$prod_lang && get_post_type($_GET['post'])=='product'){
            add_action('admin_notices', array($this, 'inf_editing_product_in_non_default_lang'));
        }

        if($woocommerce_wpml->settings['trnsl_interface'] && $pagenow == 'post.php' && isset($_GET['post']) && $default_lang!=$prod_lang && get_post_type($_GET['post'])=='product'){
            if((!isset($_GET['action'])) || (isset($_GET['action']) && !in_array($_GET['action'],array('trash','delete')))){
                $prid = icl_object_id($_GET['post'],'product',true,$default_lang);
                wp_redirect(admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$prid)); exit;
            }
        }

        if ($woocommerce_wpml->settings['trnsl_interface'] && $pagenow == 'admin.php' && isset($_GET['action']) && $_GET['action'] == 'duplicate_product' && $default_lang!=$prod_lang) {
            wp_redirect(admin_url('admin.php?page=wpml-wcml&tab=products')); exit;
        }
    }

    function warn_creating_product_in_non_default_lang(){
        global $sitepress;
        $default_lang = $sitepress->get_default_language();
        $def_lang_info = $sitepress->get_language_details($default_lang);
        $message = '<div class="message error"><p>';
        $message .= __('You should not add products in other languages, as this may cause synchronization problems between product translations.');
        $message .= '</p><p>';
        $message .= sprintf(__('Please add the product in <b>%s</b> and then use the WooCommerce Multilingual admin screen to translate it.', 'wpml-wcml'), $def_lang_info['display_name']);
        $message .= '</p></div>';

        echo $message;
     }

    function inf_editing_product_in_non_default_lang(){
        $message = '<div><p class="icl_cyan_box">';
        $message .= sprintf(__('The recommended way to translate WooCommerce products is using the <b><a href="%s">WooCommerce Multilingual admin</a></b> page. Please use this page only for translating elements that are not available in the WooCommerce Multilingual products translation table.', 'wpml-wcml'), 'admin.php?page=wpml-wcml&tab=products');
        $message .= '</p></div>';

        echo $message;
     }

    //product quickedit
    function hide_quick_edit_link( $column_name, $post_type ) {
        global $sitepress;
        $def_lang = $sitepress->get_default_language();
        $current_lang = $sitepress->get_current_language();
        if($post_type == 'product' && $def_lang!=$current_lang){
            exit;
        }
    }

    /**
     * Makes all new attributes translatable.
     */
    function make_new_attributes_translatable(){
        global $sitepress;
        if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_attributes'){

            $wpml_settings = $sitepress->get_settings();

            $get_all_taxonomies = get_taxonomies();

            foreach($get_all_taxonomies as $tax_key => $taxonomy){
                $pos = strpos($taxonomy, 'pa_');

                // get only product attribute taxonomy name
                if($pos !== false){
                    $wpml_settings['taxonomies_sync_option'][$taxonomy] = 1;
                }
            }

            $sitepress->save_settings($wpml_settings);
        }
    }
    
    /**
     * Filters upsell/crosell products in the correct language.
     */
    function filter_found_products_by_language($found_products){
        global $wpdb, $sitepress;

        $current_page_language = $sitepress->get_current_language();

        foreach($found_products as $product_id => $output_v){
            $post_data = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = '$product_id' AND element_type LIKE 'post_%'");
            $product_language = $post_data->language_code;

            if($product_language !== $current_page_language){
                unset($found_products[$product_id]);
            }
        }

        return $found_products;
    }

    /**
     * Takes off translated products from the Up-sells/Cross-sells tab.
     *
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function filter_woocommerce_upsell_crosssell_posts_by_language($posts){
        global $sitepress, $wpdb;

        foreach($posts as $key => $post){
            $post_id = $posts[$key]->ID;
            $post_data = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = '$post_id'", ARRAY_A);

            if($post_data['language_code'] !== $sitepress->get_current_language()){
                unset($posts[$key]);
            }
        }

        return $posts;
    }

    /**
     * Avoids the post translation links on the product post type.
     *
     * @global type $post
     * @return type
     */
    function hide_post_translation_links($output){
        global $post;

        $post_type = get_post_type($post->ID);
        $checkout_page_id = get_option('woocommerce_checkout_page_id');

        if($post_type == 'product' || is_page($checkout_page_id)){
            $output = '';
        }

        return $output;
    }

    function sync_product_stocks($order) {
        return $this->sync_product_stocks20($order);
    }

    /*
      Only when translated products are ordered, force adjusting stock information for all translations
      When a product in the default language is ordered stocks are adjusted automatically
    */
    function sync_product_stocks20($order){
        global $sitepress;
        $order_id = $order->id;

        foreach ( $order->get_items() as $item ) {
            if (isset($item['variation_id']) && $item['variation_id']>0){
                $trid = $sitepress->get_element_trid($item['variation_id'], 'post_product_variation');
                $translations = $sitepress->get_element_translations($trid,'post_product_variation');
                $ld = $sitepress->get_element_language_details($item['variation_id'], 'post_product_variation');
            }else{
                $trid = $sitepress->get_element_trid($item['product_id'], 'post_product');
                $translations = $sitepress->get_element_translations($trid,'post_product');
                $ld = $sitepress->get_element_language_details($item['product_id'], 'post_product');
            }

            // Process for non-current languages
            foreach($translations as $translation){
                if ($ld->language_code != $translation->language_code) {

                    $_product = get_product($translation->element_id);
                // Out of stock attribute
                if ($_product->managing_stock() && !$_product->backorders_allowed() && $_product->get_total_stock()<=0){
                    $outofstock = 'outofstock';
                }else{
                    $outofstock = false;
                }

                if ( $_product && $_product->exists() && $_product->managing_stock() ) {
                    $stock          = $_product->reduce_stock($item['qty']);
                    $total_sales    = get_post_meta($_product->id, 'total_sales', true);
                    $total_sales   += $item['qty'];
                        update_post_meta($translation->element_id, 'total_sales', $total_sales);
                    }
                }

            }
        }

    }

    function register_product_name_and_attribute_strings($meta_id, $object_id, $meta_key, $_meta_value) {
        if ($meta_key == '_product_attributes' || $meta_key == 'attribute_names') {
            $array = maybe_unserialize($_meta_value);
            foreach ((array)$array as $attr_slug => $attr) {
                if (!empty($attr['value'])) {
                    $values = explode('|',$this->sanitize_cpa_values($attr['value']));
                    foreach($values as $value) {
                        icl_register_string('woocommerce',ucfirst($value).'_attribute_name',$value);
                    }
                }
            }
        }
    }

    function sanitize_cpa_values($values) {
        // Text based, separate by pipe
         $values = explode('|', esc_html(stripslashes($values)));
         $values = array_map('trim', $values);
         $values = implode('|', $values);
         return $values;
    }

    /**
     * This function synchronizes variations when we first create a duplicate
     */
/**
     * This function synchronizes variations when we first create a duplicate
     */
    function sync_variations_for_duplicates($master_post_id, $lang, $postarr, $id) {
        $this->sync_variations($id, $postarr);
    }

    /**
     * This function takes care of synchronizing variations from original to translations
     */
    function sync_variations($post_id, $post){
        global $wpdb, $pagenow, $sitepress, $sitepress_settings,$woocommerce_wpml;
        $default_language = $sitepress->get_default_language();
        $current_language = $sitepress->get_current_language();

        //trnsl_interface option
        if (!$woocommerce_wpml->settings['trnsl_interface'] && $default_language != $current_language) {
            return;
        }

        // check its a product
        $post_type = get_post_type($post_id);


        //set trid for variations
        if ($post_type == 'product_variation') {
            $var_lang = $sitepress->get_language_for_element(wp_get_post_parent_id($post_id),'post_product');
            if($var_lang == $default_language){
            $sitepress->set_element_language_details($post_id, 'post_product_variation', false, $var_lang);
        }
        }

        if ($post_type != 'product') {
            return;
        }

        // exceptions
        $ajax_call = (!empty($_POST['icl_ajx_action']) && $_POST['icl_ajx_action'] == 'make_duplicates');
        $duplicated_post_id = icl_object_id($post_id, 'product', false, $default_language);
        if (empty($duplicated_post_id) || isset($_POST['autosave'])) {
            return;
        }
        if($pagenow != 'post.php' && $pagenow != 'post-new.php' && $pagenow != 'admin.php' && !$ajax_call){
            return;
        }
        if (isset($_GET['action']) && $_GET['action'] == 'trash') {
            return;
        }

        // get language code
        $language_details = $sitepress->get_element_language_details($post_id, 'post_product');
        if ($pagenow == 'admin.php' && empty($language_details)) {
            //translation editor support: sidestep icl_translations_cache
            global $wpdb;
            $language_details = $wpdb->get_row("SELECT element_id, trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id=$post_id AND element_type = 'post_product'");
        }
        if (empty($language_details)) {
            return;
        }

        // If we reach this point, we go ahead with sync.
        // Remove filter to avoid double sync
        remove_action('save_post', array($this, 'sync_variations'), 11, 2);

                //media sync
                update_post_meta($post_id, '_wpml_media_duplicate', 1);
                update_post_meta($post_id, '_wpml_media_featured', 1);
        //sync product gallery
        $this->sync_product_gallery($duplicated_post_id);

        // pick posts to sync
        $posts = array();
        $translations = $sitepress->get_element_translations($language_details->trid, 'post_product');
        foreach ($translations as $translation) {
            if ($translation->original) {
                $duplicated_post_id = $translation->element_id;
            } else {
                $posts[$translation->element_id] = $translation;
            }
        }


        // TODO: move outside the loop all db queries on duplicated_post_id
        foreach ($posts as $post_id => $translation) {
            $lang = $translation->language_code;

            // Filter upsell products, crosell products and default attributes for translations
            $this->duplicate_product_post_meta($duplicated_post_id,$post_id);
            $original_product_upsell_ids = get_post_meta($duplicated_post_id, '_upsell_ids', TRUE);
            if(!empty($original_product_upsell_ids)){
                $unserialized_upsell_ids = maybe_unserialize($original_product_upsell_ids);

                foreach($unserialized_upsell_ids as $k => $product_id){
                    // get the correct language
                    $upsell_product_translation_id = icl_object_id($product_id, 'product', false, $lang);

                    if($upsell_product_translation_id){
                        $unserialized_upsell_ids[$k] = $upsell_product_translation_id;
                    // if it isn't translated - unset it
                    } else {
                        unset($unserialized_upsell_ids[$k]);
                    }
                }

                $data = array('meta_value' => maybe_serialize($unserialized_upsell_ids));
                $where = array('post_id' => $post_id, 'meta_key' => '_upsell_ids');
                $wpdb->update($wpdb->postmeta, $data, $where);
            }

            $original_product_crosssell_ids = get_post_meta($duplicated_post_id, '_crosssell_ids', TRUE);
            if(!empty($original_product_crosssell_ids)){
                $unserialized_crosssell_ids = maybe_unserialize($original_product_crosssell_ids);

                foreach($unserialized_crosssell_ids as $k => $product_id){
                    // get the correct language
                    $crosssell_product_translation_id = icl_object_id($product_id, 'product', false, $lang);

                    if($crosssell_product_translation_id){
                        $unserialized_crosssell_ids[$k] = $crosssell_product_translation_id;
                    // if it isn't translated - unset it
                    } else {
                        unset($unserialized_crosssell_ids[$k]);
                    }
                }

                $data = array('meta_value' => maybe_serialize($unserialized_crosssell_ids));
                $where = array('post_id' => $post_id, 'meta_key' => '_crosssell_ids');
                $wpdb->update($wpdb->postmeta, $data, $where);
            }

            $this->sync_product_taxonomies($duplicated_post_id,$post_id,$lang);

            $this->sync_default_product_attr($duplicated_post_id,$post_id,$lang);

            //synchronize term data, postmeta (Woocommerce "global" product attributes and custom attributes)

            //get "_product_attributes" from original product
            $orig_product_attrs = $this->get_product_atributes($duplicated_post_id);
            $trnsl_product_attrs = $this->get_product_atributes($post_id);
            foreach ($orig_product_attrs as $key => $orig_product_attr) {
                if(!$orig_product_attr['is_taxonomy']){
                    $orig_product_attrs[$key]['value'] = $trnsl_product_attrs[$key]['value'];
                }
            }

            update_post_meta($post_id, '_product_attributes', $orig_product_attrs);

            // synchronize post variations
                        $this->sync_product_variations($duplicated_post_id,$post_id,$lang);

        }
    }


    function sync_default_product_attr($orig_post_id,$transl_post_id,$lang){
        global $wpdb;
        $original_default_attributes = get_post_meta($orig_post_id, '_default_attributes', TRUE);
        if(!empty($original_default_attributes)){
            $unserialized_default_attributes = maybe_unserialize($original_default_attributes);
            foreach($unserialized_default_attributes as $attribute => $default_term_slug){
                // get the correct language
                if (substr($attribute, 0, 3) == 'pa_') {
                    //attr is taxonomy
                    $default_term = get_term_by('slug', $default_term_slug, $attribute);
                    $default_term_id = icl_object_id($default_term->term_id, $attribute, false, $lang);

                    if($default_term_id){
                        $default_term = get_term_by('id', $default_term_id, $attribute);
                        $unserialized_default_attributes[$attribute] = $default_term->slug;
                    } else {
                        // if it isn't translated - unset it
                        unset($unserialized_default_attributes[$attribute]);
                    }
                }else{
                    //custom attr
                    $orig_product_attributes = get_post_meta($orig_post_id, '_product_attributes', true);
                    $unserialized_orig_product_attributes = maybe_unserialize($orig_product_attributes);
                    if(isset($unserialized_orig_product_attributes[$attribute])){
                        $orig_attr_values = explode('|',$unserialized_orig_product_attributes[$attribute]['value']);
                        foreach($orig_attr_values as $key=>$orig_attr_value){
                            $orig_attr_value = str_replace(' ','-',trim($orig_attr_value));
                            $orig_attr_value = lcfirst($orig_attr_value);
                            if($orig_attr_value == $default_term_slug){
                                $tnsl_product_attributes = get_post_meta($transl_post_id, '_product_attributes', true);
                                $unserialized_tnsl_product_attributes = maybe_unserialize($tnsl_product_attributes);
                                if(isset($unserialized_tnsl_product_attributes[$attribute])){
                                    $trnsl_attr_values = explode('|',$unserialized_tnsl_product_attributes[$attribute]['value']);
                                    $trnsl_attr_value = str_replace(' ','-',trim($trnsl_attr_values[$key]));
                                    $trnsl_attr_value = lcfirst($trnsl_attr_value);
                                    $unserialized_default_attributes[$attribute] = $trnsl_attr_value;
                                }
                            }
                        }
                    }



                }
            }

            $data = array('meta_value' => maybe_serialize($unserialized_default_attributes));
            $where = array('post_id' => $transl_post_id, 'meta_key' => '_default_attributes');
            $wpdb->update($wpdb->postmeta, $data, $where);
        }
    }

    /*
     * get attribute translation
     */
    function get_custom_attribute_translation($product_id, $attribute_key, $attribute, $lang_code) {
        global $wpdb, $sitepress;
        $tr_post_id = icl_object_id($product_id, 'product', false, $lang_code);
        $transl = array();
        if ($tr_post_id) {
            if (!$attribute['is_taxonomy']) {
                $tr_attrs = get_post_meta($tr_post_id, '_product_attributes', true);

                if ($tr_attrs) {
                    foreach ($tr_attrs as $key=>$tr_attr) {
                        if ($attribute_key == $key) {
                            $transl['value'] = $tr_attr['value'];
                            $trnsl_labels = get_option('wcml_custom_attr_translations');
                            if(isset($trnsl_labels[$lang_code][$attribute_key])){
                                $transl['name'] = $trnsl_labels[$lang_code][$attribute_key];
                            }else{
                                $transl['name'] = $tr_attr['name'];
                    }
                            return $transl;
                }
                    }
                }
                return false;
            }
        }
        return false;
    }

    //get product content
    function get_product_contents($product_id){
        global $woocommerce_wpml;
        $contents = array();
        $contents[] = 'title';
        $contents[] = 'content';
        $contents[] = 'excerpt';
        $contents[] = 'images';
        if($woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
            if(!isset($product_type)){
            foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
                $product_type = $type;
            }
            }
            if($product_type == 'variable'){
                $contents[] = 'variations';
            }elseif($product_type != 'grouped'){
                $contents[] = 'sale_price';
                $contents[] = 'regular_price';
            }
        }

        global $sitepress;
        $settings = $sitepress->get_settings();

        foreach(get_post_custom_keys($product_id) as $meta_key){
            if(isset($settings['translation-management']['custom_fields_translation'][$meta_key]) && $settings['translation-management']['custom_fields_translation'][$meta_key] == 2){
                if($this->check_custom_field_is_single_value($product_id,$meta_key)){
                    if(in_array($meta_key,$this->not_display_fields_for_variables_product)){
                        continue;
                    }
                $contents[] = $meta_key;
            }
        }
        }

        return $contents;
    }

    //get product content labels
    function get_product_contents_labels($product_id){
        global $woocommerce_wpml;

        $contents = array();
        $contents[] = __('Title','wpml-wcml');
        $contents[] = __('Content','wpml-wcml');
        $contents[] = __('Excerpt','wpml-wcml');
        $contents[] = __('Images','wpml-wcml');
        if($woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
            foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
                $product_type = $type;
            }
            if($product_type == 'variable'){
                $contents[] = 'Variations';
            }elseif($product_type != 'grouped'){
                $contents[] = 'Sale price';
                $contents[] = 'Regular price';
            }
        }

        global $sitepress;
        $settings = $sitepress->get_settings();
        foreach(get_post_custom_keys($product_id) as $meta_key){
            if(isset($settings['translation-management']['custom_fields_translation'][$meta_key]) && $settings['translation-management']['custom_fields_translation'][$meta_key] == 2){
                if(in_array($meta_key,$this->not_display_fields_for_variables_product)){
                    continue;
                }

                if($this->check_custom_field_is_single_value($product_id,$meta_key)){
                $custom_key_label = str_replace('_',' ',$meta_key);
                    $contents[] = trim($custom_key_label[0]) ? ucfirst($custom_key_label) : ucfirst(substr($custom_key_label,1));
            }
        }
        }


        return $contents;
    }

    function check_custom_field_is_single_value($product_id,$meta_key){
        $meta_value = maybe_unserialize(get_post_meta($product_id,$meta_key,true));
        if(is_array($meta_value)){
            return false;
        }else{
            return true;
        }

    }

    //get product content translation
    function get_product_content_translation($product_id,$content,$lang_code){
        global $woocommerce_wpml;

        $tr_post_id = icl_object_id($product_id, 'product', false, $lang_code);

        if (is_null($tr_post_id) && (in_array($content, array('title','content','excerpt','variations','images'))))
            return false;


        switch ($content) {
            case 'title':
                $tr_post = get_post($tr_post_id);
                return $tr_post->post_title;
                break;
            case 'content':
                $tr_post = get_post($tr_post_id);
                return $tr_post->post_content;
                break;
            case 'excerpt':
                $tr_post = get_post($tr_post_id);
                return $tr_post->post_excerpt;
                break;
            case 'product_cat':
                global $wpdb,$sitepress;
                //get original categories
                $prod_terms =  get_the_terms($product_id, 'product_cat');
                if($sitepress->get_default_language() != $lang_code){
                    //get current lang categories
                    $trn_terms = $wpdb->get_results($wpdb->prepare("SELECT tt.term_taxonomy_id,tt.term_id,t.name FROM $wpdb->term_taxonomy AS tt LEFT JOIN $wpdb->terms AS t ON tt.term_id = t.term_id LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'product_cat' AND icl.element_type= 'tax_product_cat' AND icl.language_code = %s",$lang_code));
                }
                //get translated element categories
                $tr_product_terms = get_the_terms($tr_post_id, 'product_cat');

                $taxs = array();
                $taxs_all = array();
                if($tr_product_terms){
                    foreach($tr_product_terms as $product_term){
                        $term = get_term_by('id',$product_term->term_id,'product_cat');
                        $taxs['term_taxonomy_id'] = $term->term_taxonomy_id;
                        $taxs['name'] = $term->name;
                        $taxs['checked'] = true;
                        $taxs_all[$term->term_taxonomy_id] = $taxs;
                    }
                }

                if($prod_terms){
                    foreach ($prod_terms as $prod_term) {
                        $tr_cat_id = icl_object_id($prod_term->term_id, 'product_cat', false, $lang_code);

                        if($tr_cat_id && !array_key_exists($tr_cat_id,$taxs_all)){
                            $term = get_term_by('id',$tr_cat_id,'product_cat');
                            $taxs['term_taxonomy_id'] = $term->term_taxonomy_id;
                            $taxs['name'] = $term->name;
                            $taxs['checked'] = true;
                            $taxs_all[$term->term_taxonomy_id] = $taxs;
                        }
                    }
                }

                if(isset($trn_terms) && $trn_terms){
                    foreach($trn_terms as $tr_term){
                        if(!array_key_exists($tr_term->term_taxonomy_id,$taxs_all)){
                            $term = get_term_by('id',$tr_term->term_id,'product_cat');
                            $taxs['term_taxonomy_id'] = $term->term_taxonomy_id;
                            $taxs['name'] = $term->name;
                            $taxs['checked'] = false;
                            $taxs_all[$term->term_taxonomy_id] = $taxs;
                        }
                    }
                }

                return $taxs_all;
                break;
            case 'product_tag':
                $prod_terms = get_the_terms($product_id, 'product_tag');
                $tr_product_terms = get_the_terms($tr_post_id, 'product_tag');

                $taxs = array();
                $taxs_all = array();

                if($tr_product_terms){
                    foreach($tr_product_terms as $product_term){
                        $term = get_term_by('id',$product_term->term_id,'product_tag');
                        $taxs['term_taxonomy_id'] = $term->term_taxonomy_id;
                        $taxs['name'] = $term->name;
                        $taxs_all[$term->term_taxonomy_id] = $taxs;
                    }
                }


                if($prod_terms){
                    foreach ($prod_terms as $prod_term) {
                        $tr_tag_id = icl_object_id($prod_term->term_id, 'product_tag', false, $lang_code);

                        if($tr_tag_id && !array_key_exists($tr_tag_id,$taxs_all)){
                            $term = get_term_by('id',$tr_tag_id,'product_tag');
                            $taxs['term_taxonomy_id'] = $term->term_taxonomy_id;
                            $taxs['name'] = $term->name;
                            $taxs_all[$term->term_taxonomy_id] = $taxs;
                        }
                    }
                }

                return $taxs_all;
                break;
            default:
                global $wpdb,$sitepress;

                    foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
                        $product_type = $type;
                    }

                    if($content == 'regular_price'){
                        $var_key = '_regular_price';
                    }elseif($content == 'sale_price'){
                        $var_key = '_sale_price';
                    }elseif($content == 'variations_file_paths' && $product_type == 'variable' && !$woocommerce_wpml->settings['file_path_sync']){
                        $var_key =  '_file_paths';
                    }else{
                        return get_post_meta($tr_post_id,$content,true);
                    }

                    if($product_type == 'simple'  || $product_type == 'external'){
                        if($sitepress->get_default_language() != $lang_code && $woocommerce_wpml->settings['currency_converting_option'] == '1' && in_array($var_key,array('_regular_price','_sale_price'))){
                            $currency_rate = $wpdb->get_var("SELECT c.value FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $lang_code ."'");
                            if(is_null($currency_rate)){
                                return get_post_meta($product_id,$var_key,true);
                            }else{
                                return round($currency_rate*get_post_meta($product_id,$var_key,true), (int) get_option( 'woocommerce_price_num_decimals' ));
                            }

                        }else{
                        return get_post_meta($tr_post_id,$var_key,true);
                    }
                    }

                    if($product_type == 'variable'){
                        $variables = array();
                        $variables_all = array();
                        $variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'",$tr_post_id));


                        foreach($variations as $variation){
                            $flag = true;
                            if($var_key == '_file_paths'){
                                $flag = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_downloadable' AND meta_value='yes' AND post_id = %d",$variation->ID));
                            }
                            if($flag){
                            $variation_values = $wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE (meta_key = %s OR meta_key LIKE 'attribute_%%') AND post_id = %d",$var_key,$variation->ID));
                            $variables = array();
                            $variables['value'] = '';
                            $variables['label'] = '';
                            $variables['variation'] = $variation->ID;
                            foreach($variation_values as $variation_value){
                                if($variation_value->meta_key == $var_key){
                                        if($sitepress->get_default_language() != $lang_code && $woocommerce_wpml->settings['currency_converting_option'] == '1' && in_array($var_key,array('_regular_price','_sale_price'))){
                                            $currency_rate = $wpdb->get_var("SELECT c.value FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $lang_code ."'");
                                            $original_variation_id = icl_object_id($variation->ID,'product_variation',true,$sitepress->get_default_language());
                                            $original_price = get_post_meta($original_variation_id,$var_key,true);
                                            if(is_null($currency_rate)){
                                                $variables['value'] = $original_price;
                                            }else{
                                                $variables['value'] = round($currency_rate*$original_price, (int) get_option( 'woocommerce_price_num_decimals' ));
                                            }
                                        }else{
                                    $variables['value'] = $variation_value->meta_value;
                                        }
                                }else{
                                        //get attribute name
                                        $attribute = str_replace('attribute_','',$variation_value->meta_key);
                                        $tr_product_attr  = get_post_meta($tr_post_id,'_product_attributes',true);
                                        $term_name = get_term_by('slug',$variation_value->meta_value,$tr_product_attr[$attribute]['name']);

                                        if($term_name){
                                            //if attribute is taxonomy
                                            $term_name = $term_name->name;
                                        }else{
                                            //if attribute isn't taxonomy
                                            if(isset($tr_product_attr[$attribute]) && !$tr_product_attr[$attribute]['is_taxonomy']){
                                            $term_name = $variation_value->meta_value;
                                            }

                                            if(!$term_name){
                                                $label = __('Please translate all attributes','wpml-wcml');
                                                $variables['label'] .= $label.' & ';
                                                $variables['not_translated'] = true;
                                                continue;
                                            }
                                        }
                                        $variables['label'] .= urldecode($term_name).' & ';
                                }
                            }

                            $variables['label'] = substr($variables['label'],0,strlen($variables['label'])-3);                           
                            $variables_all[$variation->ID] = $variables;
                        }
                        }
                        return $variables_all;
                    }
                break;
        }
    }

    function product_variations_box($product_id, $lang, $is_duplicate_product = false)
    {
        global $sitepress, $woocommerce_wpml,$wpdb;

        $default_language = $sitepress->get_default_language();
        $template_data = array();
        $template_data['all_variations_ids'] = array();

        if ($default_language != $lang) {
            $trn_product_id = icl_object_id($product_id, 'product', false, $lang);
        }

        if ($default_language == $lang) {
            $template_data['original'] = true;
        } else {
            $template_data['original'] = false;
        }

        //regular price
        $template_data['regular_price'] = $this->get_product_content_translation($product_id, 'regular_price', $lang);

        //sale price
        $template_data['sale_price'] = $this->get_product_content_translation($product_id, 'sale_price', $lang);

        //file path
        if (!$woocommerce_wpml->settings['file_path_sync']) {
            global $wpdb;
            $is_downloable = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON p.id=pm.post_id WHERE p.post_parent = %d AND p.post_type = 'product_variation' AND pm.meta_key='_downloadable' AND pm.meta_value = 'yes'", $product_id));
            if ($is_downloable) {
                $template_data['all_file_paths'] = $this->get_product_content_translation($product_id, 'variations_file_paths', $lang);
            }
        }

        $is_product_has_variations = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'",$product_id));

        if (!$is_product_has_variations){
            $template_data['empty_variations'] = true;
        } elseif ($template_data['original'] || !is_null($trn_product_id)) {
            foreach ($template_data['regular_price'] as $key => $price) {
                $template_data['all_variations_ids'][] = $key;
            }
        } else {
            $template_data['empty_translation'] = true;
        }

        include WCML_PLUGIN_PATH . '/menu/sub/variations_box.php';
    }

    function product_images_box($product_id,$lang, $is_duplicate_product = false ) {
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
            $product_images = $this->product_images_ids($product_id);
            if (empty($product_images)) {
                $template_data['empty_images'] = true;
            } else {
                if ($default_language == $lang) {
                    $template_data['images_thumbnails'] = $product_images;
                }
                foreach ($product_images as $prod_image) {
                    $prod_image = icl_object_id($prod_image, 'attachment', false, $lang);
                    $images_texs = array();
                    if(!is_null($prod_image)){
                    $attachment_data = get_post($prod_image);
                    $images_texs['title'] = $attachment_data->post_title;
                    $images_texs['caption'] = $attachment_data->post_excerpt;
                    $images_texs['description'] = $attachment_data->post_content;
                    }
                    $template_data['images_data'][$prod_image] = $images_texs;
                }
            }
        } else {
            $template_data['empty_translation'] = true;
        }
        include WCML_PLUGIN_PATH . '/menu/sub/images_box.php';
    }

    function product_images_ids($product_id){
        global $wpdb;
        $product_images_ids = array();

        //thumbnail image
        $tmb = get_post_meta($product_id,'_thumbnail_id',true);
        if($tmb){
            $product_images_ids[] = $tmb;
        }

        //product gallery
        $product_gallery = get_post_meta($product_id,'_product_image_gallery',true);
        if($product_gallery){
            $product_gallery = explode(',',$product_gallery);
            foreach($product_gallery as $img){
                if(!in_array($img,$product_images_ids)){
                    $product_images_ids[] = $img;
                }
            }
        }

        foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
            $product_type = $type;
        }

        if(isset($product_type) && $product_type == 'variable'){
            $get_post_variations_image = $wpdb->get_col($wpdb->prepare("SELECT pm.meta_value FROM $wpdb->posts AS p
                            LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
                            WHERE pm.meta_key='_thumbnail_id' AND p.post_status IN ('publish','private') AND p.post_type = 'product_variation' AND p.post_parent = %d ORDER BY ID",$product_id));
            foreach($get_post_variations_image as $variation_image){
                if($variation_image && !in_array($variation_image,$product_images_ids)){
                    $product_images_ids[] = $variation_image;
                }
            }
        }

        return $product_images_ids;
    }

    // translation-management $trid filter
    function wpml_tm_save_post_trid_value($trid,$post_id){
        if(isset($_POST['action']) && $_POST['action'] == 'wcml_update_product'){
            global $sitepress;
            $trid = $sitepress->get_element_trid($post_id, 'post_product');
        }
        return $trid;
    }

    // translation-management $lang filter
    function wpml_tm_save_post_lang_value($lang,$post_id){
        if(isset($_POST['action']) &&  $_POST['action'] == 'wcml_update_product'){
            global $sitepress;
            $lang = $sitepress->get_language_for_element($post_id,'post_product');
        }
        return $lang;
    }

    // sitepress $trid filter
    function wpml_save_post_trid_value($trid,$post_status){
        if(isset($_POST['action']) && $_POST['action'] == 'wcml_update_product' && $post_status != 'auto-draft'){
            global $sitepress;
            $trid = $sitepress->get_element_trid($_POST['product_id'], 'post_product');
        }
        return $trid;
    }

    // sitepress $lang filter
    function wpml_save_post_lang_value($lang){
        if(isset($_POST['action']) &&  $_POST['action'] == 'wcml_update_product' && isset($_POST['to_lang'])){
            $lang = $_POST['to_lang'];
        }
        return $lang;
    }

    //update taxonomy in variations
    function update_taxonomy_in_variations(){
        global $wpdb;
        $original_element   = $_POST['translation_of'];
        $taxonomy           = $_POST['taxonomy'];
        $language           = $_POST['language'];
        $slug               = $_POST['slug'];
        $name               = $_POST['name'];
        $term_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d",$original_element));
        $original_term = get_term( $term_id, $taxonomy );
        $original_slug = $original_term->slug;
        //get variations with original slug

        $variations = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='%s' AND meta_value = '%s'",'attribute_'.$taxonomy,$original_slug));

        foreach($variations as $variation){
           //update taxonomy in translation of variation
           $trnsl_variation_id = icl_object_id($variation->post_id,'product_variation',false,$language);
           if(!is_null($trnsl_variation_id)){
               if(!$slug){
                   $slug = sanitize_title($name);
               }
                update_post_meta($trnsl_variation_id,'attribute_'.$taxonomy,$slug);
           }
        }
    }

    function product_page_add_language_info_to_term($lang){
        if(isset($_POST['action']) && $_POST['action'] == 'woocommerce_add_new_attribute'){
            global $sitepress;
            $lang = $sitepress->get_default_language();
        }
        return $lang;

    }

    /**
     * Translates custom attribute/variation title.
     *
     * @return type
     */
    function translate_variation_term_name($term){
        return  icl_t('woocommerce', $term .'_attribute_name', $term);
    }

    function translate_attribute_terms($terms){
        global $sitepress;
        // remove autop
        $terms = str_replace('<p>', '', $terms);
        $terms = str_replace('</p>', '', $terms);

        // iterate terms translating
        $terms = explode(",", $terms);
        $out = array();
        foreach ($terms as $term) {
            $term = trim($term);
            $term = icl_t('woocommerce', $term .'_attribute_name', $term);
            $out[] = $term;
        }

        return wpautop(wptexturize(implode(", ", $out)));
    }

    function sync_product_gallery_duplicate_attachment($att_id, $dup_att_id){

        $product_id = wp_get_post_parent_id($att_id);
        $post_type = get_post_type($product_id);
        if ($post_type != 'product') {
            return;
        }
        $this->sync_product_gallery($product_id);
    }

    function remove_language_options(){
        global $WPML_media,$typenow;
        if(defined('WPML_MEDIA_VERSION') && $typenow == 'product'){
            remove_action('icl_post_languages_options_after',array($WPML_media,'language_options'));
            echo '<input name="icl_duplicate_attachments" type="hidden" value="1" />';
            echo '<input name="icl_duplicate_featured_image" type="hidden" value="1" />';
        }
    }

    function icl_pro_translation_completed($new_post_id) {
        $this->sync_variations($new_post_id, get_post($new_post_id));
    }

    function translate_cart_contents($item, $values, $key) {
        if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) < 0 ) {
            // clearing subtotal triggers calculate_totals (WC 1.x)
            // for WC 2.x its done with the function below
            $_SESSION['subtotal'] = 0;
        }

        // translate the product id and product data
        $item['product_id'] = icl_object_id($item['product_id'], 'product', true);
        if ($item['variation_id']) {
            $item['variation_id'] = icl_object_id($item['variation_id'], 'product_variation', true);
        }
        $product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
        $item['data']->post->post_title = get_the_title($item['product_id']);
        return $item;
    }

    function translate_cart_subtotal($cart) {
        $cart->calculate_totals();
    }

    function remove_variation_ajax(){
        global $sitepress;
        if(isset($_POST['variation_id'])){
            $trid = $sitepress->get_element_trid($_POST['variation_id'], 'post_product_variation');
            if ($trid) {
                $translations = $sitepress->get_element_translations($trid, 'post_product_variation');
                if($translations){
                    foreach($translations as $translation){
                        if(!$translation->original){
                            wp_delete_post($translation->element_id,true);
                        }
                    }
                }
            }
        }
    }


    function translated_attribute_label($label, $name){
        global $sitepress;
        $name = woocommerce_sanitize_taxonomy_name($name);
        $lang = $sitepress->get_current_language();
        $trnsl_labels = get_option('wcml_custom_attr_translations');
        if(isset($trnsl_labels[$lang][$name])){
            return $trnsl_labels[$lang][$name];
        }

        return icl_t('WordPress','taxonomy singular name: '.$label,$label);
    }


    function translated_cart_product_title($title, $values, $cart_item_key){
        global $sitepress;
        if($values){
            $tr_product_id = icl_object_id($values['product_id'],'product',true,$sitepress->get_current_language());
            $title = get_the_title($tr_product_id);
        }
        return $title;
    }

    function translated_checkout_product_title($title,$product){
        global $sitepress;
        if(isset($product->id)){
            $tr_product_id = icl_object_id($product->id,'product',true,$sitepress->get_current_language());
            $title = get_the_title($tr_product_id);
        }
        return $title;
    }

    function icl_before_make_duplicate_actions($master_post_id, $lang){
        if(get_post_type($master_post_id)=='product'){
            $this->product_test_content_action(array($master_post_id), 'duplicate', $lang, true);    
        }
    }

    function woocommerce_json_search_found_products($found_products) {
        global $sitepress;

        $new_found_products = array();
        foreach($found_products as $post => $formatted_product_name) {
            $product_language = $sitepress->get_language_for_element($post, 'post_product');
            if($product_language == $sitepress->get_current_language()) {
                $new_found_products[$post] = $formatted_product_name;
            }
        }

        return $new_found_products;
    }

}
