<?php

class WCML_Bookings{

    function __construct(){

        add_action( 'woocommerce_bookings_after_booking_base_cost' , array( $this, 'wcml_price_field_after_booking_base_cost' ) );
        add_action( 'woocommerce_bookings_after_booking_block_cost' , array( $this, 'wcml_price_field_after_booking_block_cost' ) );
        add_action( 'woocommerce_bookings_after_display_cost' , array( $this, 'wcml_price_field_after_display_cost' ) );
        add_action( 'woocommerce_bookings_after_booking_pricing_base_cost' , array( $this, 'wcml_price_field_after_booking_pricing_base_cost' ), 10, 2 );
        add_action( 'woocommerce_bookings_after_booking_pricing_cost' , array( $this, 'wcml_price_field_after_booking_pricing_cost' ), 10, 2 );
        add_action( 'woocommerce_bookings_after_person_cost' , array( $this, 'wcml_price_field_after_person_cost' ) );
        add_action( 'woocommerce_bookings_after_person_block_cost' , array( $this, 'wcml_price_field_after_person_block_cost' ) );
        add_action( 'woocommerce_bookings_after_resource_cost' , array( $this, 'wcml_price_field_after_resource_cost' ), 10, 2 );
        add_action( 'woocommerce_bookings_after_resource_block_cost' , array( $this, 'wcml_price_field_after_resource_block_cost' ), 10, 2 );
        add_action( 'woocommerce_bookings_after_bookings_pricing' , array( $this, 'after_bookings_pricing' ) );

        add_action( 'admin_footer', array( $this, 'load_assets' ) );

        add_action( 'save_post', array( $this, 'save_custom_costs' ), 11, 2 );
        add_action( 'wcml_before_sync_product', array( $this, 'sync_booking_data' ), 10, 2 );

        add_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );

        add_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );
        add_filter( 'woocommerce_bookings_process_cost_rules_cost', array( $this, 'wc_bookings_process_cost_rules_cost' ), 10, 3 );
        add_filter( 'woocommerce_bookings_process_cost_rules_base_cost', array( $this, 'wc_bookings_process_cost_rules_base_cost' ), 10, 3 );

        add_filter( 'wcml_multi_currency_is_ajax', array( $this, 'wcml_multi_currency_is_ajax' ) );

        add_filter( 'wcml_cart_contents_not_changed', array( $this, 'filter_bundled_product_in_cart_contents' ), 10, 3 );

        add_action( 'after_woocommerce_bookings_create_booking_page', array( $this, 'booking_currency_dropdown' ) );
        add_action( 'init', array( $this, 'set_booking_currency') );
        add_action( 'wp_ajax_wcml_booking_set_currency', array( $this, 'set_booking_currency_ajax' ) );
        add_action( 'woocommerce_bookings_create_booking_page_add_order_item', array( $this, 'set_order_currency_on_create_booking_page' ) );
        add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_booking_currency_symbol' ) );
        add_filter( 'get_booking_products_args', array( $this, 'filter_get_booking_products_args' ) );
        add_filter( 'wcml_filter_currency_position', array( $this, 'create_booking_page_client_currency' ) );

        add_filter( 'wcml_client_currency', array( $this, 'create_booking_page_client_currency' ) );

        add_filter( 'wcml_custom_box_html', array( $this, 'custom_box_html'), 10, 3 );
        add_filter( 'wcml_product_content_fields', array( $this, 'product_content_fields'), 10, 2 );
        add_filter( 'wcml_product_content_fields_label', array( $this, 'product_content_fields_label'), 10, 2 );
        add_filter( 'wcml_check_is_single', array( $this, 'show_custom_blocks_for_resources_and_persons'), 10, 3 );
        add_filter( 'wcml_product_content_exception', array( $this, 'remove_custom_fields_to_translate' ), 10, 3 );
        add_filter( 'wcml_product_content_label', array( $this, 'product_content_resource_label' ), 10, 2 );
        add_action( 'wcml_update_extra_fields', array( $this, 'wcml_products_tab_sync_resources_and_persons'), 10, 3 );
    }

    function wcml_price_field_after_booking_base_cost( $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_booking_cost' );

    }

    function wcml_price_field_after_booking_block_cost( $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_booking_base_cost' );

    }

    function wcml_price_field_after_display_cost( $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_display_cost' );

    }

    function wcml_price_field_after_booking_pricing_base_cost( $pricing, $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_booking_pricing_base_cost', $pricing );

    }

    function wcml_price_field_after_booking_pricing_cost( $pricing, $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_booking_pricing_cost', $pricing );

    }

    function wcml_price_field_after_person_cost( $person_type_id ){

        $this->echo_wcml_price_field( $person_type_id, 'wcml_wc_booking_person_cost', false, false );

    }

    function wcml_price_field_after_person_block_cost( $person_type_id ){

        $this->echo_wcml_price_field( $person_type_id, 'wcml_wc_booking_person_block_cost', false, false );

    }

    function wcml_price_field_after_resource_cost( $resource_id, $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_booking_resource_cost', false, true, $resource_id );

    }

    function wcml_price_field_after_resource_block_cost( $resource_id, $post_id ){

        $this->echo_wcml_price_field( $post_id, 'wcml_wc_booking_resource_block_cost', false, true, $resource_id );

    }


    function echo_wcml_price_field( $post_id, $field, $pricing = false, $check = true, $resource_id = false ){
        global $woocommerce_wpml;

        if( ( !$check || $woocommerce_wpml->products->is_original_product( $post_id ) ) && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){

            $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

            $wc_currencies = get_woocommerce_currencies();

            echo '<div class="wcml_custom_cost_field" >';

            foreach($currencies as $currency_code => $currency){

                switch( $field ){
                    case 'wcml_wc_booking_cost':
                        woocommerce_wp_text_input( array( 'id' => 'wcml_wc_booking_cost', 'class'=>'wcml_bookings_custom_price', 'name' => 'wcml_wc_booking_cost['.$currency_code.']', 'label' => get_woocommerce_currency_symbol($currency_code), 'description' => __( 'One-off cost for the booking as a whole.', 'woocommerce-bookings' ), 'value' => get_post_meta( $post_id, '_wc_booking_cost_'.$currency_code, true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
                            'min'   => '',
                            'step' 	=> '0.01'
                        ) ) );
                        break;
                    case 'wcml_wc_booking_base_cost':
                        woocommerce_wp_text_input( array( 'id' => 'wcml_wc_booking_base_cost', 'class'=>'wcml_bookings_custom_price', 'name' => 'wcml_wc_booking_base_cost['.$currency_code.']', 'label' => get_woocommerce_currency_symbol($currency_code), 'description' => __( 'This is the cost per block booked. All other costs (for resources and persons) are added to this.', 'woocommerce-bookings' ), 'value' => get_post_meta( $post_id, '_wc_booking_base_cost_'.$currency_code, true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
                            'min'   => '',
                            'step' 	=> '0.01'
                        ) ) );
                        break;
                    case 'wcml_wc_display_cost':
                        woocommerce_wp_text_input( array( 'id' => 'wcml_wc_display_cost', 'class'=>'wcml_bookings_custom_price', 'name' => 'wcml_wc_display_cost['.$currency_code.']', 'label' => get_woocommerce_currency_symbol($currency_code), 'description' => __( 'The cost is displayed to the user on the frontend. Leave blank to have it calculated for you. If a booking has varying costs, this will be prefixed with the word "from:".', 'woocommerce-bookings' ), 'value' => get_post_meta( $post_id, '_wc_display_cost_'.$currency_code, true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
                            'min'   => '',
                            'step' 	=> '0.01'
                        ) ) );
                        break;

                    case 'wcml_wc_booking_pricing_base_cost':

                        if( isset( $pricing[ 'base_cost_'.$currency_code ] ) ){
                            $value = $pricing[ 'base_cost_'.$currency_code ];
                        }else{
                            $value = '';
                        }

                        echo '<div class="wcml_bookings_range_block" >';
                        echo '<label>'. get_woocommerce_currency_symbol($currency_code) .'</label>';
                        echo '<input type="number" step="0.01" name="wcml_wc_booking_pricing_base_cost['.$currency_code.'][]" class="wcml_bookings_custom_price" value="'. $value .'" placeholder="0" />';
                        echo '</div>';
                        break;

                    case 'wcml_wc_booking_pricing_cost':

                        if( isset( $pricing[ 'cost_'.$currency_code ] ) ){
                            $value = $pricing[ 'cost_'.$currency_code ];
                        }else{
                            $value = '';
                        }

                        echo '<div class="wcml_bookings_range_block" >';
                        echo '<label>'. get_woocommerce_currency_symbol($currency_code) .'</label>';
                        echo '<input type="number" step="0.01" name="wcml_wc_booking_pricing_cost['.$currency_code.'][]" class="wcml_bookings_custom_price" value="'. $value .'" placeholder="0" />';
                        echo '</div>';
                        break;

                    case 'wcml_wc_booking_person_cost':

                        $value = get_post_meta( $post_id, 'cost_'.$currency_code, true );

                        echo '<div class="wcml_bookings_person_block" >';
                        echo '<label>'. get_woocommerce_currency_symbol($currency_code) .'</label>';
                        echo '<input type="number" step="0.01" name="wcml_wc_booking_person_cost['.$post_id.']['.$currency_code.']" class="wcml_bookings_custom_price" value="'. $value .'" placeholder="0" />';
                        echo '</div>';
                        break;

                    case 'wcml_wc_booking_person_block_cost':

                        $value = get_post_meta( $post_id, 'block_cost_'.$currency_code, true );

                        echo '<div class="wcml_bookings_person_block" >';
                        echo '<label>'. get_woocommerce_currency_symbol($currency_code) .'</label>';
                        echo '<input type="number" step="0.01" name="wcml_wc_booking_person_block_cost['.$post_id.']['.$currency_code.']" class="wcml_bookings_custom_price" value="'. $value .'" placeholder="0" />';
                        echo '</div>';
                        break;

                    case 'wcml_wc_booking_resource_cost':

                        $resource_base_costs = maybe_unserialize( get_post_meta( $post_id, '_resource_base_costs', true ) );

                        if( isset( $resource_base_costs[ 'custom_costs' ][ $currency_code ][ $resource_id ] ) ){
                            $value = $resource_base_costs[ 'custom_costs' ][ $currency_code ][ $resource_id ];
                        }else{
                            $value = '';
                        }

                        echo '<div class="wcml_bookings_resource_block" >';
                        echo '<label>'. get_woocommerce_currency_symbol($currency_code) .'</label>';
                        echo '<input type="number" step="0.01" name="wcml_wc_booking_resource_cost['.$resource_id.']['.$currency_code.']" class="wcml_bookings_custom_price" value="'. $value .'" placeholder="0" />';
                        echo '</div>';
                        break;

                    case 'wcml_wc_booking_resource_block_cost':

                        $resource_block_costs = maybe_unserialize( get_post_meta( $post_id, '_resource_block_costs', true ) );

                        if( isset( $resource_block_costs[ 'custom_costs' ][ $currency_code ][ $resource_id ] ) ){
                            $value = $resource_block_costs[ 'custom_costs' ][ $currency_code ][ $resource_id ];
                        }else{
                            $value = '';
                        }

                        echo '<div class="wcml_bookings_resource_block" >';
                        echo '<label>'. get_woocommerce_currency_symbol($currency_code) .'</label>';
                        echo '<input type="number" step="0.01" name="wcml_wc_booking_resource_block_cost['.$resource_id.']['.$currency_code.']" class="wcml_bookings_custom_price" value="'. $value .'" placeholder="0" />';
                        echo '</div>';
                        break;

                    default:
                        break;

                }

            }

            echo '</div>';

        }
    }

    function after_bookings_pricing( $post_id ){
        global $woocommerce_wpml;

        if( $woocommerce_wpml->products->is_original_product( $post_id ) && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){

            $custom_costs_status = get_post_meta( $post_id, '_wcml_custom_costs_status', true );

            $checked = !$custom_costs_status ? 'checked="checked"' : ' ';

            echo '<div class="wcml_custom_costs">';

                echo '<input type="radio" name="_wcml_custom_costs" id="wcml_custom_costs_auto" value="0" class="wcml_custom_costs_input" '. $checked .' />';
                echo '<label for="wcml_custom_costs_auto">'. __('Calculate costs in other currencies automatically','wpml-wcml') .'</label>';

                $checked = $custom_costs_status == 1 ? 'checked="checked"' : ' ';

                echo '<input type="radio" name="_wcml_custom_costs" value="1" id="wcml_custom_costs_manually" class="wcml_custom_costs_input" '. $checked .' />';
                echo '<label for="wcml_custom_costs_manually">'. __('Set costs in other currencies manually','wpml-wcml') .'</label>';

                wp_nonce_field( 'wcml_save_custom_costs', '_wcml_custom_costs_nonce' );

            echo '</div>';
        }

    }

    function save_custom_costs( $post_id, $post ){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, '_wcml_custom_costs_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if( isset( $_POST['_wcml_custom_costs'] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_costs' ) ){

            update_post_meta( $post_id, '_wcml_custom_costs_status', $_POST['_wcml_custom_costs'] );

            if( $_POST['_wcml_custom_costs'] == 1 ){

                $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                foreach( $currencies as $code => $currency ){

                    $wc_booking_cost = $_POST[ 'wcml_wc_booking_cost' ][ $code ];
                    update_post_meta( $post_id, '_wc_booking_cost_'.$code, $wc_booking_cost  );

                    $wc_booking_base_cost = $_POST[ 'wcml_wc_booking_base_cost' ][ $code ];
                    update_post_meta( $post_id, '_wc_booking_base_cost_'.$code, $wc_booking_base_cost  );

                    $wc_display_cost = $_POST[ 'wcml_wc_display_cost' ][ $code ];
                    update_post_meta( $post_id, '_wc_display_cost_'.$code, $wc_display_cost  );

                }

                //person costs
                if( isset( $_POST[ 'wcml_wc_booking_person_cost' ] ) ) {

                    foreach ($_POST['wcml_wc_booking_person_cost'] as $person_id => $costs) {

                        foreach ($currencies as $code => $currency) {

                            $wc_booking_person_cost = $costs[$code];
                            update_post_meta($person_id, 'cost_' . $code, $wc_booking_person_cost);

                        }

                    }

                }

                if( isset( $_POST[ 'wcml_wc_booking_person_cost' ] ) ){

                    foreach( $_POST[ 'wcml_wc_booking_person_block_cost' ] as $person_id => $costs ){

                        foreach( $currencies as $code => $currency ){

                            $wc_booking_person_block_cost = $costs[ $code ];
                            update_post_meta( $person_id, 'block_cost_'.$code, $wc_booking_person_block_cost  );

                        }

                    }

                }

            }
        }



    }

    function sync_booking_data( $original_product_id, $current_product_id ){

        if( has_term( 'booking', 'product_type', $original_product_id ) ){
            global $wpdb, $sitepress, $pagenow, $iclTranslationManagement;

            // get language code
            $language_details = $sitepress->get_element_language_details( $original_product_id, 'post_product' );
            if ( $pagenow == 'admin.php' && empty( $language_details ) ) {
                //translation editor support: sidestep icl_translations_cache
                $language_details = $wpdb->get_row( $wpdb->prepare( "SELECT element_id, trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = 'post_product'", $original_product_id ) );
            }
            if ( empty( $language_details ) ) {
                return;
            }

            // pick posts to sync
            $posts = array();
            $translations = $sitepress->get_element_translations( $language_details->trid, 'post_product' );
            foreach ( $translations as $translation ) {

                if ( !$translation->original ) {
                    $posts[ $translation->element_id ] = $translation;
                }
            }

            foreach ( $posts as $post_id => $translation ) {

                $trn_lang = $sitepress->get_language_for_element( $post_id, 'post_product' );

                //sync_resources
                $this->sync_resources( $original_product_id, $post_id, $trn_lang );

                //sync_persons
                $this->sync_persons( $original_product_id, $post_id, $trn_lang );
            }

        }

    }

    function sync_resources( $original_product_id, $trnsl_product_id, $lang_code, $duplicate = true ){
        global $wpdb;

        $orig_resources = $wpdb->get_results( $wpdb->prepare( "SELECT resource_id, sort_order FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d", $original_product_id ) );

        $trnsl_product_resources = $wpdb->get_col( $wpdb->prepare( "SELECT resource_id FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d", $trnsl_product_id ) );

        foreach ($orig_resources as $resource) {

            $trns_resource_id = icl_object_id( $resource->resource_id, 'bookable_resource', false, $lang_code );

            if ( !is_null( $trns_resource_id ) && in_array( $trns_resource_id, $trnsl_product_resources ) ) {

                if ( ( $key = array_search( $trns_resource_id, $trnsl_product_resources ) ) !== false ) {

                    unset($trnsl_product_resources[$key]);

                    $wpdb->update(
                        $wpdb->prefix . 'wc_booking_relationships',
                        array(
                            'sort_order' => $resource->sort_order
                        ),
                        array(
                            'product_id' => $trnsl_product_id,
                            'resource_id' => $trns_resource_id
                        )
                    );

                    update_post_meta( $trns_resource_id, 'qty', get_post_meta( $resource->resource_id, 'qty', true ) );
                    update_post_meta( $trns_resource_id, '_wc_booking_availability', get_post_meta( $resource->resource_id, '_wc_booking_availability', true ) );

                }

            } else {

                if( $duplicate ){

                    $trns_resource_id = $this->duplicate_resource( $trnsl_product_id, $resource, $lang_code );

                }else{

                    continue;

                }


            }

        }

        foreach ($trnsl_product_resources as $trnsl_product_resource) {

            $wpdb->delete(
                $wpdb->prefix . 'wc_booking_relationships',
                array(
                    'product_id' => $trnsl_product_id,
                    'resource_id' => $trnsl_product_resource
                )
            );

            wp_delete_post( $trnsl_product_resource );

        }

    }


    function duplicate_resource( $tr_product_id, $resource, $lang_code){
        global $sitepress, $wpdb, $iclTranslationManagement;

        if( method_exists( $sitepress, 'make_duplicate' ) ){

            $trns_resource_id = $sitepress->make_duplicate( $resource->resource_id, $lang_code );

        }else{

            if ( !isset( $iclTranslationManagement ) ) {
                $iclTranslationManagement = new TranslationManagement;
            }

            $trns_resource_id = $iclTranslationManagement->make_duplicate( $resource->resource_id, $lang_code );

        }

        $wpdb->insert(
            $wpdb->prefix . 'wc_booking_relationships',
            array(
                'product_id' => $tr_product_id,
                'resource_id' => $trns_resource_id,
                'sort_order' => $resource->sort_order
            )
        );

        delete_post_meta( $trns_resource_id, '_icl_lang_duplicate_of' );

        return $trns_resource_id;
    }

    function sync_persons( $original_product_id, $tr_product_id, $lang_code, $duplicate = true ){
        global $wpdb, $woocommerce_wpml;

        $orig_persons = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'bookable_person'", $original_product_id ) );

        $trnsl_persons = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'bookable_person'", $tr_product_id ) );


        foreach ($orig_persons as $person) {

            $trnsl_person_id = icl_object_id( $person, 'bookable_person', false, $lang_code );

            if ( !is_null( $trnsl_person_id ) && in_array( $trnsl_person_id, $trnsl_persons ) ) {

                if ( ( $key = array_search( $trnsl_person_id, $trnsl_persons ) ) !== false ) {

                    unset($trnsl_persons[$key]);

                    update_post_meta( $trnsl_person_id, 'block_cost', get_post_meta( $person, 'block_cost', true ) );
                    update_post_meta( $trnsl_person_id, 'cost', get_post_meta( $person, 'cost', true ) );
                    update_post_meta( $trnsl_person_id, 'max', get_post_meta( $person, 'max', true ) );
                    update_post_meta( $trnsl_person_id, 'min', get_post_meta( $person, 'min', true ) );


                    if( get_post_meta( $person, '_wcml_custom_costs_status', true ) && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
                        $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                        foreach( $currencies as $code => $currency ){

                            update_post_meta( $trnsl_person_id, 'block_cost_'.$code, get_post_meta( $person, 'block_cost_'.$code, true ) );
                            update_post_meta( $trnsl_person_id, 'block_cost_'.$code, get_post_meta( $person, 'cost_'.$code, true ) );

                        }
                    }

                }

            }else{

                if( $duplicate ) {

                    $this->duplicate_person($tr_product_id, $person, $lang_code);

                }else{

                    continue;

                }

            }

        }

        foreach ($trnsl_persons as $trnsl_persons) {

            wp_delete_post( $trnsl_persons );

        }

    }


    function duplicate_person( $tr_product_id, $person_id, $lang_code ){
        global $sitepress, $wpdb, $iclTranslationManagement;

        if( method_exists( $sitepress, 'make_duplicate' ) ){

            $new_person_id = $sitepress->make_duplicate( $person_id, $lang_code );

        }else{

            if ( !isset( $iclTranslationManagement ) ) {
                $iclTranslationManagement = new TranslationManagement;
            }

            $new_person_id = $iclTranslationManagement->make_duplicate( $person_id, $lang_code );

        }

        $wpdb->update(
            $wpdb->posts,
            array(
                'post_parent' => $tr_product_id
            ),
            array(
                'ID' => $new_person_id
            )
        );

        delete_post_meta( $new_person_id, '_icl_lang_duplicate_of' );

        return $new_person_id;
    }

    function filter_wc_booking_cost( $check, $object_id, $meta_key, $single ){

        if( in_array( $meta_key, array( '_wc_booking_cost', '_wc_booking_base_cost', '_wc_display_cost', '_wc_booking_pricing', 'cost', 'block_cost', '_resource_base_costs', '_resource_block_costs' ) ) ){

            global $woocommerce_wpml;

            if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){

                $original_id = icl_object_id( $object_id, 'product', true, $woocommerce_wpml->products->get_original_product_language( $object_id ) );

                $cost_status = get_post_meta( $original_id, '_wcml_custom_costs_status', true );

                $currency = $woocommerce_wpml->multi_currency_support->get_client_currency();

                if ( $currency == get_option('woocommerce_currency') ){
                    return $check;
                }

                if( in_array( $meta_key, array( 'cost', 'block_cost' ) ) ) {

                    if ( get_post_type($object_id) == 'bookable_person' ) {

                        $value = get_post_meta($object_id, $meta_key . '_' . $currency, true);

                        if ( $cost_status && $value ) {

                            return $value;

                        } else {

                            remove_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );

                            $cost = get_post_meta( $object_id, $meta_key, true);

                            add_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );

                            return $woocommerce_wpml->multi_currency->convert_price_amount( $cost, $currency );
                        }

                    } else {

                        return $check;

                    }

                }

                if( in_array ( $meta_key, array( '_wc_booking_pricing', '_resource_base_costs', '_resource_block_costs' ) ) ){

                    remove_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );

                    if( $meta_key == '_wc_booking_pricing' ){

                        if( $original_id != $object_id ){
                            $value = get_post_meta( $original_id, $meta_key );
                        }else{
                            $value = $check;
                        }

                    }else{

                        $costs = maybe_unserialize( get_post_meta( $object_id, $meta_key, true ) );

                        if( !$costs ){
                            $value = $check;
                        }elseif( $cost_status && isset( $costs[ 'custom_costs' ][ $currency ] ) ){
                            $value = array( 0 => $costs[ 'custom_costs' ][ $currency ] );
                        }elseif( $cost_status && isset( $costs[ 0 ][ 'custom_costs' ][ $currency ] )){
                            $value = array( 0 => $costs[ 0 ][ 'custom_costs' ][ $currency ] );
                        }else{

                            $converted_values = array();

                            foreach( $costs as $resource_id => $cost ){
                                $converted_values[0][ $resource_id ] = $woocommerce_wpml->multi_currency->convert_price_amount( $cost, $currency );
                            }

                            $value = $converted_values;
                        }

                    }

                    add_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );

                    return $value;

                }

                $value = get_post_meta( $original_id, $meta_key.'_'.$currency, true );

                if( $cost_status &&  ( !empty($value) || ( empty($value) && $meta_key == '_wc_display_cost' ) ) ){

                    return $value;

                }else{

                    remove_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );

                    $value = get_post_meta( $original_id, $meta_key, true );

                    $value = $woocommerce_wpml->multi_currency->convert_price_amount( $value, $currency );

                    add_filter( 'get_post_metadata', array( $this, 'filter_wc_booking_cost' ), 10, 4 );

                    return $value;

                }

            }

        }

        return $check;
    }

    function update_wc_booking_costs(  $check, $object_id, $meta_key, $meta_value, $prev_value ){

        if( in_array( $meta_key, array( '_wc_booking_pricing', '_resource_base_costs', '_resource_block_costs' ) ) ){

            global $woocommerce_wpml;

            remove_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );

            $nonce = filter_input( INPUT_POST, '_wcml_custom_costs_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

            if( isset( $_POST['_wcml_custom_costs'] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_costs' ) && $_POST['_wcml_custom_costs'] == 1 ) {

                $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                $updated_meta = array();

                if( $meta_key == '_wc_booking_pricing' ){

                    foreach (maybe_unserialize($meta_value) as $key => $prices) {

                        $updated_meta[ $key ] = $prices;

                        foreach ($currencies as $code => $currency) {

                            $updated_meta[ $key ][ 'base_cost_'.$code ] = $_POST[ 'wcml_wc_booking_pricing_base_cost' ][ $code ][ $key ];
                            $updated_meta[ $key ][ 'cost_'.$code ] = $_POST[ 'wcml_wc_booking_pricing_cost' ][ $code ][ $key ];

                        }

                    }

                    update_post_meta( $object_id, '_wc_booking_pricing', $updated_meta );

                }elseif( $meta_key == '_resource_base_costs' ){

                    if( isset( $_POST[ 'wcml_wc_booking_resource_cost' ] ) ) {

                        $updated_meta = $meta_value;

                        $wc_booking_resource_costs = array();

                        foreach ( $_POST['wcml_wc_booking_resource_cost'] as $resource_id => $costs) {

                            foreach ($currencies as $code => $currency) {

                                $wc_booking_resource_costs[ $code ][ $resource_id ] = $costs[ $code ];

                            }

                        }

                        $updated_meta[ 'custom_costs' ] = $wc_booking_resource_costs;

                        update_post_meta( $object_id, '_resource_base_costs', $updated_meta );

                        $this->sync_resource_costs_with_translations( $object_id, $meta_key );

                    }

                }elseif( $meta_key == '_resource_block_costs' ){

                    if( isset( $_POST[ 'wcml_wc_booking_resource_block_cost' ] ) ){

                        $updated_meta = $meta_value;

                        $wc_booking_resource_block_costs = array();

                        foreach( $_POST[ 'wcml_wc_booking_resource_block_cost' ] as $resource_id => $costs ){

                            foreach( $currencies as $code => $currency ){

                                $wc_booking_resource_block_costs[ $code ][ $resource_id ] = $costs[ $code ];

                            }

                        }

                        $updated_meta[ 'custom_costs' ] = $wc_booking_resource_block_costs;

                        update_post_meta( $object_id, '_resource_block_costs', $updated_meta );

                        $this->sync_resource_costs_with_translations( $object_id, $meta_key );

                    }

                }

                add_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );

                return true;

            }elseif(in_array( $meta_key, array( '_resource_base_costs', '_resource_block_costs' ) ) ){

                $return = $this->sync_resource_costs_with_translations( $object_id, $meta_key, $check );

                add_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );

                return $return;

            }else{

                add_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );

                return $check;

            }

        }else{

            return $check;

        }

    }

    function sync_resource_costs_with_translations( $object_id, $meta_key, $check = false ){
        global $sitepress,$woocommerce_wpml;

        $original_product_id = icl_object_id( $object_id, 'product', true, $woocommerce_wpml->products->get_original_product_language( $object_id ) );

        if( $object_id == $original_product_id ){

            $trid = $sitepress->get_element_trid( $object_id, 'post_product' );
            $translations = $sitepress->get_element_translations( $trid, 'post_product' );

            foreach ( $translations as $translation ) {

                if ( !$translation->original ) {

                    $this->sync_resource_costs( $original_product_id, $translation->element_id, $meta_key, $translation->language_code );

                }
            }

            return $check;

        }else{

            $language_code = $sitepress->get_language_for_element( $object_id, 'post_product' );

            $this->sync_resource_costs( $original_product_id, $object_id, $meta_key, $language_code );

            return true;

        }

    }

    function sync_resource_costs( $original_product_id, $object_id, $meta_key, $language_code ){

        $original_costs = maybe_unserialize( get_post_meta( $original_product_id, $meta_key, true ) );

        $wc_booking_resource_costs = array();

        foreach ( $original_costs as $resource_id => $costs ) {

            if ( $resource_id == 'custom_costs' && isset($costs[ 'custom_costs']) ){

                foreach ( $costs[ 'custom_costs'] as $code => $currencies ) {

                    foreach( $currencies as $custom_costs_resource_id => $custom_cost ){

                        $trns_resource_id = icl_object_id( $custom_costs_resource_id, 'bookable_resource', true, $language_code );

                        $wc_booking_resource_costs[ 'custom_costs' ][ $code ][ $trns_resource_id ] = $custom_cost;

                    }

                }

            }else{

                $trns_resource_id = icl_object_id( $resource_id, 'bookable_resource', true, $language_code );

                $wc_booking_resource_costs[ $trns_resource_id ] = $costs;

            }

        }

        update_post_meta( $object_id, $meta_key, $wc_booking_resource_costs );

    }


    function wc_bookings_process_cost_rules_cost( $cost, $fields, $key ){
        return $this->filter_pricing_cost( $cost, $fields, 'cost_', $key );
    }

    function wc_bookings_process_cost_rules_base_cost( $base_cost, $fields, $key ){
        return $this->filter_pricing_cost( $base_cost, $fields, 'base_cost_', $key );
    }

    function filter_pricing_cost( $cost, $fields, $name, $key ){
        global $woocommerce_wpml, $product;

        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){

            $currency = $woocommerce_wpml->multi_currency_support->get_client_currency();

            if ( $currency == get_option('woocommerce_currency') ) {
                return $cost;
            }

            if( isset( $_POST[ 'form' ] ) ){
                parse_str( $_POST[ 'form' ], $posted );

                $booking_id = $posted[ 'add-to-cart' ];

            }elseif( isset( $_POST[ 'add-to-cart' ] ) ){

                $booking_id = $_POST[ 'add-to-cart' ];

            }

            if( isset( $booking_id ) ){
                $original_id = icl_object_id( $booking_id, 'product', true, $woocommerce_wpml->products->get_original_product_language( $booking_id ) );

                if( $booking_id != $original_id ){
                    $fields = maybe_unserialize( get_post_meta( $original_id, '_wc_booking_pricing', true ) );
                    $fields = $fields[$key];
                }
            }

            if( isset( $fields[ $name.$currency ] ) ){
                return $fields[ $name.$currency ];
            }else{
                return $woocommerce_wpml->multi_currency->convert_price_amount( $cost, $currency );
            }

        }

        return $cost;

    }

    function load_assets( ){
        global $pagenow, $woocommerce_wpml;

        if( $pagenow == 'post.php' || $pagenow == 'post-new.php' ){

            wp_register_style( 'wcml-bookings-css', WCML_PLUGIN_URL . '/compatibility/assets/css/wcml-bookings.css', array(), WCML_VERSION );
            wp_enqueue_style( 'wcml-bookings-css' );

            wp_register_script( 'wcml-bookings-js' , WCML_PLUGIN_URL . '/compatibility/assets/js/wcml-bookings.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script( 'wcml-bookings-js' );
            wp_localize_script( 'wcml-bookings-js', 'lock_fields',  ( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) == 'product' && !$woocommerce_wpml->products->is_original_product( $_GET[ 'post' ] ) ) ||
            ( $pagenow == 'post-new.php' && isset( $_GET[ 'source_lang' ] ) ) ? 1 : false );
        }

    }


    function wcml_multi_currency_is_ajax( $actions ){

        $actions[] = 'wc_bookings_calculate_costs';

        return $actions;
    }

    function filter_bundled_product_in_cart_contents( $cart_item, $key, $current_language ){

        if( $cart_item[ 'data' ] instanceof WC_Product_Booking ){
            global $woocommerce_wpml;

            $current_id = icl_object_id( $cart_item[ 'data' ]->id, 'product', true, $current_language );
            $cart_product_id = $cart_item['data']->id;

            if( $current_id != $cart_product_id ) {

                $cart_item['data'] = new WC_Product_Booking( $current_id );

            }

            if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT || $current_id != $cart_product_id ){

                $booking_info = array(
                    'wc_bookings_field_start_date_year' => $cart_item[ 'booking' ][ '_year' ],
                    'wc_bookings_field_start_date_month' => $cart_item[ 'booking' ][ '_month' ],
                    'wc_bookings_field_start_date_day' => $cart_item[ 'booking' ][ '_day' ],
                    'add-to-cart' => $current_id,
                    '_persons' => isset( $cart_item[ 'booking' ][ '_persons' ] ) ? isset( $cart_item[ 'booking' ][ '_persons' ] ) : array()
                );

                if( isset( $cart_item[ 'booking' ][ '_resource_id' ]  ) ){
                    $booking_info[ 'wc_bookings_field_resource' ] = $cart_item[ 'booking' ][ '_resource_id' ];
                }

                if( isset( $cart_item[ 'booking' ][ '_duration' ]  ) ){
                    $booking_info[ 'wc_bookings_field_duration' ] = $cart_item[ 'booking' ][ '_duration' ];
                }

                if( isset( $cart_item[ 'booking' ][ '_time' ]  ) ){
                    $booking_info[ 'wc_bookings_field_start_date_time' ] = $cart_item[ 'booking' ][ '_time' ];
                }

                $booking_form = new WC_Booking_Form( wc_get_product( $current_id ) );

                $prod_qty = get_post_meta( $current_id, '_wc_booking_qty', true );
                update_post_meta( $current_id, '_wc_booking_qty', intval( $prod_qty + $cart_item[ 'booking' ][ '_qty' ] ) );
                $cost = $booking_form->calculate_booking_cost( $booking_info );
                update_post_meta( $current_id, '_wc_booking_qty', $prod_qty );

                if( !is_wp_error( $cost ) ){
                    $cart_item[ 'data' ]->set_price( $cost );
                }
            }

        }

        return $cart_item;

    }

    function booking_currency_dropdown(){
        global $woocommerce_wpml, $sitepress;

        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
            $current_booking_currency = $this->get_cookie_booking_currency();

            $wc_currencies = get_woocommerce_currencies();
            $order_currencies = $woocommerce_wpml->multi_currency->get_orders_currencies();
            ?>
            <tr valign="top">
                <th scope="row"><?php _e( 'Booking currency', 'wpml-wcml' ); ?></th>
                <td>
                    <select id="dropdown_booking_currency">

                        <?php foreach($order_currencies as $currency => $count ): ?>

                            <option value="<?php echo $currency ?>" <?php echo $current_booking_currency == $currency ? 'selected="selected"':''; ?>><?php echo $wc_currencies[$currency]; ?></option>

                        <?php endforeach; ?>

                    </select>
                </td>
            </tr>

            <?php

            $wcml_booking_set_currency_nonce = wp_create_nonce( 'booking_set_currency' );

            wc_enqueue_js( "

            jQuery(document).on('change', '#dropdown_booking_currency', function(){
               jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'wcml_booking_set_currency',
                        currency: jQuery('#dropdown_booking_currency').val(),
                        wcml_nonce: '".$wcml_booking_set_currency_nonce."'
                    },
                    success: function( response ){
                        if(typeof response.error !== 'undefined'){
                            alert(response.error);
                        }else{
                           window.location = window.location.href;
                        }
                    }
                })
            });
        ");

        }

    }

    function set_booking_currency_ajax(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'booking_set_currency')){
            echo json_encode(array('error' => __('Invalid nonce', 'wpml-wcml')));
            die();
        }

        $this->set_booking_currency(filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ));

        die();
    }

    function set_booking_currency( $currency_code = false ){

        if( !isset( $_COOKIE [ '_wcml_booking_currency' ]) && !headers_sent()) {
            global $woocommerce_wpml;

            $currency_code = get_woocommerce_currency();

            if ( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
                $order_currencies = $woocommerce_wpml->multi_currency->get_orders_currencies();

                if (!isset($order_currencies[$currency_code])) {
                    foreach ($order_currencies as $currency_code => $count) {
                        $currency_code = $currency_code;
                        break;
                    }
                }
            }
        }

        if( $currency_code ){
            setcookie('_wcml_booking_currency', $currency_code , time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
        }

    }

    function get_cookie_booking_currency(){

        if( isset( $_COOKIE [ '_wcml_booking_currency' ] ) ){
            $currency = $_COOKIE[ '_wcml_booking_currency' ];
        }else{
            $currency = get_woocommerce_currency();
        }

        return $currency;
    }

    function filter_booking_currency_symbol( $currency ){
        global $pagenow;

        remove_filter( 'woocommerce_currency_symbol', array( $this, 'filter_booking_currency_symbol' ) );
        if( isset( $_COOKIE [ '_wcml_booking_currency' ] ) && $pagenow == 'edit.php' && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'create_booking' ){
            $currency = get_woocommerce_currency_symbol( $_COOKIE [ '_wcml_booking_currency' ] );
        }
        add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_booking_currency_symbol' ) );

        return $currency;
    }

    function create_booking_page_client_currency( $currency ){
        global $pagenow;

        if( wpml_is_ajax() && isset( $_POST[ 'form' ] ) ){
            parse_str( $_POST[ 'form' ], $posted );
        }

        if( ( $pagenow == 'edit.php' && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'create_booking' ) || ( isset( $posted[ '_wp_http_referer' ] ) && strpos( $posted[ '_wp_http_referer' ], 'page=create_booking' ) !== false ) ){
            $currency = $this->get_cookie_booking_currency();
        }

        return $currency;
    }

    function set_order_currency_on_create_booking_page( $order_id ){
        global $sitepress;

        update_post_meta( $order_id, '_order_currency', $this->get_cookie_booking_currency() );

        update_post_meta( $order_id, 'wpml_language', $sitepress->get_current_language() );

    }

    function filter_get_booking_products_args( $args ){
        if( isset( $args['suppress_filters'] ) ){
            $args['suppress_filters'] = false;
        }
        return $args;
    }

    function custom_box_html( $html, $template_data, $lang ){

        if( in_array( $template_data[ 'product_content' ], array( 'wc_booking_resources', 'wc_booking_persons' ) ) ){

            switch( $template_data[ 'product_content' ] ){
                case 'wc_booking_resources':

                    $resources = array();

                    foreach( maybe_unserialize( get_post_meta( $template_data[ 'product_id' ], '_resource_base_costs', true ) ) as $resource_id => $cost ){

                        if( $resource_id == 'custom_costs' ) continue;

                        $trns_resource_id = icl_object_id( $resource_id, 'bookable_resource', false, $template_data[ 'lang' ] );

                        if( !empty( $trns_resource_id ) && $template_data[ 'translation_exist' ] ){
                            $resources[ $resource_id ] = $trns_resource_id;
                        }else{
                            $resources[ $resource_id ] = false;
                        }

                    }
                    $template_data[ 'resources' ] = $resources;

                    break;
                case 'wc_booking_persons':
                    global $wpdb;

                    $persons = array();

                    $original_persons = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'bookable_person' AND post_status = 'publish'", $template_data[ 'product_id' ] ) );

                    foreach( $original_persons as $person ){

                        $trnsl_person_id = icl_object_id( $person, 'bookable_person', false, $template_data[ 'lang' ] );

                        if( !empty( $trnsl_person_id ) && $template_data[ 'translation_exist' ] ){
                            $persons[ $person ] = $trnsl_person_id;
                        }else{
                            $persons[ $person ] = false;
                        }

                    }

                    $template_data[ 'persons' ] = $persons;

                    break;
            }

            return include WCML_PLUGIN_PATH . '/compatibility/templates/wc_bookings_custom_box_html.php';

        }

        return $html;
    }

    function product_content_fields( $fields, $product_id ){

        return $this->product_content_fields_data( $fields, $product_id );

    }

    function product_content_fields_label( $fields, $product_id ){

        return $this->product_content_fields_data( $fields, $product_id, 'label' );

    }

    function product_content_fields_data( $fields, $product_id, $data = false ){

        if( get_post_meta( $product_id, '_resource_base_costs', true ) ){
            if( $data == 'label' ){
                $fields[] = __( 'Resources', 'wpml-wcml' );
            }else{
                $fields[] = 'wc_booking_resources';
            }
        }

        if( has_term( 'booking', 'product_type', $product_id ) ){
            global $wpdb;

            $persons = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'bookable_person'", $product_id ) );

            if( $persons ){
                if( $data == 'label' ){
                    $fields[] = __( 'Person types', 'wpml-wcml' );
                }else{
                    $fields[] = 'wc_booking_persons';
                }
            }

        }

        return  $fields;

    }

    function show_custom_blocks_for_resources_and_persons( $check, $product_id, $product_content ){
        if( in_array( $product_content, array( 'wc_booking_resources', 'wc_booking_persons' ) ) ){
            return false;
        }
        return $check;
    }

    function remove_custom_fields_to_translate( $exception, $product_id, $meta_key ){
        if( in_array( $meta_key, array( '_resource_base_costs', '_resource_block_costs' ) ) ){
            $exception = true;
        }
        return $exception;
    }

    function product_content_resource_label( $meta_key, $product_id ){
        if ($meta_key == '_wc_booking_resouce_label'){
            return __( 'Resources label', 'wpml-wcml' );
        }
        return $meta_key;
    }

    function wcml_products_tab_sync_resources_and_persons( $tr_product_id, $data, $language ){
        global $wpdb, $woocommerce_wpml;

        //sync resources
        if( isset( $data[ 'wc_booking_resources_'.$language ] ) ){

            $original_product_lang = $woocommerce_wpml->products->get_original_product_language( $tr_product_id );
            $original_product_id = icl_object_id( $tr_product_id, 'product', true, $original_product_lang );

            foreach( $data[ 'wc_booking_resources_'.$language ][ 'id' ] as $key => $resource_id ){

                if( !$resource_id ){

                    $resource_id = icl_object_id( $data[ 'wc_booking_resources_'.$language ][ 'orig_id' ][ $key ], 'bookable_resource', false, $language );

                    $orig_resource = $wpdb->get_row( $wpdb->prepare( "SELECT resource_id, sort_order FROM {$wpdb->prefix}wc_booking_relationships WHERE resource_id = %d AND product_id = %d", $data[ 'wc_booking_resources_'.$language ][ 'orig_id' ][ $key ], $original_product_id ), OBJECT );

                    if( is_null( $resource_id ) ){

                        if( $orig_resource ) {
                            $resource_id = $this->duplicate_resource($tr_product_id, $orig_resource, $language);
                        }else{
                            continue;
                        }

                    }else{
                        //update_relationship

                        $exist = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}wc_booking_relationships WHERE resource_id = %d AND product_id = %d", $resource_id, $tr_product_id ) );

                        if( !$exist ){

                            $wpdb->insert(
                                $wpdb->prefix . 'wc_booking_relationships',
                                array(
                                    'product_id' => $tr_product_id,
                                    'resource_id' => $resource_id,
                                    'sort_order' => $orig_resource->sort_order
                                )
                            );

                        }

                    }

                }

                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_title' => $data[ 'wc_booking_resources_'.$language ][ 'title' ][ $key ]
                    ),
                    array(
                        'ID' => $resource_id
                    )
                );

            }

            //sync resources data
            $this->sync_resources( $original_product_id, $tr_product_id, $language, false );

            remove_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );
            $this->sync_resource_costs( $original_product_id, $tr_product_id, '_resource_base_costs', $language );
            $this->sync_resource_costs( $original_product_id, $tr_product_id, '_resource_block_costs', $language );
            add_filter( 'update_post_metadata', array( $this, 'update_wc_booking_costs' ), 10, 5 );

        }


        //sync persons
        if( isset( $data[ 'wc_booking_persons_'.$language ] ) ){

            $original_product_lang = $woocommerce_wpml->products->get_original_product_language( $tr_product_id );
            $original_product_id = icl_object_id( $tr_product_id, 'product', true, $original_product_lang );

            foreach( $data[ 'wc_booking_persons_'.$language ][ 'id' ] as $key => $person_id ) {

                if ( !$person_id ) {

                    $person_id = icl_object_id( $data[ 'wc_booking_persons_'.$language ][ 'orig_id' ][ $key ], 'bookable_person', false, $language );

                    if( is_null( $person_id ) ){

                        $person_id = $this->duplicate_person( $tr_product_id, $data['wc_booking_persons_' . $language]['orig_id'][$key], $language);

                    }else{

                        $wpdb->update(
                            $wpdb->posts,
                            array(
                                'post_parent' => $tr_product_id
                            ),
                            array(
                                'ID' => $person_id
                            )
                        );

                    }

                }

                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_title' => $data[ 'wc_booking_persons_' . $language ][ 'title' ][ $key ],
                        'post_excerpt' => $data[ 'wc_booking_persons_' . $language ][ 'description' ][ $key ],
                    ),
                    array(
                        'ID' => $person_id
                    )
                );

            }

            //sync persons data
            $this->sync_persons(  $original_product_id, $tr_product_id, $language, false );

        }

    }

}