<?php
defined('ABSPATH') || exit;

/**
 * @class WPCF_Reports
 *
 * return various type of reports
 */

if ( !class_exists('WPCF_Reports') ) {

    class WPCF_Reports {
        /**
         * @var null
         *
         * Instance of this class
         */
        protected static $_instance = null;

        /**
         * @return null|WPCF
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct(){
            add_action('init', array($this, 'csv_export'));
            add_action('admin_menu', array($this, 'wpcf_add_reports_page'));
            // Add CSS & JS for this Addons
            add_action('admin_enqueue_scripts', array($this, 'wpcf_add_report_assets'));
            //Check is current page is crowdfunding report page or not
            if ( ! empty($_GET['page'])) {
                if($_GET['page'] === 'wpcf-reports'){
                    //Logic goes here...
                    include_once 'wpcf-reports-query.php';
                }
            }
        }

        public function wpcf_add_reports_page(){
            add_submenu_page('wpcf-crowdfunding', __('Reports', 'wp-crowdfunding-pro'), __('Reports', 'wp-crowdfunding-pro'), 'manage_options', 'wpcf-reports', array($this, 'WPCF_Reports'));
        }

        public function wpcf_add_report_assets($hook){
            if( 'wpcf-reports' !== $hook )
                return;

            wp_enqueue_script( 'field-date-js', plugin_dir_url( WPCF_REPORTS_FILE ).'assets/js/reports.js', array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), WPCF_PRO_VERSION, true );
            wp_register_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
            wp_enqueue_style( 'jquery-ui' );
        }

        /**
         *
         */
        public function WPCF_Reports() {

            //Defining page location into variable
            $default_file = WPCF_REPORTS_DIR_PATH.'pages/reports-area-chart.php';
            $sales_report_page = WPCF_REPORTS_DIR_PATH.'pages/reports-area-chart.php';
            $top_campaigns_page = WPCF_REPORTS_DIR_PATH.'pages/reports-by-campaign.php';

            // Settings Tab With slug and Display name
            $tabs = apply_filters('wpcf_reports_page_panel_tabs', array(
                    'sales_report' 	=>
                        array(
                            'tab_name' => __('Sales Report','wp-crowdfunding-pro'),
                            'load_form_file' => $sales_report_page
                        ),
                    'top_campaigns' 	=>
                        array(
                            'tab_name' => __('Top Campaigns','wp-crowdfunding-pro'),
                            'load_form_file' => $top_campaigns_page
                        )
                )
            );

            $current_page = 'sales_report';
            if( ! empty($_GET['tab']) ){
                $current_page = sanitize_text_field($_GET['tab']);
            }

            // Print the Tab Title
            echo '<h1 class="top-reports">'.__( "Crowdfunding Sales Reports" , "wp-crowdfunding" ).'</h1>';
            echo '<h2 class="nav-tab-wrapper">';
            foreach( $tabs as $tab => $name ){
                $class = ( $tab == $current_page ) ? ' nav-tab-active' : '';
                echo "<a class='nav-tab$class' href='?page=wpcf-reports&tab=$tab'>{$name['tab_name']}</a>";
            }
            echo '</h2>';

            //Load tab file
            $request_file = $tabs[$current_page]['load_form_file'];

            if (array_key_exists(trim(esc_attr($current_page)), $tabs)){
                if (file_exists($default_file)){
                    include_once $request_file;
                }else{
                    include_once $default_file;
                }
            } else {
                include_once $default_file;
            }
        }


        public function csv_export(){
            if (!empty($_GET['export_csv'])) {
                $file_name = 'csv-report-'.date('d-m-Y-h:i:s');
                if (! empty($_GET['file_name']))
                    $file_name = $_GET['file_name'];


                $file_name = strtolower(str_replace(' ', '-',trim($file_name)));

                // output headers so that the file is downloaded rather than displayed
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename='.$file_name.'.csv');

                $output = fopen('php://output', 'w');
                $csv = $_GET['export_csv'];
                $csv =  unserialize(str_replace('--', '"', $csv));

                foreach ($csv as $c) {
                    foreach ($c as $k => $v){
                        if (is_array($v)){
                            $c[$k] = implode($v);
                        }
                    }

                    fputcsv($output, $c);
                }
                exit;
            }
        }


    }
}
WPCF_Reports::instance();