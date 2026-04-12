<?php

if (!defined('ABSPATH')) {exit;}
if(!class_exists('brAdminMenu')) {

    class brAdminMenu {

        public function __construct() {
            add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
            add_action( 'admin_menu', [$this, 'adminMenu'] );
        }

        public function adminEnqueueScripts($hook) {
            // if( strpos( $hook, 'business-review' ) ){
                wp_enqueue_style( 'grbb-admin-dashboard', GRBB_DIR . 'build/admin-dashboard.css', [], GRBB_PLUGIN_VERSION );
                wp_enqueue_script( 'grbb-admin-dashboard', GRBB_DIR . 'build/admin-dashboard.js', [ 'react', 'react-dom', 'wp-data', "wp-api", "wp-util", "wp-i18n"], GRBB_PLUGIN_VERSION, true );
                wp_set_script_translations( 'grbb-admin-dashboard', 'business-review', GRBB_DIR_PATH . 'languages' );   
            // }
        }

        public function adminMenu(){
            $menuIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='24px' height='24px' fill='#fff' viewBox='0 0 24 24'><path d='M10.3 6.74a.75.75 0 01-.04 1.06l-2.908 2.7 2.908 2.7a.75.75 0 11-1.02 1.1l-3.5-3.25a.75.75 0 010-1.1l3.5-3.25a.75.75 0 011.06.04zm3.44 1.06a.75.75 0 111.02-1.1l3.5 3.25a.75.75 0 010 1.1l-3.5 3.25a.75.75 0 11-1.02-1.1l2.908-2.7-2.908-2.7z' /><path fillRule='evenodd' d='M1.5 4.25c0-.966.784-1.75 1.75-1.75h17.5c.966 0 1.75.784 1.75 1.75v12.5a1.75 1.75 0 01-1.75 1.75h-9.69l-3.573 3.573A1.457 1.457 0 015 21.043V18.5H3.25a1.75 1.75 0 01-1.75-1.75V4.25zM3.25 4a.25.25 0 00-.25.25v12.5c0 .138.112.25.25.25h2.5a.75.75 0 01.75.75v3.19l3.72-3.72a.75.75 0 01.53-.22h10a.25.25 0 00.25-.25V4.25a.25.25 0 00-.25-.25H3.25z' /></svg>";

             

            
            add_submenu_page(
                'edit.php?post_type=grbb',
                __('Demo and Help', 'business-review'),
                __('Demo and Help', 'business-review'),
                'manage_options',
                'business-review',
                [$this, 'grbbHelpPage']
            );   
            
        }

        public function grbbHelpPage()
        {?>
            <div
                id='grbbDashboard'
                data-info='<?php echo esc_attr( wp_json_encode( [
                    'version' => GRBB_PLUGIN_VERSION,
                    'isPremium' => brIsPremium(),
                    'hasPro' => BR_IS_PRO,
                    'nonce' => wp_create_nonce( 'apbCreatePage' ),
		            'licenseActiveNonce' => wp_create_nonce( 'bPlLicenseActivation' )
                ] ) ); ?>'
            >
            </div>
        <?php } 
    }
    new brAdminMenu();
}