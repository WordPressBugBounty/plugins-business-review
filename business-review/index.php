<?php
/**
 * Plugin Name: Business Reviews – Display Customer Reviews from Popular Sites
 * Description: Simple and easy way display your Google ,Facebook and yelp business reviews in your Posts and Pages.
 * Version: 1.0.18
 * Author: bPlugins
 * Author URI: http://bplugins.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: business-review
 */

// ABS PATH
if (!defined('ABSPATH')) {exit;}

if (function_exists('grbb_fs')) {
     grbb_fs()->set_basename( true, __FILE__ );
} else {

    define( 'GRBB_PLUGIN_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ? time() : '1.0.18' );
    define('GRBB_DIR', plugin_dir_url(__FILE__));
    define('GRBB_ASSETS_DIR', plugin_dir_url(__FILE__) . 'assets/');
    define('GRBB_DIR_PATH', plugin_dir_path(__FILE__));

    if ( ! function_exists( 'grbb_fs' ) ) {
    // Create a helper function for easy SDK access.
    function grbb_fs() {
        global $grbb_fs;

        if ( ! isset( $grbb_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/vendor/freemius-lite/start.php';

            $brConfig = array(
                'id'                  => '12846',
                'slug'                => 'business-review',
                'type'                => 'plugin',
                'public_key'          => 'pk_fc967390d964ec916d711a9a03a91',
                'is_premium'          => false,
                'menu' =>    array(
                        'slug'           => 'edit.php?post_type=grbb',
                        'first-path'     => 'edit.php?post_type=grbb&page=business-review#/pricing',
                        'support'        => false,
                )
            );
            $grbb_fs = fs_lite_dynamic_init( $brConfig );
        }
        return $grbb_fs;
    }
    // Init Freemius.
    grbb_fs();
    // Signal that SDK was initiated.
    do_action( 'grbb_fs_loaded' );
}

    class GRBB_Business_Review {

        private static $instance;

        private function __construct()
        {  
            $this->load_classes();
            add_action('enqueue_block_assets', [$this, 'enqueueBlockAssets']);
            add_action('init', [$this, 'onInit']);
            add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
             
            add_filter('plugin_row_meta', array($this, 'insert_plugin_row_meta'), 10, 2);

            add_action('admin_init', [$this, 'register_my_setting']);
            add_action('rest_api_init', [$this, 'register_my_setting']);
            add_filter( 'plugin_action_links', [$this, 'plugin_action_links'], 10, 2 );
        }

        public function plugin_action_links($links, $file) {
            
            if( plugin_basename( __FILE__ ) == $file ) {
                $links['go_pro'] = sprintf( '<a href="%s" style="%s" target="__blank">%s</a>', 'https://bplugins.com/products/business-reviews/#pricing', 'color:#4527a4;font-weight:bold', __( 'Go Pro!', 'business-review' ) );
            }

            return $links;
        }

        // Extending row meta 
        public function insert_plugin_row_meta($links, $file)
        {
            if (plugin_basename( __FILE__ ) == $file) {
                // docs & faq
                $links[] = sprintf('<a href="https://bplugins.com/docs/business-reviews" target="_blank">' . __('Docs & FAQs', 'business-review') . '</a>');

                // Demos
                $links[] = sprintf('<a href="https://bplugins.com/products/business-reviews/#demos" target="_blank">' . __('Demos', 'business-review') . '</a>');
            }

            return $links;
        }

        public static function get_instance() {
            if (self::$instance) {
                return self::$instance;
            }

            self::$instance = new self();
            return self::$instance;
        }
 
        public function load_classes() {
            require_once plugin_dir_path(__FILE__) . 'includes/api/BusinessReviewAPI.php';
            require_once plugin_dir_path(__FILE__) . 'includes/custom-post.php';
            new GRBBB_CPT\Business_Review_Custom_Post_Type();
            require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
        }

        public function register_my_setting()
        {
            register_setting('grbb_apis', 'grbb_apis', array(
                'show_in_rest' => array(
                    'name' => 'grbb_apis',
                    'schema' => array(
                        'type' => 'string',
                    ),
                ),
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ));
        }

        public function adminEnqueueScripts($hook)
        {
            if ('edit.php' === $hook || 'post.php' === $hook) {
                wp_enqueue_style('grbbAdmin', GRBB_ASSETS_DIR . 'css/admin.css', [], GRBB_PLUGIN_VERSION);
                wp_enqueue_script('grbbAdmin', GRBB_ASSETS_DIR . 'js/admin.js', ['wp-i18n'], GRBB_PLUGIN_VERSION, true);
            }
        }

        public function enqueueBlockAssets()
        {
            wp_register_style('fontAwesome', GRBB_ASSETS_DIR . 'css/fontAwesome.min.css', [], GRBB_PLUGIN_VERSION);
            wp_register_script('MiniMasonry', GRBB_ASSETS_DIR . 'js/masonry.min.js', [], '1.3.1', true);

            wp_localize_script('MiniMasonry', 'grbbData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);

            wp_localize_script('MiniMasonry', 'grbbData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);
        }

        public function onInit()
        {
            register_block_type( __DIR__ . '/build' );
        }
    }
    GRBB_Business_Review::get_instance();
}