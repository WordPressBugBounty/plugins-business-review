<?php

class BusinessReviewAPI
{

    /**
     * Initialize the class
     */

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
        if (!wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'wp_rest')) {
            wp_die();
        }
        $state = sanitize_text_field($_GET['state']) ?? '';

        $response = wp_remote_get("https://api.bplugins.com/wp-json/facebook/v1/get-token?state=$state");
        echo wp_remote_retrieve_body($response);
        wp_die();

    }

    public function grbb_get_all_reviews()
    {
        if (!wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'wp_rest')) {
            wp_die();
        }
        $reviews = [];

        if (isset($_GET['platform'])) {
            $platform = $_GET['platform'];
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
        echo wp_kses_post(wp_json_encode($reviews));

        wp_die();
    }

    public function getYelpReviews()
    {

        $reviews = get_transient('grbb_yelp_reviews');
        $data = json_decode(get_option('grbb_apis'), true);
        $yelpKey = $data['yelpKey'] ?? '';
        $yelpBusinessUrl = $data['yelpBusinessURL'] ?? '';

        if (!$yelpKey || !$yelpBusinessUrl) {
            // delete_transient( 'grbb_yelp_reviews' );
            return [];
        }

        if (!$reviews) {
            $segments = explode('/', trim(parse_url($yelpBusinessUrl, PHP_URL_PATH), '/'));

            $url = "https://api.yelp.com/v3/businesses/" . $segments[1] . "/reviews?limit=20&sort_by=yelp_sort";

            $header_args = array(
                'headers' => array(
                    'user-agent' => 'business-reviews-wp',
                    'Authorization' => "Bearer " . $yelpKey,
                ),
            );
            $yelp_reviews = wp_remote_get($url, $header_args);

            $yelp_reviews = json_decode(wp_remote_retrieve_body($yelp_reviews), true);
            $reviews = $yelp_reviews['reviews'] ?? [];
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
            return [];
        }

        if (!$reviews) {
            $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$placeId&fields=reviews&reviews_no_translations=true&key=$key";

            $response = wp_remote_get($url);
            $reviews = json_decode(wp_remote_retrieve_body($response), true);
            $reviews = $reviews['result']['reviews'] ?? [];
            set_transient('grbb_google_reviews', $reviews, 60 * 60 * 24);
        }

        return $reviews;

    }

    public function getFBReviews()
    {
        $reviews = get_transient('grbb_fb_reviews');

        $isPremium = get_option('grbb_is_premium', false);

        global $grbb_bs;
        if ($grbb_bs->can_use_premium_feature()) {
            $this->fb_limit = 500;
        }

        if ($isPremium != $grbb_bs->can_use_premium_feature()) {
            $reviews = [];
        }
        update_option('grbb_is_premium', $grbb_bs->can_use_premium_feature());

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
            if (str_contains($reviews[$key]['review_text'], '<')) {
                $reviews[$key]['review_text'] = str_replace('<', "", $reviews[$key]['review_text']);
            }
        }

        return $reviews;
    }

    public function grbb_remove_cache()
    {
        if (!wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'wp_rest')) {
            wp_die();
        }

        delete_transient('grbb_yelp_reviews');
        delete_transient('grbb_google_reviews');
        delete_transient('grbb_fb_reviews');

        echo wp_json_encode(['success' => true]);
        wp_die();

    }
}
new BusinessReviewAPI();
