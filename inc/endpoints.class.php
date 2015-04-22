<?php

class WCML_Endpoints{

    var $endpoints_strings = array();

    function __construct(){

        //endpoints hooks
        $this->register_endpoints_translations();
        add_action( 'icl_ajx_custom_call', array( $this, 'rewrite_rule_endpoints' ), 11, 2 );
        add_action( 'woocommerce_update_options', array( $this, 'update_endpoints_rules' ) );
        add_filter( 'pre_update_option_rewrite_rules', array( $this, 'update_rewrite_rules' ), 100, 2 );

        add_filter( 'page_link', array( $this, 'endpoint_permalink_filter' ), 10, 2 ); //after WPML

    }

    function register_endpoints_translations(){
        if( !class_exists( 'woocommerce' ) || !defined( 'ICL_SITEPRESS_VERSION' ) || ICL_PLUGIN_INACTIVE || version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) return false;

        $wc_vars = WC()->query->query_vars;

        if ( !empty( $wc_vars ) ){
            $query_vars = array(
                // Checkout actions
                'order-pay'          => $this->get_endpoint_translation( $wc_vars['order-pay'] ),
                'order-received'     => $this->get_endpoint_translation( $wc_vars['order-received'] ),

                // My account actions
                'view-order'         => $this->get_endpoint_translation( $wc_vars['view-order'] ),
                'edit-account'       => $this->get_endpoint_translation( $wc_vars['edit-account'] ),
                'edit-address'       => $this->get_endpoint_translation( $wc_vars['edit-address'] ),
                'lost-password'      => $this->get_endpoint_translation( $wc_vars['lost-password'] ),
                'customer-logout'    => $this->get_endpoint_translation( $wc_vars['customer-logout'] ),
                'add-payment-method' => $this->get_endpoint_translation( $wc_vars['add-payment-method'] ),
            );

            WC()->query->query_vars = $query_vars;

        }

    }

    function get_endpoint_translation( $endpoint ){
        global $wpdb;

        $string = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'Endpoint slug: ' . $endpoint, $endpoint ) );

        if( !$string && function_exists( 'icl_register_string' ) ){
            icl_register_string( 'WordPress', 'Endpoint slug: ' . $endpoint, $endpoint );
        }else{
            $this->endpoints_strings[] = $string;
        }

        if( function_exists('icl_t') ){
            return icl_t( 'WordPress', 'Endpoint slug: '. $endpoint, $endpoint );
        }else{
            return $endpoint;
        }
    }

    function rewrite_rule_endpoints( $call, $data ){
        if( $call == 'icl_st_save_translation' && in_array( $data['icl_st_string_id'], $this->endpoints_strings ) ){
            $this->add_endpoints();
            flush_rewrite_rules();
        }
    }

    function update_rewrite_rules( $value, $old_value ){
        remove_filter( 'pre_update_option_rewrite_rules', array( $this, 'update_rewrite_rules' ), 100, 2 );
        $this->add_endpoints();
        flush_rewrite_rules();
        return $value;
    }

    function update_endpoints_rules(){
        $this->add_endpoints();
    }

    function add_endpoints(){
        if( !isset( $this->endpoints_strings ) )
            return;

        global $wpdb;
        //add endpoints and flush rules
        foreach( $this->endpoints_strings as $string_id ){
            $strings = $wpdb->get_results( $wpdb->prepare( "SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %s AND status = 1", $string_id ) );
            foreach( $strings as $string ){
                add_rewrite_endpoint( $string->value, EP_ROOT | EP_PAGES );
            }
        }

    }

    function endpoint_permalink_filter( $p, $pid ){
        global $post;

        if( isset($post->ID) && !is_admin() && version_compare( WOOCOMMERCE_VERSION, '2.2', '>=' ) && defined( 'ICL_SITEPRESS_VERSION' ) && !ICL_PLUGIN_INACTIVE ){
            global $wp,$sitepress;

            $current_lang = $sitepress->get_current_language();
            $page_lang = $sitepress->get_language_for_element( $post->ID, 'post_page');
            if( $current_lang != $page_lang && icl_object_id( $pid, 'page', false, $page_lang ) == $post->ID  ){

                $endpoints = WC()->query->get_query_vars();

                foreach( $endpoints as $key => $endpoint ){
                    if( isset($wp->query_vars[$key]) ){
                        if( in_array( $key, array( 'pay', 'order-received' ) ) ){
                            $endpoint = get_option( 'woocommerce_checkout_'.str_replace( '-','_',$key).'_endpoint' );
                        }else{
                            $endpoint = get_option( 'woocommerce_myaccount_'.str_replace( '-','_',$key).'_endpoint' );
                        }
                        $p = $this->get_endpoint_url($this->get_endpoint_translation( $endpoint ),$wp->query_vars[ $key ],$p);
                    }
                }
            }
        }

        return $p;
    }

    function get_endpoint_url($endpoint, $value = '', $permalink = ''){
        if ( get_option( 'permalink_structure' ) ) {
            if ( strstr( $permalink, '?' ) ) {
                $query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
                $permalink    = current( explode( '?', $permalink ) );
            } else {
                $query_string = '';
            }
            $url = trailingslashit( $permalink ) . $endpoint . '/' . $value . $query_string;
        } else {
            $url = add_query_arg( $endpoint, $value, $permalink );
        }
        return $url;
    }


}
