<?php
  
// Our case:
// Muli-currency can be enabled by an option in wp_options - wcml_multi_currency_enabled
// User currency will be set in the woocommerce session as 'client_currency'
//     
  
class WCML_WC_MultiCurrency{
    
    private $client_currency;
    
    private $exchange_rates = array();
    
    function __construct(){
        
        add_filter('init', array($this, 'init'), 5);
        
    }
    
    function init(){        
                 
        add_filter('wcml_price_currency', array($this, 'price_currency_filter'));            
        
        add_filter('wcml_raw_price_amount', array($this, 'raw_price_filter'), 10, 2);
        
        add_filter('wcml_shipping_price_amount', array($this, 'shipping_price_filter'));
        add_filter('wcml_shipping_free_min_amount', array($this, 'shipping_free_min_amount'));
        add_action('woocommerce_product_meta_start', array($this, 'currency_switcher'));            
            
        add_filter('wcml_exchange_rates', array($this, 'get_exchange_rates'));
        
        // exchange rate GUI and logic
        if(is_admin()){
            add_action('wp_ajax_wcml_update_currency', array($this,'update_currency_exchange_rate'));
            add_action('wp_ajax_wcml_delete_currency', array($this,'delete_currency_exchange_rate'));        
        }
        
        if(defined('W3TC')){
            
            $WCML_WC_MultiCurrency_W3TC = new WCML_WC_MultiCurrency_W3TC;    
            
        }
        
        add_action('woocommerce_email_before_order_table', array($this, 'fix_currency_before_order_email'));
        add_action('woocommerce_email_after_order_table', array($this, 'fix_currency_after_order_email'));
        
        // orders
        if(is_admin()){
            global $wp;
            add_action( 'restrict_manage_posts', array($this, 'filter_orders_by_currency_dropdown'));
            $wp->add_query_var('_order_currency');
            
            add_filter('posts_join', array($this, 'filter_orders_by_currency_join'));
            add_filter('posts_where', array($this, 'filter_orders_by_currency_where'));
            
            // use correct order currency on order detail page
            add_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
            
        }
        
    }
    
    static function install(){
        global $wpdb;
        
        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."icl_currencies` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
              `value` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
              `changed` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            )";
        $wpdb->query($sql);
        
        return;
        
    }
    
    function raw_price_filter($price, $product_id = false) {
        
        $price = $this->convert_price_amount($price, $this->get_client_currency());
        
        return $price;
        
    }
    
    function shipping_price_filter($price) {
        
        $price = $this->convert_price_amount($price, $this->get_client_currency());
        
        return $price;
        
    }    
    
    function shipping_free_min_amount($price) {
        
        $price = $this->convert_price_amount($price, $this->get_client_currency());
        
        return $price;
        
    }        
    
    function convert_price_amount($amount, $currency = false){
        
        if(empty($currency)){
            $currency = $this->get_client_currency();
        }
        
        $exchange_rates = $this->get_exchange_rates();
        
        if(isset($exchange_rates[$currency]) && is_numeric($amount)){
            $amount = $amount * $exchange_rates[$currency];
        }else{
            $amount = 0;
        }
        
        return $amount;        
        
    }    
        
    function price_currency_filter($currency){
        
        if(isset($this->order_currency)){
            $currency = $this->order_currency;
        }else{
            $currency = $this->get_client_currency();    
        }
        
        return $currency;
    }
    
    function get_exchange_rates(){
        
        if(empty($this->exchange_rates)){
            global $wpdb;
            
            $this->exchange_rates = array(get_option('woocommerce_currency') => 1);
            
            $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC", OBJECT);
            
            $woo_currencies = get_woocommerce_currencies(); 
            
            foreach($currencies as $currency){
                if(!empty($woo_currencies[$currency->code])){
                    $this->exchange_rates[$currency->code] = $currency->value;
                }
            }
        }
        
        return $this->exchange_rates;
    }
    
    function update_currency_exchange_rate(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_currency')){
            die('Invalid nonce');
        }

        global $wpdb;
        if($_POST['currency_id'] == 0){
            $wpdb->insert($wpdb->prefix .'icl_currencies', array(
                    'code' => $_POST['currency_code'],
                    'value' => (double) $_POST['currency_value'],
                    'changed' => date('Y-m-d H:i:s')
                )
            );

            echo $wpdb->insert_id;
        } else {
            $wpdb->update(
                $wpdb->prefix .'icl_currencies',
                array(
                    'code' => $_POST['currency_code'],
                    'value' => (double) $_POST['currency_value'],
                    'changed' => date('Y-m-d H:i:s')
                ),
                array( 'id' => $_POST['currency_id'] )
            );
        }
        die();
    }

    function delete_currency_exchange_rate(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_delete_currency')){
            die('Invalid nonce');
        }
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM ". $wpdb->prefix ."icl_currencies WHERE id = %d", $_POST['currency_id']));
        die();
    }
    
    function currency_switcher(){
        echo(do_shortcode('[currency_switcher]'));
    }
    
    function get_client_currency(){
        global $woocommerce;
        return $woocommerce->session->get('client_currency');
        
    }
    
    function woocommerce_currency_hijack($currency){
        if(isset($this->order_currency)){
            $currency = $this->order_currency;                
        }
        return $currency;
    }
    
    // handle currency in order emails before handled in woocommerce
    function fix_currency_before_order_email($order){
        
        // backwards comp
        if(!method_exists($order, 'get_order_currency')) return;
        
        $this->order_currency = $order->get_order_currency();
        add_filter('woocommerce_currency', array($this, 'woocommerce_currency_hijack'));
    }
    
    function fix_currency_after_order_email($order){
        unset($this->order_currency);
        remove_filter('woocommerce_currency', array($this, 'woocommerce_currency_hijack'));
    }
    
    function filter_orders_by_currency_join($join){
        global $wp_query, $typenow, $wpdb;
        
        if($typenow == 'shop_order' &&!empty($wp_query->query['_order_currency'])){
            $join .= " JOIN {$wpdb->postmeta} wcml_pm ON {$wpdb->posts}.ID = wcml_pm.post_id AND wcml_pm.meta_key='_order_currency'";
        }
        
        return $join;
    }
    
    function filter_orders_by_currency_where($where){
        global $wp_query, $typenow;
        
        if($typenow == 'shop_order' &&!empty($wp_query->query['_order_currency'])){
            $where .= " AND wcml_pm.meta_value = '" . esc_sql($wp_query->query['_order_currency']) .  "'";
        }
        
        return $where;
    }
    
    function filter_orders_by_currency_dropdown(){
        global $woocommerce, $wp_query, $typenow;
        
        if($typenow != 'shop_order') return false;
        
        $order_currencies = $this->get_orders_currencies();
        $currencies = get_woocommerce_currencies(); 
        ?>        
        <select id="dropdown_shop_order_currency" name="_order_currency">
            <option value=""><?php _e( 'Show all currencies', 'wpml-wcml' ) ?></option>
            <?php foreach($order_currencies as $currency => $count): ?>            
            <option value="<?php echo $currency ?>" <?php 
                if ( isset( $wp_query->query['_order_currency'] ) ) selected( $currency, $wp_query->query['_order_currency'] ); 
                ?> ><?php printf("%s (%s) (%d)", $currencies[$currency], get_woocommerce_currency_symbol($currency), $count) ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        $woocommerce->add_inline_js( "jQuery('select#dropdown_shop_order_currency, select[name=m]').css('width', '180px').chosen();");
        
        $this->get_orders_currencies();
        
    }
    
    function get_orders_currencies(){
        global $wpdb;
        
        $currencies = array();
        
        $results = $wpdb->get_results("
            SELECT m.meta_value AS currency, COUNT(m.post_id) AS c
            FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
            WHERE meta_key='_order_currency' AND p.post_type='shop_order'
            GROUP BY meta_value           
        ");
        
        foreach($results as $row){
            $currencies[$row->currency] = $row->c;
        }
        
        return $currencies;
        
        
    }
    
    function _use_order_currency_symbol($currency){
        global $wp_query, $typenow, $woocommerce;
        remove_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
        
        $current_screen = get_current_screen();

        if($current_screen->id == 'shop_order'){            
            
            $the_order = new WC_Order( get_the_ID() );                        
            if($the_order && method_exists($the_order, 'get_order_currency')){
                $currency = get_woocommerce_currency_symbol($the_order->get_order_currency());    
            }
        }
        
        return $currency;
    }
    
    

}

//@todo Move to separate file
class WCML_WC_MultiCurrency_W3TC{
    
    function __construct(){
        
        add_filter('init', array($this, 'init'), 15);
        
    }
    
    function init(){
        
        add_action('wcml_switch_currency', array($this, 'flush_page_cache'));
        
    }
    
    function flush_page_cache(){
        w3_require_once(W3TC_LIB_W3_DIR . '/AdminActions/FlushActionsAdmin.php');
        $flush = new W3_AdminActions_FlushActionsAdmin();
        $flush->flush_pgcache(); 
    }
    
}