<?php
/**
 * Plugin Name: Business Review
 * Description: Simple and easy way display your Google ,Facebook and yelp business reviews in your Posts and Pages.
 * Version: 1.0.8
 * Author: bPlugins
 * Author URI: http://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: business-review
 */

// ABS PATH
if (!defined('ABSPATH')) {exit;}

// Constant
define('GRBB_PLUGIN_VERSION', 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.8');
define('GRBB_DIR', plugin_dir_url(__FILE__));
define('GRBB_ASSETS_DIR', plugin_dir_url(__FILE__) . 'assets/');

if (!function_exists('grbb_init')) {
    function grbb_init()
    {
        global $grbb_bs;
        require_once plugin_dir_path(__FILE__) . 'bplugins_sdk/init.php';
        $grbb_bs = new BPlugins_SDK(__FILE__);
    }
    grbb_init();
} else {
    $grbb_bs->uninstall_plugin(__FILE__);
}

// Business Review
class GRBBBusinessReview
{
    public function __construct()
    {
        add_action('enqueue_block_assets', [$this, 'enqueueBlockAssets']);
        add_action('init', [$this, 'onInit']);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
        add_action('admin_init', [$this, 'register_my_setting']);
        add_action('rest_api_init', [$this, 'register_my_setting']);
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
        wp_register_style('fontAwesome', GRBB_ASSETS_DIR . 'css/fontAwesome.min.css', [], GRBB_PLUGIN_VERSION); // Font Awesome

        wp_register_style('grbb-style', GRBB_DIR . 'dist/style.css', ['fontAwesome'], GRBB_PLUGIN_VERSION); // Frontend Style
        wp_register_script('MiniMasonry', GRBB_ASSETS_DIR . 'js/masonry.min.js', [], '1.3.1');
        wp_register_script('grbb-script', GRBB_DIR . 'dist/script.js', ['react', 'react-dom', 'jquery', 'MiniMasonry'], GRBB_PLUGIN_VERSION);

        wp_localize_script('grbb-script', 'grbbData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        wp_localize_script('grbb-business-review-editor-script', 'grbbData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function onInit()
    {
        wp_register_style('grbb-business-review-editor-style', plugins_url('dist/editor.css', __FILE__), ['grbb-style'], GRBB_PLUGIN_VERSION); // Backend Style

        register_block_type(__DIR__, [
            'editor_style' => 'grbb-business-review-editor-style',
            'render_callback' => [$this, 'render'],
        ]); // Register Block

        wp_set_script_translations('grbb-business-review-editor-script', 'business-review', plugin_dir_path(__FILE__) . 'languages'); // Translate
    }

    public function render($attributes)
    {
        extract($attributes);

        $className = $className ?? '';
        $grbbBlockClassName = 'wp-block-grbb-business-review ' . $className . ' align' . $align;
        wp_enqueue_style('grbb-style');
        wp_enqueue_script('grbb-script');

        ob_start();?>
		<div class='<?php echo esc_attr($grbbBlockClassName); ?>' id='grbbBusinessReview-<?php echo esc_attr($cId) ?>' data-attributes='<?php echo esc_attr(wp_json_encode($attributes)); ?>'></div>

		<?php return ob_get_clean();
    } // Render
}
new GRBBBusinessReview();

require_once plugin_dir_path(__FILE__) . '/api/BusinessReviewAPI.php';
require_once plugin_dir_path(__FILE__) . '/custom-post.php';
