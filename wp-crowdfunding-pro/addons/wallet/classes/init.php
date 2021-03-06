<?php
defined('ABSPATH') || exit;

if ( !class_exists('WPCF_Wallet') ) {

    class WPCF_Wallet {
        /**
         * @var null
         * $_instance
         */
        protected static $_instance = null;
        
        /**
         * @return null|WPCF_Wallet
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * WPNeo_Crowdfunding_Wallet constructor.
         */
        public function __construct() {
            //Add menu to frontend dashboard
            add_filter( 'wpcf_frontend_dashboard_menus', array($this, 'frontend_dashboard_menu_callback') );
            //Add admin menu to wp-crowdfunding for wallet
            add_action( 'admin_menu', array($this, 'withdraw_menu') );

            //Ajax request
            add_action( 'wp_ajax_request_paid', array($this, 'request_paid_callback') );
            add_action( 'wp_ajax_request_pending', array($this, 'request_pending_callback') );

            //Add css and js for wallet addons
            add_action( 'admin_enqueue_scripts', array($this, 'wpcf_wallet_assets') );
            add_action( 'wp_enqueue_scripts', array($this, 'wallet_assets_frontend') );

            add_filter( 'wpcf_settings_panel_tabs', array($this, 'wallet_setting_callback') ); //Hook to add social share field with user registration form
            add_action( 'admin_init', array($this, 'wallet_save_setting_callback') ); // Social Share Settings

            //After order complete
            //add_action('woocommerce_order_status_completed', array($this, 'wpneo_crowdfunding_after_order_complete'));

            add_action( 'woocommerce_process_product_meta', array($this, 'save_wallet_receiver_percent') );
            add_action( 'wpcf_after_campaign_email', array($this, 'save_wallet_receiver_percent') );

            //Withdraw ajax request
            add_action( 'wp_ajax_wpneo_crowdfunding_wallet_withdraw', array($this, 'wpcf_wallet_withdraw') );

            add_action( 'wp_loaded', array($this, 'add_deposit_amount_to_session') );
            add_action( 'woocommerce_before_calculate_totals', array($this, 'deposit_wallet_balance'), 20 );
            add_action( 'woocommerce_add_to_cart', array($this, 'remove_wallet_deposit_data_in_cart'), 10, 2 );

            //Clear User Deposit Meta Right After Checkout
	        add_action( 'woocommerce_checkout_order_processed', array($this, 'woocommerce_checkout_order_processed'), 10, 1 );

            //Deposit Form
            if( get_option( 'wpneo_enable_wallet', true ) == 'true' ){ // If Wallet Enable
                add_action( 'wpcf_dashboard_place_3', array($this, 'dashboard_place_callback') );
            }
            
	        add_filter( 'wpcf_frontend_dashboard_menus', array($this, 'dashboard_menus_link_add_deposits_page') );

        }

        /**
         * @param $hook
         *
         * wallet assets/ [css, javascript resources]
         */
        public function wpcf_wallet_assets($hook){
            wp_enqueue_style('wpcf-wallet-css',  plugin_dir_url( WPCF_WALLET_FILE ).'assets/css/wallet.css', array(), WPCF_PRO_VERSION);
            wp_enqueue_script('wpcf-wallet-js', plugin_dir_url( WPCF_WALLET_FILE ).'assets/js/wallet.js', array('jquery'), WPCF_PRO_VERSION, true );
        }

        public function wallet_assets_frontend(){
            wp_enqueue_style('wpcf-wallet-front-css',  plugin_dir_url( WPCF_WALLET_FILE ).'assets/css/wallet.css', array(), WPCF_PRO_VERSION);
            wp_enqueue_script('wpncf-wallet-front-js', plugin_dir_url( WPCF_WALLET_FILE ).'assets/js/wallet.js',array('jquery'), WPCF_PRO_VERSION, true );
        }

        /**
         * @param $menus
         * @return mixed
         *
         * @this method will add a menu to frontend dashboard :)
         */
        public function frontend_dashboard_menu_callback($menus){
            $menus['payments'] = array(
                'tab'           => 'campaign',
                'tab_name'      => __('Payments','wp-crowdfunding-pro'),
                'load_form_file'=> WPCF_WALLET_DIR_PATH.'pages/payments.php'
            );
            return $menus;
        }

        public function withdraw_menu(){
            add_submenu_page(
                'wpcf-crowdfunding', 
                __('Withdraw', 'wp-crowdfunding-pro'),
                __('Withdraw', 'wp-crowdfunding-pro'),
                'manage_options', 
                'wpcf-crowdfunding-withdraw', 
                array($this, 'withdraw_callback')
            );
        }

        /**
         * Table interface for wallet withdraw
         */
        public function withdraw_callback(){
            include WPCF_WALLET_DIR_PATH.'classes/wpcf_withdraw_request_table.php';

            echo '<div class="wpcf-withdraw-request-wrap">';
            echo '<div id="wpneo-fade" class="wpcf-message-overlay"></div>';
            echo '<h1>'.__( 'Withdraw Request','wp-crowdfunding-pro' ).'</h1>';
            new WPCF_Withdraw_Request_Table();
            echo '</div>';
        }

        public function request_paid_callback(){
            if(!empty( $_POST['postid'] ) ){
                update_post_meta( $_POST['postid'],'withdraw_request_status','paid');
            }
        }

        public function request_pending_callback(){
            if(!empty( $_POST['postid'] ) ){
                update_post_meta( $_POST['postid'],'withdraw_request_status','pending');
            }
        }

        public function wallet_setting_callback($tabs){
            $tabs['wallet'] = array(
                'tab_name' => __('Wallet','wp-crowdfunding-pro'),
                'load_form_file' => WPCF_WALLET_DIR_PATH.'pages/tab-wallet.php'
            );
            return $tabs;
        }

        /**
         * Wallet Settings
         */
        public function wallet_save_setting_callback(){
            if (isset($_POST['wpneo_admin_settings_submit_btn']) && isset($_POST['wpneo_varify_wallet_settings']) && wp_verify_nonce( $_POST['wpneo_settings_page_nonce_field'], 'wpneo_settings_page_action' ) ){
                // Checkbox
                $withdraw_type = sanitize_text_field(wpcf_function()->post('wpneo_wallet_withdraw_type'));
                wpcf_function()->update_text('wpneo_wallet_withdraw_type', $withdraw_type);

                $withdraw_period = sanitize_text_field(wpcf_function()->post('wpneo_wallet_withdraw_period'));
                wpcf_function()->update_text('wpneo_wallet_withdraw_period', $withdraw_period);

                $receiver_percent = sanitize_text_field(wpcf_function()->post('wallet_receiver_percent'));
                wpcf_function()->update_text('wallet_receiver_percent', $receiver_percent);

                $withdraw_amount = sanitize_text_field(wpcf_function()->post('walleet_min_withdraw_amount'));
                wpcf_function()->update_text('walleet_min_withdraw_amount', $withdraw_amount);

                /* $withdraw_methods = wpcf_function()->post('wpcf_withdraw_methods');
                wpcf_function()->update_checkbox('wpcf_withdraw_methods', $withdraw_methods); */

	            $wallet_deposit = sanitize_text_field(wpcf_function()->post('wpneo_enable_wallet_deposit'));
	            wpcf_function()->update_checkbox('wpneo_enable_wallet_deposit', $wallet_deposit);

	            $deposit_product_id = sanitize_text_field(wpcf_function()->post('wpneo_deposit_product_id'));
	            if ($deposit_product_id){
		            wpcf_function()->update_text('wpneo_deposit_product_id', $deposit_product_id);
	            }

            }
        }

        /**
         * @param $order_id
         */
        public function wpneo_crowdfunding_after_order_complete($order_id){
            global $wpdb;
            $order = new WC_Order($order_id);
            $line_items = $order->get_items('line_item');
            $ids = array();
            foreach ($line_items as $item_id => $item) {
                $product_id = $wpdb->get_var("select meta_value from {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$item_id} AND meta_key = '_product_id'");
                $ids[] = $product_id;
            }
            $product = wc_get_product($product_id);

            if ($product->get_type() === 'crowdfunding') {
                $order_sub_total = $order->get_subtotal();
                $reserved_payment_gateway = array( 'wpneo_stripe_connect');

                /**
                 * Determine this order is not placed by our payment gateway
                 */
                if ( ! in_array($order->payment_method, $reserved_payment_gateway)){
                    $wpcf_campaign_owner_percent = get_option('wallet_receiver_percent');

                    $campaigns_owner_amount = ($order_sub_total * $wpcf_campaign_owner_percent) / 100;
                    $wpcf_admin_amount = ($order_sub_total - $campaigns_owner_amount);

                    //Get deposit details
                    $date_format = date(get_option('date_format'));
                    $time_format = date(get_option('time_format'));
                    $campaigns_owner_id = $product->post->post_author;

                    $deposit_data = array(
                        'post_title'    => 'WP-Crowdfunding wallet deposit - '.$date_format.' @ '.$time_format,
                        'post_type'     =>'wpneo_deposit',
                        'post_status'   => 'wpcf-approved',
                        'post_author'   => $campaigns_owner_id,
                        'meta_input'    => array(
                            'wpcf_deposit_campaign_owner_percent'   => $wpcf_campaign_owner_percent,
                            'wpcf_deposit_campaign_owner_amount'    => $campaigns_owner_amount,
                            'wpcf_deposit_admin_amount'             => $wpcf_admin_amount,
                            'wpcf_deposit_for_campaign_id'          => $product_id,
                            'wpcf_deposit_for_order_id'             => $order_id,
                        ),
                    );

                    //Insert deposit data now
                    wp_insert_post($deposit_data);
                    //$wpdb->insert($wpdb->posts, $deposit_data);
                }
            }
        }


        /**
         * Save wallet receiver percent
         */
        public function save_wallet_receiver_percent($post_id){

            // wpneo_country
            $wallet_receiver_percent = (int) get_option('wallet_receiver_percent');
            add_post_meta($post_id, 'wpneo_wallet_receiver_percent', $wallet_receiver_percent);

        }

        /**
         * Withdraw will be here
         */
        public function wpcf_wallet_withdraw() {
            global $wpdb, $woocommerce;

            $campaign_id = (int) sanitize_text_field(wpcf_function()->post('campaign_id'));
            $requested_withdraw_amount = sanitize_text_field(wpcf_function()->post('withdraw_amount'));
            $withdraw_message = sanitize_text_field(wpcf_function()->post('withdraw_message'));
            $user_id = get_current_user_id();

            $date_format = date(get_option('date_format'));
            $time_format = date(get_option('time_format'));

            if ( ! class_exists('WC_Admin_Report'))
                include_once($woocommerce->plugin_path().'/includes/admin/reports/class-wc-admin-report.php');

            $wc_report = new WC_Admin_Report();
            $where_meta = array();
            $where_meta[] = array(
                'type' => 'order_item_meta',
                'meta_key' => '_product_id',
                'operator' => 'in',
                'meta_value' => array($campaign_id)
            );

            // Avoid max join size error
            $wpdb->query('SET SQL_BIG_SELECTS=1');

            $sold_products = $wc_report->get_order_report_data(array(
                'data' => array(
                    '_product_id' => array(
                        'type' => 'order_item_meta',
                        'order_item_type' => 'line_item',
                        'function' => '',
                        'name' => 'product_id'
                    ),
                    '_qty' => array(
                        'type' => 'order_item_meta',
                        'order_item_type' => 'line_item',
                        'function' => 'SUM',
                        'name' => 'quantity'
                    ),
                    '_line_subtotal' => array(
                        'type' => 'order_item_meta',
                        'order_item_type' => 'line_item',
                        'function' => 'SUM',
                        'name' => 'gross'
                    ),
                    '_line_total' => array(
                        'type' => 'order_item_meta',
                        'order_item_type' => 'line_item',
                        'function' => 'SUM',
                        'name' => 'gross_after_discount'
                    )
                ),
                'query_type' => 'get_results',
                'group_by' => 'product_id',
                'where_meta' => $where_meta,
                'order_by' => 'quantity DESC',
                //'limit' => 50,
                'order_types' => wc_get_order_types('order_count'),
                'order_status' => array('completed')
            ));

            $total_sales = 0;
            foreach ($sold_products as $product) {
                $total_sales = $product->gross;
            }

            $wpneo_wallet_receiver_percent = get_post_meta($campaign_id, 'wpneo_wallet_receiver_percent', true);
            $commission = ( $total_sales * $wpneo_wallet_receiver_percent ) / 100;

            $balance = $commission;

            //Get previous withdraw
            $withdraw_args = array(
                'post_type' => 'wpneo_withdraw',
                'post_parent'   => $campaign_id
            );

            $withdraw_query = new WP_Query($withdraw_args);
            if ($withdraw_query->have_posts()){
                $withdrawal_amount_array = array();

                while ($withdraw_query->have_posts()) {
                    $withdraw_query->the_post();

                    $withdrawal_amount = get_post_meta(get_the_ID(), 'wpneo_wallet_withdrawal_amount', true);
                    $withdrawal_amount_array[] = $withdrawal_amount;
                }

                //Get the last balance
                $balance = $commission - array_sum($withdrawal_amount_array);
            }

            //Compare if balance is greater then commission
            if ($requested_withdraw_amount <= $balance) {
                $deposit_data = array(
                    'post_title'    => __('Withdraw request', 'wp-crowdfunding-pro') . ' - '.$date_format.' @ '.$time_format,
                    'post_type'     => 'wpneo_withdraw',
                    'post_status'   => 'publish',
                    'post_author'   => $user_id,
                    'post_parent'   => $campaign_id,
                    'post_content'  => $withdraw_message,
                    'meta_input'    => array(
                        'wpneo_wallet_withdrawal_amount'    => $requested_withdraw_amount,
                    ),
                );
                //Insert deposit data now
                $post_id = wp_insert_post($deposit_data);

                if ($post_id) {
	                WC()->mailer(); // load email classes
                    do_action('wpcf_withdrawal_request_email', $post_id);
                }

                die(json_encode(array('success' => 1, 'msg' => __('Your withdraw request is processing', 'wp-crowdfunding-pro') )));
            }

            die(json_encode(array('success' => 0, 'msg' => __('You are not eligible to make a withdraw', 'wp-crowdfunding-pro') )));
        }


        public function add_deposit_amount_to_session(){
	        if ( !is_user_logged_in() ) {
		        return;
	        }

	        if (isset($_POST['deposit_amount'])){
		        $deposit_amount = wpcf_function()->post('deposit_amount');
	        	$user_id = get_current_user_id();
		        if ($user_id){
		        	$deposit_amount = wpcf_function()->post('deposit_amount');
			        $donate_amount = wpcf_function()->post('wpneo_donate_amount_field');

			        wc()->cart->empty_cart();

			        $deposit_data = json_encode( array(
				        'deposit_amount'    => $deposit_amount,
				        'checkout_type'     => 'wpneo_wallet_deposit')
			        );

			        update_user_meta($user_id, 'wpneo_wallet_info', $deposit_data);

			        $default_checkout_product = get_option('wpneo_deposit_product_id');
			        wc()->cart->add_to_cart($default_checkout_product);


			        $checkout_url   = wc_get_checkout_url();
			        $preferance     = get_option('wpneo_crowdfunding_add_to_cart_redirect');

			        if ($preferance == 'cart_page'){
				        $checkout_url = wc_get_cart_url();
			        }

			        wp_redirect($checkout_url);
			        die();
		        }
	        }
        }


	    public function deposit_wallet_balance(){
        	if ( ! is_user_logged_in()){
        		return;
	        }
		    $user_id = get_current_user_id();
		    $deposit_info = get_user_meta($user_id, 'wpneo_wallet_info', true);

		    if ($deposit_info){
			    $deposit_info = json_decode($deposit_info);

			    if (isset($deposit_info->deposit_amount) && $deposit_info->deposit_amount){
				    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					    $cart_item['data']->set_price($deposit_info->deposit_amount);
				    }
			    }
		    }
	    }

	    public function remove_wallet_deposit_data_in_cart( $cart_item_key, $product_id){
		    if ( ! is_user_logged_in()){
			    return;
		    }
		    $user_id = get_current_user_id();

		    $product_id = (int) $product_id;
		    $default_checkout_product = (int) get_option('wpneo_deposit_product_id');

		    if ($product_id !== $default_checkout_product){
			    delete_user_meta($user_id,'wpneo_wallet_info');
		    }
	    }

	    /**
	     * Delete User Meta for removing deposit amount information
	     * Add Order as User Deposit
	     */
	    public function woocommerce_checkout_order_processed($order_id){
		    if ( ! is_user_logged_in()){
			    return;
		    }
		    $user_id = get_current_user_id();
		    $deposit_info = get_user_meta($user_id, 'wpneo_wallet_info', true);

		    if ($deposit_info) {
			    $deposit_info = json_decode( $deposit_info );

			    if (count($deposit_info)){
				    update_post_meta($order_id, 'wpcrowdfunding_wallet_deposit', $deposit_info->deposit_amount);
			    }
			    delete_user_meta($user_id,'wpneo_wallet_info');
		    }

	    }

	    /**
	     * @param int $user_id
	     *
	     * @return int|null|string
         *
         * Get Total Deposit Amount
	     */
	    public function get_total_deposit_amount($user_id = 0){
		    global $wpdb;
		    if ( ! $user_id){
			    $user_id = get_current_user_id();
		    }

		    $args = array(
			    'numberposts' => - 1,
			    'post_type'   => array( 'shop_order' ),
			    'post_status' => array( 'wc-completed' ),
                'meta_query' => array(
				    'relation' => 'AND',
				    array(
					    'key'     => 'wpcrowdfunding_wallet_deposit',
					    'value'   => '0',
					    'compare' => '>',
				    ),
				    array(
					    'key'     => '_customer_user',
					    'value'   => $user_id,
					    'compare' => '=',
				    ),
			    ),
		    );

		    $deposit_orders = get_posts($args);
		    $deposits_amount = 0;

		    if (is_array($deposit_orders) && count($deposit_orders)){
		        $deposit_ids = array();
		        foreach ($deposit_orders as $order){
			        $deposit_ids[] = $order->ID;
                }
                $deposit_ids = implode(',', $deposit_ids);

			    $deposits_amount = $wpdb->get_var("select SUM(meta_value) from {$wpdb->postmeta} WHERE post_id IN({$deposit_ids}) AND meta_key = 'wpcrowdfunding_wallet_deposit' ");
            }

		    return $deposits_amount;
	    }

	    /**
	     * @param int $user_id
	     *
	     * @return int
         *
         * Get Backed Amount
	     */
	    public function get_total_debited_amount($user_id = 0){
		    global $wpdb;
		    if ( ! $user_id){
			    $user_id = get_current_user_id();
		    }
		    $args = array(
			    'numberposts' => - 1,
			    'post_type'   => array( 'shop_order' ),
			    'post_status' => array( 'wc-completed' ),
			    'meta_query' => array(
				    'relation' => 'AND',
				    array(
					    'key'     => 'wpcrowdfunding_wallet_debited',
					    'value'   => '0',
					    'compare' => '>',
				    ),
				    array(
					    'key'     => '_customer_user',
					    'value'   => $user_id,
					    'compare' => '=',
				    ),
			    ),
		    );

		    $debited_orders = get_posts($args);
		    $debited_amount = 0;

		    if (is_array($debited_orders) && count($debited_orders)){
			    $deposit_ids = array();
			    foreach ($debited_orders as $order){
				    $deposit_ids[] = $order->ID;
			    }
			    $deposit_ids = implode(',', $deposit_ids);

			    $debited_amount = $wpdb->get_var("select SUM(meta_value) from {$wpdb->postmeta} WHERE post_id IN({$deposit_ids}) AND meta_key = 'wpcrowdfunding_wallet_debited' ");
		    }
		    return (int) $debited_amount;
	    }

	    /**
	     * @param int $user_id
	     *
	     * @return int|null|string
         *
         * Get Current Balance
	     */
	    public function current_balance($user_id = 0){
		    if ( ! $user_id){
			    $user_id = get_current_user_id();
		    }

		    $total_deposited = $this->get_total_deposit_amount($user_id);
		    $total_debited = $this->get_total_debited_amount($user_id);

		    return ($total_deposited - $total_debited);
	    }

	    public function dashboard_place_callback() {
	    	?>
		    <div class="wpneo-content wpneo-shadow wpneo-padding25 margin-bottom-20 wpneo-clearfix">
			    <h4><?php _e('Balance', 'wp-crowdfunding-pro') ?></h4>
			    <hr />

			    <p>
				    <?php _e('Current Balance', 'wp-crowdfunding-pro');
				    echo ' : ';
				    echo wc_price( $this->current_balance() ); ?>
			    </p>

			    <form action="" method="post">
				    <div class="wpneo-single">
					    <div class="wpneo-name float-left">
						    <p><?php _e('Deposit Amount', 'wp-crowdfunding-pro'); ?> (<?php echo get_woocommerce_currency() ?>) </p>
					    </div>
					    <div class="wpneo-fields float-right">
						    <input type="text" name="deposit_amount" value="50" />
					    </div>

					    <button type="submit" id="add-balance-btn" class="wpneo-edit-btn margin-top-0"><?php _e('Add Balance', 'wp-crowdfunding-pro');
						    ?></button>
				    </div>
			    </form>
		    </div>
	    	<?php
	    }

	    public function dashboard_menus_link_add_deposits_page($menus){
		    $menus['deposits'] = array(
			    'tab' => 'campaign',
			    'tab_name' => __('Deposits','wp-crowdfunding-pro'),
			    'load_form_file' => WPCF_WALLET_DIR_PATH.'pages/deposits.php'
		    );
		    $menus['backed_campaign'] = array(
			    'tab' => 'campaign',
			    'tab_name' => __('Backed Campaign','wp-crowdfunding-pro'),
			    'load_form_file' => WPCF_WALLET_DIR_PATH.'pages/backed_campaign.php'
		    );

		    return $menus;
        }


    }
}

$GLOBALS['wpcf_wallet'] = new WPCF_Wallet();

include_once  WPCF_WALLET_DIR_PATH.'classes/wpcf_gateway_wallet.php';