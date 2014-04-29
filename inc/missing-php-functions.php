<?php
  
/* PHP 5.3 - start */

if(false === function_exists('lcfirst'))
{
    /**
     * Make a string's first character lowercase
     *
     * @param string $str
     * @return string the resulting string.
     */
    function lcfirst( $str ) {
        $str[0] = strtolower($str[0]);
        return (string)$str;
    }
}

if (get_magic_quotes_gpc()) {
    if(!function_exists('stripslashes_deep')){
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);

        return $value;
    }
    }

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}
/* PHP 5.3 - end */
  
//WPML
add_action('plugins_loaded', 'wcml_check_wpml_is_ajax');

function wcml_check_wpml_is_ajax(){
    if(version_compare(preg_replace('#-(.+)$#', '', ICL_SITEPRESS_VERSION), '3.1.5', '<')){
        
        function wpml_is_ajax() {
            if ( defined( 'DOING_AJAX' ) ) {
                return true;
            }

            return ( isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest' ) ? true : false;
        }
        
    }
    
    
}

  
?>
