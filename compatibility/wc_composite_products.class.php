<?php


class WCML_Composite_Products {
	function __construct() {
		add_filter('woocommerce_composite_component_default_option', array($this, 'woocommerce_composite_component_default_option'), 10, 3);
		add_filter( 'wcml_cart_contents', array($this, 'wpml_composites_compat'), 11, 4 );
		add_filter( 'wcml_exception_duplicate_products_in_cart', array($this, 'wpml_composites_dupicate_exception'), 10, 2 );
		add_filter( 'woocommerce_composite_component_options_query_args', array($this, 'wpml_composites_transients_cache_per_language'), 10, 3 );
	}
	
	function woocommerce_composite_component_default_option($selected_value, $component_id, $object) {
		$selected_value = apply_filters('wpml_object_id', $selected_value, 'product');
		
		return $selected_value;
	}
	
	function wpml_composites_compat( $new_cart_data, $cart_contents, $key, $new_key ) {

		if ( isset( $cart_contents[ $key ][ 'composite_children' ] ) || isset( $cart_contents[ $key ][ 'composite_parent' ] ) ) {

			$buff = $new_cart_data[ $new_key ];

			unset( $new_cart_data[ $new_key ] );

			$new_cart_data[ $key ] = $buff;
		}

		return $new_cart_data;
	}

	function wpml_composites_dupicate_exception( $exclude, $cart_item ) {

		if ( isset( $cart_item[ 'composite_parent' ] ) || isset( $cart_item[ 'composite_children' ] ) ) {
			$exclude = true;
		}

		return $exclude;
	}

	function wpml_composites_transients_cache_per_language( $args, $query_args, $component_data ) {

		$args[ 'wpml_lang' ] = apply_filters( 'wpml_current_language', NULL );

		return $args;
	}

}
