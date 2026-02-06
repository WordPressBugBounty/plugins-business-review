<?php

if (!defined('ABSPATH')) {exit;}
if(!class_exists('brAdminMenu')) {

    class brAdminMenu {

        public function __construct() {
            add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
            add_action( 'admin_menu', [$this, 'adminMenu'] );
        }

        public function adminEnqueueScripts($hook) {
            if( strpos( $hook, 'business-review' ) ){
                wp_enqueue_style( 'grbb-admin-dashboard', GRBB_DIR . 'build/admin-dashboard.css', [], GRBB_PLUGIN_VERSION );
                wp_enqueue_script( 'grbb-admin-dashboard', GRBB_DIR . 'build/admin-dashboard.js', [ 'react', 'react-dom',], GRBB_PLUGIN_VERSION, true );
                wp_set_script_translations( 'grbb-admin-dashboard', 'business-review', GRBB_DIR_PATH . 'languages' );   
            }
        }

        public function adminMenu(){

            add_submenu_page(
                'tools.php',
                __('Business review', 'business-review'),
                __('Business review', 'business-review'),
                'manage_options',
                'business-review',
                [$this, 'brHelpPage']
            ); 
        }

        public function brHelpPage()
        {?>
            <div
                id='grbbDashboard'
                data-info='<?php echo esc_attr( wp_json_encode( [
                    'version' => GRBB_PLUGIN_VERSION,
                    'isPremium' => brIsPremium(),
                    'hasPro' => BR_IS_PRO
                ] ) ); ?>'
            >
            </div>
        <?php } 
    }
    new brAdminMenu();
}