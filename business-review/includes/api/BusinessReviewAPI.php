<?php
if (!defined('ABSPATH')) {exit;}
class Business_Review_Api
{
    private $fb_limit = 6;
    private $apiData = [];

    public function __construct()
    {
        add_action('wp_ajax_get_all_reviews', [$this, 'grbb_get_all_reviews']);
        add_action('wp_ajax_nopriv_get_all_reviews', [$this, 'grbb_get_all_reviews']);

        add_action('wp_ajax_grbb_get_access_token', [$this, 'get_access_token']);
        add_action('wp_ajax_nopriv_grbb_get_access_token', [$this, 'get_access_token']);

        add_action('wp_ajax_grbb_remove_cache', [$this, 'grbb_remove_cache']);
        $this->apiData = json_decode(get_option('grbb_apis'), true);
    }
 
    public function get_access_token()
    {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_die();
        }

        // Retrieving/storing the Facebook access token is an admin-only action.
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';

        $response = wp_remote_get('https://api.bplugins.com/wp-json/facebook/v1/get-token?state=' . rawurlencode($state));

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo wp_remote_retrieve_body($response);
        wp_die();
    }

    public function grbb_get_all_reviews()
    {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_die();
        }
        $reviews = [];

        if (isset($_GET['platform'])) {

            $platform = sanitize_text_field(wp_unslash($_GET['platform']));
            if (strpos($platform, 'facebook') !== false) {
                $reviews['facebook'] = $this->getFBReviews();
            }
            if (strpos($platform, 'yelp') !== false) {
                $reviews['yelp'] = $this->getYelpReviews();
            }
            if (strpos($platform, 'google') !== false) {
                $reviews['google'] = $this->getGoogleReviews();
            }
        }
        $reviews['errors'] = get_option('grbb_api_errors', []);

        wp_send_json($reviews);
    }

    private function setApiError($platform, $message = null)
    {
        $errors = get_option('grbb_api_errors', []);
        if (!is_array($errors)) {
            $errors = [];
        }
        if ($message) {
            $errors[$platform] = $message;
        } else {
            unset($errors[$platform]);
        }
        update_option('grbb_api_errors', $errors);
    }

    public function getYelpReviews()
    {

        $reviews = get_transient('grbb_yelp_reviews');
        $data = json_decode(get_option('grbb_apis'), true);
        $yelpKey = $data['yelpKey'] ?? '';
        $yelpBusinessUrl = $data['yelpBusinessURL'] ?? '';

        if (!$yelpKey || !$yelpBusinessUrl) {
            // delete_transient( 'grbb_yelp_reviews' );
            $this->setApiError('yelp');
            return [];
        }

        if (false === $reviews) {
            $path = trim((string) parse_url($yelpBusinessUrl, PHP_URL_PATH), '/');
            $segments = $path ? explode('/', $path) : [];
            $bizIndex = array_search('biz', $segments, true);
            $alias = false !== $bizIndex ? ($segments[$bizIndex + 1] ?? '') : '';

            if (!$alias) {
                $this->setApiError('yelp', __('Invalid Yelp Business URL. Expected a URL like https://www.yelp.com/biz/your-business-name.', 'business-review'));
                return [];
            }

            $url = "https://api.yelp.com/v3/businesses/" . rawurlencode($alias) . "/reviews?limit=20&sort_by=yelp_sort";

            $header_args = array(
                'headers' => array(
                    'user-agent' => 'business-reviews-wp',
                    'Authorization' => "Bearer " . $yelpKey,
                ),
            );
            $response = wp_remote_get($url, $header_args);

            if (is_wp_error($response)) {
                $this->setApiError('yelp', $response->get_error_message());
                return [];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                $this->setApiError('yelp', $body['error']['description'] ?? $body['error']['code'] ?? __('Unknown Yelp API error.', 'business-review'));
                return [];
            }

            $reviews = $body['reviews'] ?? [];
            $this->setApiError('yelp');
            set_transient('grbb_yelp_reviews', $reviews, 60 * 60 * 24);
        }
        return $reviews;

    }

    public function getGoogleReviews()
    {
        $reviews = get_transient('grbb_google_reviews');
        $data = json_decode(get_option('grbb_apis'), true);
        $placeId = $data['googlePlaceID'] ?? '';
        $key = $data['googlePlaceKey'] ?? '';

        if (!$placeId || !$key) {
            // delete_transient( 'grbb_google_reviews' );
            $this->setApiError('google');
            return [];
        }

        if (false === $reviews) {
            $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . rawurlencode($placeId) . "&fields=reviews&reviews_no_translations=true&key=" . rawurlencode($key);

            $response = wp_remote_get($url);

            if (is_wp_error($response)) {
                $this->setApiError('google', $response->get_error_message());
                return [];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['status']) && 'OK' !== $body['status']) {
                $this->setApiError('google', $body['error_message'] ?? $body['status']);
                return [];
            }

            $reviews = $body['result']['reviews'] ?? [];
            $this->setApiError('google');
            set_transient('grbb_google_reviews', $reviews, 60 * 60 * 24);
        }

        return $reviews;

    }

    public function getFBReviews()
    {
        $reviews = get_transient('grbb_fb_reviews');

        $data = json_decode(get_option('grbb_apis'), true);
        $accessToken = $data['fbAccessToken'] ?? false;
        $pageAccessToken = $data['fbPageAccessToken'] ?? false;
        $pageID = $data['pageID'] ?? false;

        if (!$accessToken) {
            // delete_transient( 'grbb_fb_reviews' );
            return [];
        }

        if (!$reviews) {
            if ((!$pageAccessToken || !$pageID) && $accessToken) {
                $url = "https://graph.facebook.com/me/accounts?access_token=$accessToken&limit=250";
                $response = wp_remote_get($url);
                $data = json_decode(wp_remote_retrieve_body($response), true);

                foreach ($data['data'] as $page) {
                    if (isset($page['access_token'])) {
                        $pageAccessToken = $page['access_token'] ?? false;
                        $pageID = $page['id'] ?? false;
                    }
                }
            }

            if ($pageAccessToken && $pageID) {
                $url = "https://graph.facebook.com/v15.0/$pageID/ratings?access_token=$pageAccessToken&fields=reviewer{id,name,picture.width(120).height(120)},created_time,rating,recommendation_type,review_text&limit=$this->fb_limit";

                $response = wp_remote_get($url);
                $data = json_decode(wp_remote_retrieve_body($response), true);

                $reviews = $data['data'] ?? [];
                set_transient('grbb_fb_reviews', $reviews, 60 * 60 * 24);
            } else {
                $reviews = [];
            }
        }

        foreach ($reviews as $key => $review) {
            if (isset($reviews[$key]['review_text']) && str_contains($reviews[$key]['review_text'], '<')) {
                $reviews[$key]['review_text'] = str_replace('<', "", $reviews[$key]['review_text']);
            }
        }

        return $reviews;
    }

    public function grbb_remove_cache()
    {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_die();
        }

        // Clearing the cache is a management action; restrict it to capable users.
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        delete_transient('grbb_yelp_reviews');
        delete_transient('grbb_google_reviews');
        delete_transient('grbb_fb_reviews');

        echo wp_json_encode(['success' => true]);
        wp_die();
    }
}
new Business_Review_Api();
