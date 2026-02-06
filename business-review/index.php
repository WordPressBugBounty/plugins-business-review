<?php

/**
 * Plugin Name: Business Review
 * Description: Simple and easy way display your Google ,Facebook and yelp business reviews in your Posts and Pages.
 * Version: 1.0.16
 * Author: bPlugins
 * Author URI: http://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: business-review
 * @fs_free_only, /freemius-lite, bsdk_config.json, /includes/admin-menu-free.php
 */
// ABS PATH
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'br_fs' ) ) {
    br_fs()->set_basename( false, __FILE__ );
} else {
    define( 'GRBB_PLUGIN_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.16' ) );
    define( 'GRBB_DIR', plugin_dir_url( __FILE__ ) );
    define( 'GRBB_ASSETS_DIR', plugin_dir_url( __FILE__ ) . 'assets/' );
    define( 'GRBB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'BR_IS_PRO', file_exists( dirname( __FILE__ ) . '/freemius/start.php' ) );
    if ( !function_exists( 'br_fs' ) ) {
        // Create a helper function for easy SDK access.
        function br_fs() {
            global $br_fs;
            if ( !isset( $br_fs ) ) {
                // Include Freemius SDK.
                if ( BR_IS_PRO ) {
                    require_once dirname( __FILE__ ) . '/freemius/start.php';
                } else {
                    require_once dirname( __FILE__ ) . '/freemius-lite/start.php';
                }
                $brConfig = array(
                    'id'                  => '12846',
                    'slug'                => 'business-review',
                    'premium_slug'        => 'business-review-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_fc967390d964ec916d711a9a03a91',
                    'is_premium'          => false,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'trial'               => array(
                        'days'               => 7,
                        'is_require_payment' => false,
                    ),
                    'menu'                => ( BR_IS_PRO ? array(
                        'slug'       => 'business-review',
                        'first-path' => 'admin.php?page=business-review#/pricing',
                        'support'    => false,
                    ) : array(
                        'slug'       => 'business-review',
                        'first-path' => 'tools.php?page=business-review#/pricing',
                        'support'    => false,
                        'parent'     => array(
                            'slug' => 'tools.php',
                        ),
                    ) ),
                );
                $br_fs = ( BR_IS_PRO ? fs_dynamic_init( $brConfig ) : fs_lite_dynamic_init( $brConfig ) );
            }
            return $br_fs;
        }

        // Init Freemius.
        br_fs();
        // Signal that SDK was initiated.
        do_action( 'br_fs_loaded' );
    }
    function brIsPremium() {
        return ( BR_IS_PRO ? br_fs()->can_use_premium_code() : false );
    }

    class GRBB_Business_Review {
        private static $instance;

        private function __construct() {
            $this->load_classes();
            add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
            add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
            add_action( 'init', [$this, 'onInit'] );
            add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
            add_filter(
                'plugin_row_meta',
                array($this, 'insert_plugin_row_meta'),
                10,
                2
            );
            add_action( 'admin_init', [$this, 'register_my_setting'] );
            add_action( 'rest_api_init', [$this, 'register_my_setting'] );
            if ( !brIsPremium() ) {
                add_filter(
                    'plugin_action_links',
                    [$this, 'plugin_action_links'],
                    10,
                    2
                );
            }
        }

        public function plugin_action_links( $links, $file ) {
            if ( plugin_basename( __FILE__ ) == $file ) {
                $links['go_pro'] = sprintf(
                    '<a href="%s" style="%s" target="__blank">%s</a>',
                    'https://bplugins.com/products/business-reviews/#pricing',
                    'color:#4527a4;font-weight:bold',
                    __( 'Go Pro!', 'business-review' )
                );
            }
            return $links;
        }

        // Extending row meta
        public function insert_plugin_row_meta( $links, $file ) {
            if ( plugin_basename( __FILE__ ) == $file ) {
                // docs & faq
                $links[] = sprintf( '<a href="https://bplugins.com/docs/business-reviews" target="_blank">' . __( 'Docs & FAQs', 'business-review' ) . '</a>' );
                // Demos
                $links[] = sprintf( '<a href="https://bplugins.com/products/business-reviews/#demos" target="_blank">' . __( 'Demos', 'business-review' ) . '</a>' );
            }
            return $links;
        }

        public static function get_instance() {
            if ( self::$instance ) {
                return self::$instance;
            }
            self::$instance = new self();
            return self::$instance;
        }

        public function load_classes() {
            require_once plugin_dir_path( __FILE__ ) . '/api/BusinessReviewAPI.php';
            if ( BR_IS_PRO ) {
                require_once plugin_dir_path( __FILE__ ) . '/includes/admin-menu-pro.php';
            } else {
                require_once plugin_dir_path( __FILE__ ) . '/includes/admin-menu-free.php';
            }
            if ( BR_IS_PRO && brIsPremium() ) {
                require_once plugin_dir_path( __FILE__ ) . '/custom-post.php';
                new GRBBB_CPT\Business_Review_Custom_Post_Type();
            }
        }

        public function register_my_setting() {
            register_setting( 'grbb_apis', 'grbb_apis', array(
                'show_in_rest'      => array(
                    'name'   => 'grbb_apis',
                    'schema' => array(
                        'type' => 'string',
                    ),
                ),
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ) );
        }

        public function adminEnqueueScripts( $hook ) {
            if ( 'edit.php' === $hook || 'post.php' === $hook ) {
                wp_enqueue_style(
                    'grbbAdmin',
                    GRBB_ASSETS_DIR . 'css/admin.css',
                    [],
                    GRBB_PLUGIN_VERSION
                );
                wp_enqueue_script(
                    'grbbAdmin',
                    GRBB_ASSETS_DIR . 'js/admin.js',
                    ['wp-i18n'],
                    GRBB_PLUGIN_VERSION,
                    true
                );
            }
        }

        public function enqueueBlockAssets() {
            wp_register_style(
                'fontAwesome',
                GRBB_ASSETS_DIR . 'css/fontAwesome.min.css',
                [],
                GRBB_PLUGIN_VERSION
            );
            wp_register_script(
                'MiniMasonry',
                GRBB_ASSETS_DIR . 'js/masonry.min.js',
                [],
                '1.3.1'
            );
            wp_localize_script( 'MiniMasonry', 'grbbData', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
            ] );
            wp_localize_script( 'MiniMasonry', 'grbbData', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
            ] );
        }

        public function onInit() {
            register_block_type( __DIR__ . '/build' );
        }

        public function enqueueBlockEditorAssets() {
            wp_add_inline_script( 'grbb-business-review-editor-script', "const brpipecheck=" . wp_json_encode( brIsPremium() ) . ';', 'before' );
        }

    }

    GRBB_Business_Review::get_instance();
}