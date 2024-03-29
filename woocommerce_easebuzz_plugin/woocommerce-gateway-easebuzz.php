<?php
        
        /*
        Plugin Name: Easebuzz Gateway
        Plugin URI: https://pay.easebuzz.in/
        Description: Easebuzz Payment Gateway Integration for WooCommerce
        Version: 5.2.2
        Developer : Rajesh Kushwaha
        Author: Team Easebuzz 
        Author URI: http://www.easebuzz.in/
        License: GNU General Public License v3.0
        License URI: http://www.gnu.org/licenses/gpl-3.0.html
        */
        

        add_action('plugins_loaded', 'woocommerce_gateway_payeasebuzz_init', 0);
        
        define('payeasebuzz_IMG', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');
        
        function woocommerce_gateway_payeasebuzz_init() {
            if (!class_exists('WC_Payment_Gateway'))
                return;       
            class WC_Easebuzz_Gateway extends WC_Payment_Gateway {
                
                /*******       construct configs      *******/
                public function __construct() {
                    $this->id = 'payeasebuzz'; // ID for WC to associate the gateway values
                    $this->method_title = 'Easebuzz gateway'; // Gateway Title as seen in Admin Dashboad
                    $this->method_description = 'Pay with Easebuzz - Redefining Payments'; // Gateway Description as seen in Admin Dashboad
                    $this->has_fields = false; // Inform WC if any fileds have to be displayed to the visitor in Frontend
                    
                    $this->init_form_fields(); // defines your settings to WC
                    $this->init_settings();  // loads the Gateway settings into variables for WC
                    
                    // settings if gateway is on Test Mode
                    $test_title = '';
                    $test_description = '';
                    // if ($this->settings['test_mode'] == 'test') {
                    //     $test_title = ' [TEST MODE]';
                    //     $test_description = '<br/><br/><u>Test Mode is <strong>ACTIVE</strong>, use following Credit Card details:-</u><br/>' . "\n"
                    //     . 'Test Card Name: <strong><em>any name</em></strong><br/>' . "\n"
                    //     . 'Test Card Number: <strong>5123 4567 8901 2346</strong><br/>' . "\n"
                    //     . 'Test Card CVV: <strong>123</strong><br/>' . "\n"
                    //     . 'Test Card Expiry: <strong>May 2017</strong>';
                    // }
                    
                    $this->title = $this->settings['title'] . $test_title; // Title as displayed on Frontend
                    $this->description = $this->settings['description'] . $test_description; // Description as displayed on Frontend
                    if ($this->settings['show_logo'] != "no") { // Check if Show-Logo has been allowed
                        $this->icon = payeasebuzz_IMG . $this->settings['show_logo'] . '.png';
                    }
                    $this->key_id = $this->settings['key_id'];
                    $this->key_secret = $this->settings['key_secret'];
                    $this->redirect_page = $this->settings['redirect_page']; // Define the Redirect Page.
                    $this->redirect_fail_page = $this->settings['redirect_fail_page']; // Define the Redirect Page.
                    $this->msg['message'] = '';
                    $this->msg['class'] = '';
                    $this->enable_iframe = $this->settings['enable_iframe'];
                    
                    add_action('init', array(&$this, 'check_payeasebuzz_response'));
                    add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_payeasebuzz_response')); //update for woocommerce >2.0
                    
                    if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options')); //update for woocommerce >2.0
                    } else {
                        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); // WC-1.6.6
                    }
                    add_action('woocommerce_receipt_payeasebuzz', array(&$this, 'receipt_page'));
                }

                /** Initiate Form Fields in the Admin Backend **/
                function init_form_fields() {    
                    $this->form_fields = array(
                                            // Activate the Gateway
                                            'enabled' => array(
                                                            'title' => __('Enable/Disable:', 'woo_payeasebuzz'),
                                                            'type' => 'checkbox',
                                                            'label' => __('Enable Easebuzz', 'woo_payeasebuzz'),
                                                            'default' => 'no',
                                                            'description' => 'Show in the Payment List as a payment option'
                                                            ),
                                            // Title as displayed on Frontend
                                            'title' => array(
                                                            'title' => __('Title:', 'woo_payeasebuzz'),
                                                            'type' => 'text',
                                                            'default' => __('Pay Online (Upto 50% returns)', 'woo_payeasebuzz'),
                                                            'description' => __('This controls the title which the user sees during checkout.', 'woo_payeasebuzz'),
                                                            'placeholder' => __('Enter The Title'),
                                                            'desc_tip' => true
                                                            ),
                                            // Description as displayed on Frontend
                                            'description' => array(
                                                                'title' => __('Description:', 'woo_payeasebuzz'),
                                                                'type' => 'textarea',
                                                                'default' => __('Pay securely by Credit or Debit card or internet banking through Easebuzz.', 'woo_payeasebuzz'),
                                                                'placeholder' => __('Enter The Descriptions'),
                                                                'description' => __('This controls the description which the user sees during checkout.', 'woo_payeasebuzz'),
                                                                'desc_tip' => true
                                                                ),
                                            // LIVE Key-ID
                                            'key_id' => array(
                                                            'title' => __('Merchant KEY:', 'woo_payeasebuzz'),
                                                            'type' => 'text',
                                                            'placeholder' => __('Enter The Merchant Key'),
                                                            'description' => __('Given to Merchant by Easebuzz'),
                                                            'desc_tip' => true
                                                            ),
                                            // LIVE Key-Secret
                                            'key_secret' => array(
                                                                'title' => __('Merchant SALT:', 'woo_payeasebuzz'),
                                                                'type' => 'text',
                                                                'description' => __('Given to Merchant by Easebuzz'),
                                                                'placeholder' => __('Enter The Merchant Key'),
                                                                'desc_tip' => true
                                                                ),
                                            // Mode of Transaction
                                            'test_mode' => array(
                                                                'title' => __('Mode:', 'woo_payeasebuzz'),
                                                                'type' => 'select',
                                                                'label' => __('Easebuzz Tranasction Mode.', 'woo_payeasebuzz'),
                                                                'options' => array('test' => 'Test Mode', 'prod' => 'Live Mode'),
                                                                'default' => 'test',
                                                                'description' => __('Mode of Easebuzz activities'),
                                                                'desc_tip' => true
                                                                ),
                                            // Page for Redirecting after Transaction
                                            'redirect_page' => array(
                                                                    'title' => __('Success URL'),
                                                                    'type' => 'select',
                                                                    'options' => $this->easebuzz_get_pages('Select Page'),
                                                                    'description' => __('URL of success page','woo_payeasebuzz'),
                                                                    'desc_tip' => true
                                                                    ),
                                            'redirect_fail_page' => array(
                                                                        'title' => __('failure URL'),
                                                                        'type' => 'select',
                                                                        'options' => $this->easebuzz_get_pages('Select Page'),
                                                                        'description' => __('URL of failure page', 'woo_payeasebuzz'),
                                                                        'desc_tip' => true
                                                                        ),
                                            // Show Logo on Frontend
                                            'show_logo' => array(
                                                                'title' => __('Show Logo:', 'woo_payeasebuzz'),
                                                                'type' => 'select',
                                                                'label' => __('Logo on Checkout Page', 'woo_payeasebuzz'),
                                                                'options' => array('no' => 'No Logo', 'pay01' => 'Easebuzz - Icon', 'pay02' => 'Easebuzz - Logo'),
                                                                'default' => 'no',
                                                                'description' => __('<strong>Easebuzz logo: &nbsp;&nbsp;&nbsp;&nbsp;</strong> | Icon: <img src="' . payeasebuzz_IMG . 'pay01.png" height="24px" /> | Logo: <img src="' . payeasebuzz_IMG . 'pay02.png" height="24px" />'),
                                                                'desc_tip' => false
                                                                ),
                                            'enable_iframe' => array(
                                                    'title' => __('Enable/Disable:', 'woo_payeasebuzz'),
                                                    'type' => 'checkbox',
                                                    'label' => __('Enable iframe', 'woo_payeasebuzz'),
                                                    'default' => 'no',
                                                    'description' => 'Easecheckout option'
                                                    ),
                                            );
                }//END-init_form_fields
                
                /** Get Page list from WordPress **/
                function easebuzz_get_pages($title = false, $indent = true) {
                    $wp_pages = get_pages('sort_column=menu_order');
                    $page_list = array();
                    if ($title)
                        $page_list[] = $title;
                    foreach ($wp_pages as $page) {
                        $prefix = '';
                        // show indented child pages?
                        if ($indent) {
                            $has_parent = $page->post_parent;
                            while ($has_parent) {
                                $prefix .= ' - ';
                                $next_page = get_post($has_parent);
                                $has_parent = $next_page->post_parent;
                            }
                        }
                        // add to page list array array
                        $page_list[$page->ID] = $prefix . $page->post_title;
                    }
                    return $page_list;
                }
                
                /** Admin Panel Options- Show info on Admin Backend **/
                public function admin_options() {
                    echo '<h3>' . __('Easebuzz', 'woo_payeasebuzz') . '</h3>';
                    echo '<p><small><strong>' . __('Confirm your Mode: Is it LIVE or TEST.') . '</strong></small></p>';
                    echo '<table class="form-table">';
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                    echo '</table>';
                }
                
                /** There are no payment fields, but we want to show the description if set. **/
                function payment_fields() {
                    if ($this->description) {
                        echo wpautop(wptexturize($this->description));
                    }
                }
                
                /** Receipt Page **/
                function receipt_page($order){
                    
                    echo '<div id ="ebz-checkout-btn"></div>';
                    //echo '<p><strong>' . __('Thank you for your order.', 'woo_payeasebuzz').'</strong><br/>' . __('The payment page will open soon.', 'woo_payeasebuzz').'</p>';
                    echo $this->generate_payeasebuzz_form($order);
                }
                
                /** Generate button link **/
                function generate_payeasebuzz_form($order_id){
                    global $woocommerce;
                    $order = new WC_Order( $order_id );
                    // Redirect fail URL
                    if ( $this->redirect_fail_page == '' || $this->redirect_fail_page == 0 ) {
                        $redirect_fail_url = get_site_url() . "/";
                    } else {
                        $redirect_fail_url = get_permalink( $this->redirect_fail_page );
                    }
                    // Redirect URL : For WooCoomerce 2.0
                    if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                        $redirect_fail_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_fail_url);
                    }
                    
                    // Redirect URL
                    if ( $this->redirect_page == '' || $this->redirect_page == 0 ) {
                        $redirect_url = get_site_url() . "/";
                    } else {
                        $redirect_url = get_permalink( $this->redirect_page );
                    }
                    // Redirect URL : For WooCoomerce 2.0
                    if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                        $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
                    }
                    
                    $productinfo = "Order $order_id";
                    $txnid = $order_id.'_'.date("ymds");
                    if ($this->settings['test_mode'] == 'prod') {
                        $ENV='prod';
                    }else{
                        $ENV='test';
                    }
                    $SALT=$this->settings['key_secret'];

                    $OriginalString = $order->customer_note;   
                    
                    $arr = explode(",",$OriginalString);
                    $udf = array();
                    for($a = 0;$a<6; $a++){
                        $udf[$a] = $arr[$a]."";
                    }

                    $name = $order->billing_first_name." ".$order->billing_last_name;

                    include( plugin_dir_path( __FILE__ ) . 'easepay-lib.php');
                    $payment_request_parameter = array(
                        'key'           =>   $this->key_id,
                        'txnid'         =>   $order->order_key,
                        'amount'        =>   $order->order_total,
                        'email'         =>   $order->billing_email,
                        'firstname'     =>   preg_match('/^[a-zA-Z0-9\\-._ \()\/,\s]+$/', $name) ? $name : $order->add_order_note("The customer enter any special character in Name, which we does not allows.")."".$order->update_status('failed')."".die('<script>alert("We found special characters in Name.");document.write("<b>Please remove the special character from your Name and try again to complete you order.</b>");</script>'),
                        'phone'         =>   preg_match('/^(\\+\\d{1,3}[-]?)?\\d{5,20}$/',$order->billing_phone) ? $order->billing_phone : $order->add_order_note("The customer enter wrong value for phone number.")."".$order->update_status('failed')."".die('<script>alert("You are passing wrong values for the phone number.");document.write("Please enter your correct phone number and try again to complete you order.");</script>'),
                        'address1'      =>   preg_match('/^[a-zA-Z\\.0-9\/\\,\s_#@()&|:-]*$/',$order->billing_address_1) ? $order->billing_address_1 : $order->add_order_note("The customer enter any special character in address1, which is not allowed in Address.")."".$order->update_status('failed')."".die('<script>alert("You are passing special characters or symbols in address 1 field.");document.write("Please remove specail character or symbols from address1 and try again to complete you order.");</script>'),
                        'address2'      =>   preg_match('/^[a-zA-Z\\.0-9\/\\,\s_#@()&|:-]*$/',$order->billing_address_2) ? $order->billing_address_2 : $order->add_order_note("The customer enter any special character in address2, which is not allowed in Address.")."".$order->update_status('failed')."".die('<script>alert("You are passing special characters or symbols in address 2 field.");document.write("Please remove specail character or symbols from address2 and try again to complete you order.");</script>'),
                        'city'          =>   preg_match('/^[0-9a-zA-Z_.\s-]*$/',$order->billing_city) ? $order->billing_city : $order->add_order_note("The customer enter any special character in city, which is not allowed in City.")."".$order->update_status('failed')."".die('<script>alert("We found special characters in city field.");document.write("Please remove the specail character from city and try again to complete you order.");</script>'),
                        'state'         =>   preg_match('/^[0-9a-zA-Z_.\s-]*$/',$order->billing_state) ? $order->billing_state : $order->add_order_note("The customer enter any special character in state, which is not allowed in State.")."".$order->update_status('failed')."".die('<script>alert("There is any special character in state.");document.write("Please remove special character from state field and try again to complete you order.");</script>'),
                        'country'       =>   preg_match('/^[a-zA-Z_.\s-]*$/',$order->billing_country) ? $order->billing_country : $order->add_order_note("The customer enter any special character in country, which is not allowed in country.")."".$order->update_status('failed')."".die('<script>alert("Please select correct country name.");document.write("Please select correct country.");</script>'),
                        'zipcode'       =>   preg_match('/^[0-9]{0,6}$/',$order->billing_postcode) ? $order->billing_postcode : $order->add_order_note("The customer enter any special character or invalid zipcode, which is not allowed in zipcode.")."".$order->update_status('failed')."".die('<script>alert("Invalid zip code.");document.write("Please enter correct the zipcode.");</script>'),
                        'udf1'          =>   $order_id,
                        'udf2'          =>   $udf[0],
                        'udf3'          =>   $udf[1],
                        'udf4'          =>   $udf[2],
                        'udf5'          =>   $udf[3],
                        'udf6'          =>   $udf[4],
                        'udf7'          =>   $udf[5],
                        'productinfo'   =>   $productinfo,
                        'surl'          =>   $redirect_url,
                        'furl'          =>   $redirect_fail_url
                    );
                    $response = easepay_page($payment_request_parameter, $SALT, $ENV);
                    if($response["status"]===1){
                        return '<script type="text/javascript">
                        jQuery(function(){
                            jQuery("body").block({
                                message: "'.__('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'woo_payeasebuzz').'",
                                overlayCSS: {
                                background	: "#fff",
                                opacity	: 0.6
                                },
                                css: {
                                padding	: 20,
                                textAlign	: "center",
                                color	: "#555",
                                border	: "3px solid #aaa",
                                backgroundColor: "#fff",
                                cursor	: "wait",
                                lineHeight	: "32px"
                                }
                                });
                                
                                window.location="'.$response["data"].'";
                            });
                            </script>
                        </form>';
                    }
                    else if($response["status"]==='iframe'){
                        $key =$response["data"]["key"];
                        $access_key=$response["data"]["access_key"];
                        $ajaxurl = admin_url('admin-ajax.php');
                        
                        return 
                        " <style>#loading {
                            background: url('https://www.voya.ie/Interface/Icons/LoadingBasketContents.gif') no-repeat center center;
                            position: absolute;
                            top: 0;
                            left: 0;
                            height: 100%;
                            width: 100%;
                            z-index: 9999999;
                            background-size:100px;
                        }</style>
                        <script src='https://ebz-static.s3.ap-south-1.amazonaws.com/easecheckout/easebuzz-checkout.js'></script>
                        <script type='text/javascript'>
                            jQuery(function(){
                                jQuery('.entry-content').append('<div id=loading></div>');
                                var easebuzzCheckout = new EasebuzzCheckout('". $key ."','prod')

                            jQuery( '#ebz-checkout-btn' ).on( 'click', function( e ) {
                                var options = {
                                access_key: '".$access_key."' , // access key received via Initiate Payment
                                onResponse: (response_data) => {
                                console.log(response_data);
                                jQuery('#loading').show();
                                    jQuery.ajax({
                                        type : 'post',
                                        data : { 
                                            action : 'payment_response_iframe',
                                            'response':response_data,
                                            'key_secret':'".$this->key_secret."',
                                            'key_id':'".$this->key_id."'
                                        },
                                        dataType : 'json',
                                        url : '".$ajaxurl."',                                        
                                        success: function(data) {
                                            
                                            if(data.class=='success') {
                                                var html = '<p style=color:black>'+data.message+'</p>'
                                            }else{
                                                var html = '<p style=color:black>'+data.message+'</p>'
                                            }                                            
                                            jQuery('.entry-content').append(html);     
                                            jQuery('#loading').hide();
                                            
                                        }
                                    });
                                },
                                theme: '#123456' // color hex
                                }
                                easebuzzCheckout.initiatePayment(options);
                            });
                            jQuery( '#ebz-checkout-btn' ).trigger( 'click' );
                        });
                        </script>";                    
                    }
                    else{
                        return '<script type="text/javascript">
                        jQuery(function(){
                            jQuery("body").block({
                                    message: "'.__('Oops, Server error, Please try again. '.$response["data"], 'woo_payeasebuzz').'",
                                    overlayCSS: {
                                        background	: "#fff",
                                        opacity	: 0.6
                                        },
                                    css: {
                                        padding	: 20,
                                        textAlign	: "center",
                                        color	: "#555",
                                        border	: "3px solid #aaa",
                                        backgroundColor: "#fff",
                                        cursor	: "wait",
                                        lineHeight	: "32px"
                                        }
                                    });
                                });
                        </script>
                        </form>';
                    }
                    
                }
                /** Process the payment and return the result **/
                function process_payment($order_id){
                    global $woocommerce;
                    $order = new WC_Order($order_id);
                    
                    if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) { // For WC 2.1.0
                        $checkout_payment_url = $order->get_checkout_payment_url( true );
                    } else {
                        $checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
                    }
                    
                    return array(
                                'result' => 'success',
                                'redirect' => add_query_arg(
                                                            'order',
                                                            $order->id,
                                                            add_query_arg(
                                                                        'key',
                                                                        $order->order_key,
                                                                        $checkout_payment_url
                                                                        )
                                                            )
                                );
                }
                
                /** Check for valid gateway server callback **/
                function check_payeasebuzz_response(){
                    global $woocommerce;
                    global $wpdb;
                    // set redirect url and redirect fail url
                    if ( $this->redirect_page == '' || $this->redirect_page == 0 ) {
                        $redirect_url = get_permalink( get_option('woocommerce_myaccount_page_id') );
                    } else {
                        $redirect_url = get_permalink( $this->redirect_page );
                    }

                    if ( $this->redirect_fail_page == '' || $this->redirect_fail_page == 0 ) {
                        $redirect_fail_url = get_permalink( get_option('woocommerce_myaccount_page_id'));
                    } else {
                        $redirect_fail_url = get_permalink( $this->redirect_fail_page );
                    }
                    
                    $redirect_new_url = '';
                    
                    if( isset($_REQUEST['txnid']) && isset($_REQUEST['easepayid']) ){
                        $order_id = $_REQUEST['udf1'];
                        
                        if($order_id != ''){
                            try{
                                //$order = new WC_Order( $order_id );
                                $order = wc_get_order($order_id);
                                $order->set_transaction_id($_REQUEST['easepayid']);
                                $order->save();
                                $hash = $_REQUEST['hash'];
                                $status = $_REQUEST['status'];
                                $checkhash = hash('sha512', "$this->key_secret|$_REQUEST[status]||||$_REQUEST[udf7]|$_REQUEST[udf6]|$_REQUEST[udf5]|$_REQUEST[udf4]|$_REQUEST[udf3]|$_REQUEST[udf2]|$_REQUEST[udf1]|$_REQUEST[email]|$_REQUEST[firstname]|$_REQUEST[productinfo]|$_REQUEST[amount]|$_REQUEST[txnid]|$this->key_id");
                                $trans_authorised = false;
                                if( $order->status !=='completed'){
                                    if($hash == $checkhash){ 
                                        $status = strtolower($status);
                                        if($status=="success"){
                                            $trans_authorised = true;
                                            $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful.";
                                            $this->msg['class'] = 'success';
                                            if($order->status == 'processing'){
                                                $order->add_order_note('Easebuzz payment successful.<br/>Easbeuzz ID: '.$_REQUEST['easepayid'].' ('.$_REQUEST['txnid'].')<br/>PG: '.$_REQUEST['PG_TYPE'].'<br/>Bank Ref: '.$_REQUEST['bank_ref_num'].'('.$_REQUEST['mode'].')');
                                                $woocommerce->cart->empty_cart();
                                            }else{
                                                $order->payment_complete();
                                                $order->add_order_note('Easebuzz payment successful.<br/>Easbeuzz ID: '.$_REQUEST['easepayid'].' ('.$_REQUEST['txnid'].')<br/>PG: '.$_REQUEST['PG_TYPE'].'<br/>Bank Ref: '.$_REQUEST['bank_ref_num'].'('.$_REQUEST['mode'].')');
                                                $woocommerce->cart->empty_cart();
                                                
                                            }
                                            $this->redirectUser($order);
                                            //$redirect_new_url = $redirect_url;
                                            
                                        }else if($status=="pending"){
                                            $trans_authorised = true;
                                            $this->msg['message'] = "Thank you for shopping with us. Right now your payment status is pending. We will keep you posted regarding the status of your order through eMail";
                                            $this->msg['class'] = 'pending';
                                            $order->add_order_note('Easebuzz payment status is pending<br/>Easebuzz ID: '.$_REQUEST['easepayid'].' ('.$_REQUEST['txnid'].')<br/>PG: '.$_REQUEST['PG_TYPE'].'<br/>Bank Ref: '.$_REQUEST['bank_ref_num'].'('.$_REQUEST['mode'].')');
                                            $order->update_status('on-hold');
                                            $woocommerce -> cart -> empty_cart();
                                            //$redirect_new_url = $redirect_url;
                                            $this->redirectUser($order);
                                        }else{
                                            $this->msg['class'] = 'error';
                                            $this->msg['message'] = "Oops, the transaction has been failed. Please try again";
                                            $order->add_order_note('Transaction ERROR: '.$_REQUEST['error'].'<br/>Easebuzz ID: '.$_REQUEST['easepayid'].' ('.$_REQUEST['txnid'].')<br/>PG: '.$_REQUEST['PG_TYPE'].'<br/>Bank Ref: '.$_REQUEST['bank_ref_num'].'('.$_REQUEST['mode'].')');
                                            //$redirect_new_url = $redirect_fail_url;
                                            $this->redirectUser($order);
                                        }
                                    }else{
                                        $this->msg['class'] = 'error';
                                        $this->msg['message'] = "Security Error. Illegal access detected.";
                                        $order->add_order_note('Checksum ERROR: '.json_encode($_REQUEST));
                                        $redirect_new_url = $redirect_fail_url;
                                    }
                                    if($trans_authorised==false){
                                        $order->update_status('failed');
                                    }
                                }else{
                                // $redirect_new_url = $redirect_fail_url;
                                }
                                
                                if ( function_exists( 'wc_add_notice' ) )
                                {
                                    wc_add_notice( $this->msg['message'], $this->msg['class'] );
                                }
                                else
                                {
                                    if($this->msg['class']=='success'){
                                        $woocommerce->add_message( $this->msg['message']);
                                    }else{
                                        $woocommerce->add_error( $this->msg['message'] );
                                    }
                                    $woocommerce->set_messages();
                                }
                            }catch(Exception $e){
                                // $errorOccurred = true;
                                // $redirect_new_url = $redirect_fail_url;
                                $msg = "Error";
                            }
                        }else{
                            //$redirect_new_url = $redirect_fail_url;
                        
                        }
                        wp_redirect( $redirect_new_url );
                        exit;
                        
                    }
                    
                } //END-check_payeasebuzz_response
    
            public function redirectUser($order){    
                    $redirectUrl = $this->get_return_url($order);
                    wp_redirect($redirectUrl);
                }
            }


            /** Add the Gateway to WooCommerce **/
            function woocommerce_add_gateway_payeasebuzz_gateway($methods) {
                $methods[] = 'WC_Easebuzz_Gateway';
                return $methods;
            }            
            add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_payeasebuzz_gateway');

        }
        
        /** 'Settings' link on plugin page **/
        add_filter('plugin_action_links', 'payeasebuzz_add_action_plugin', 10, 5);
        function payeasebuzz_add_action_plugin($actions, $plugin_file) {
            static $plugin;
            
            if (!isset($plugin))
                $plugin = plugin_basename(__FILE__);
            if ($plugin == $plugin_file) {
                
                $settings = array('settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_gateway_payeasebuzz">' . __('Settings') . '</a>');
                
                $actions = array_merge($settings, $actions);
            }
            return $actions;
        }



add_action("wp_ajax_payment_response_iframe", "payment_response_iframe");
add_action("wp_ajax_nopriv_payment_response_iframe", "payment_response_iframe");

function payment_response_iframe(){
    global $woocommerce;
    global $wpdb;
    $return_response=array();
    $response=$_POST['response'];
    if( isset($response['txnid']) && isset($response['easepayid']) ){
        $order_id = $response['udf1'];
        $key_secret=$_POST['key_secret'];
        $key_id=$_POST['key_id'];
        if($order_id != ''){
            try{
                $order = new WC_Order( $order_id );
                $hash = $response['hash'];
                $status = $response['status'];
                $checkhash = hash('sha512', "$key_secret|$response[status]||||$response[udf7]|$response[udf6]|$response[udf5]|$response[udf4]|$response[udf3]|$response[udf2]|$response[udf1]|$response[email]|$response[firstname]|$response[productinfo]|$response[amount]|$response[txnid]|$key_id");
                $trans_authorised = false;
                if( $order->status !=='completed'){
                    if($hash == $checkhash){
                        $status = strtolower($status);
                        if($status=="success"){
                            $order->add_order_note('Easebuzz payment successful.<br/>Easbeuzz ID: '.$response['easepayid'].' ('.$response['txnid'].')<br/>PG: '.$response['PG_TYPE'].'<br/>Bank Ref: '.$response['bank_ref_num'].'('.$response['mode'].')');
                            $woocommerce->cart->empty_cart();
                            $trans_authorised = true;
                            $return_response['message'] = "<strong>Thank you for shopping with us.Your transaction is successful.<br><br></strong>".
                            "<style>
                                table, th, td {
                                    color: black;
                                    border:1px solid #000;
                                    background: #ffffff;
                                    font-size:20px;
                                    padding: 10px;
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                            </style>
                            <table class='mycss' 
                            style="."width:100%".">
                                    <tr><strong>Transaction Details:</strong><br></tr>
                                    <tr>
                                        <td>Transaction status : </td>
                                        <td>".$response['status']."</td>
                                    </tr>
                                    <tr>
                                        <td>Easebuzz ID : </td>
                                        <td>".$response['easepayid']."</td>
                                    </tr>
                                    <tr>
                                        <td>Txn id : </td>
                                        <td>".$response['txnid']."</td>
                                    </tr>
                                    <tr>
                                        <td>Bank Ref Number : </td>
                                        <td>".$response['bank_ref_num']."</td>
                                    </tr>
                                    <tr>
                                        <td>Total Amount : </td>
                                        <td>".$order->order_total."</td>
                                    </tr>
                                    
                            </table>
                            <style>
                                table, th, td {
                                    color: black;
                                    background: #ffffff;
                                    font-size:20px;
                                    padding: 10px;
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                            </style>
                            <table class='mycss' 
                            style="."width:100%".">
                                    <tr><br><strong>Billing Address</strong><br></tr>
                                    <tr>
                                        <td>$order->billing_first_name $order->billing_last_name</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_address_1</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_address_2</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_city $order->billing_state </td>
                                    </tr>
                                    <tr>
                                        <td>&#128222;$order->billing_phone</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_email</td>
                                    </tr>
                            </table>";
                            $return_response['class'] = 'success';
                            if($order->status == 'processing'){
                                $order->add_order_note('Easebuzz payment successful.<br/>Easbeuzz ID: '.$response['easepayid'].' ('.$response['txnid'].')<br/>PG: '.$response['PG_TYPE'].'<br/>Bank Ref: '.$response['bank_ref_num'].'('.$response['mode'].')');
                                $woocommerce->cart->empty_cart();
                            }else{
                                $order->payment_complete();
                                $order->add_order_note('Easebuzz payment successful.<br/>Easbeuzz ID: '.$response['easepayid'].' ('.$response['txnid'].')<br/>PG: '.$response['PG_TYPE'].'<br/>Bank Ref: '.$response['bank_ref_num'].'('.$response['mode'].')');
                                $woocommerce->cart->empty_cart(); 
                            }
                        }else if($status=="pending"){
                            $trans_authorised = true;
                            $return_response['message'] = "<strong>Thank you for shopping with us. Right now your payment status is pending. We will keep you posted regarding the status of your order through eMail</strong>".
                            "<style>
                                table, th, td {
                                    color: black;
                                    border:1px solid #000;
                                    background: #ffffff;
                                    font-size:20px;
                                    padding: 10px;
                                    border: 1px solid black; 
                                    border-collapse: collapse;
                                }
                            </style>
                            <table class='mycss' 
                            style="."width:100%".">
                                    <tr><strong>Transaction Details:</strong><br></tr>
                                    <tr>
                                        <td>Transaction status : </td>
                                        <td>".$response['status']."</td>
                                    </tr>
                                    <tr>
                                        <td>Easebuzz ID : </td>
                                        <td>".$response['easepayid']."</td>
                                    </tr>
                                    <tr>
                                        <td>Txn id : </td>
                                        <td>".$response['txnid']."</td>
                                    </tr>
                                    <tr>
                                    <td>Bank Ref Number : </td>
                                        <td>".$response['bank_ref_num']."</td>
                                    </tr>
                                    <tr>
                                        <td>Total Amount : </td>
                                        <td>".$order->order_total."</td>
                                    </tr>
                                    
                            </table>
                            <style>
                                table, th, td {
                                    color: black;
                                    background: #ffffff;
                                    font-size:20px;
                                    padding: 10px;
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                            </style>
                            <table class='mycss' 
                            style="."width:100%".">
                                    <tr><br><strong>Billing Address</strong><br></tr>
                                    <tr>
                                        <td>$order->billing_first_name $order->billing_last_name</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_address_1</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_address_2</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_city $order->billing_state </td>
                                    </tr>
                                    <tr>
                                        <td>&#128222;$order->billing_phone</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_email</td>
                                    </tr>
                            </table>";
                            $return_response['class'] = 'pending';
                            $order->add_order_note('Easebuzz payment status is pending<br/>Easebuzz ID: '.$response['easepayid'].' ('.$response['txnid'].')<br/>PG: '.$response['PG_TYPE'].'<br/>Bank Ref: '.$response['bank_ref_num'].'('.$response['mode'].')');
                            $order->update_status('on-hold');
                            $woocommerce -> cart -> empty_cart();
                            
                        }else{  
                            $return_response['class'] = 'error';
                            $return_response['message'] =
                            "<strong>Oops, Sorry your transaction has been failed.<br><br></strong>".
                            "<style>
                                table, th, td {
                                    color: black;
                                    border:1px solid #000;
                                    background: #ffffff;
                                    padding: 10px;
                                    font-size:20px;
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                            </style>
                            <table class='mycss' 
                            style="."width:100%".">
                                    <tr><strong>Transaction Details:</strong><br></tr>
                                    <tr>
                                        <td>Transaction status : </td>
                                        <td>".$response['status']."</td>
                                    </tr>
                                    <tr>
                                        <td>Easebuzz ID : </td>
                                        <td>".$response['easepayid']."</td>
                                    </tr>
                                    <tr>
                                        <td>Txn id : </td>
                                        <td>".$response['txnid']."</td>
                                    </tr>
                                    <tr>
                                        <td>Total Amount : </td>
                                        <td>".$order->order_total."</td>
                                    </tr>
                                    
                            </table>
                            <style>
                                table, th, td {
                                    color: black;
                                    background: #ffffff;
                                    padding: 10px;
                                    font-size:20px;
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                            </style>
                            <table class='mycss' 
                            style="."width:100%".">
                                    <tr><br><strong>Billing Address</strong><br></tr>
                                    <tr>
                                        <td>$order->billing_first_name $order->billing_last_name</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_address_1</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_address_2</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_city $order->billing_state </td>
                                    </tr>
                                    <tr>
                                        <td>&#128222; $order->billing_phone</td>
                                    </tr>
                                    <tr>
                                        <td>$order->billing_email</td>
                                    </tr>
                            </table>";
                            $order->add_order_note('Transaction ERROR: '.$response['error'].'<br/>Easebuzz ID: '.$response['easepayid'].' ('.$response['txnid'].')<br/>PG: '.$response['PG_TYPE'].'<br/>Bank Ref: '.$response['bank_ref_num'].'('.$response['mode'].')');
                        }
                    }else{
                        $return_response['class'] = 'error';
                        $return_response['message'] = "Security Error. Illegal access detected.";
                        $order->add_order_note('Checksum ERROR: '.json_encode($response));
                    }
                    if($trans_authorised==false){
                        $order->update_status('failed');
                    }
                }
                echo json_encode($return_response);
            }
            catch(Exception $e){
                $return_response['class'] = 'error';
                $return_response['message'] = "Oops, the transaction has been failed. Please try again";
                echo json_encode($return_response);
            }
        }else{
            //$redirect_new_url = $redirect_fail_url;
        }
    }
    exit();
}
