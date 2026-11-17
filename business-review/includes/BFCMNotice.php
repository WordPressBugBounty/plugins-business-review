<?php
/**
 * BFCM 2026 Banner & Admin Notice for Business Reviews
 *
 * Handles the Black Friday / Cyber Monday 2026 promotion banner and notice.
 * Expiry: December 5, 2026.
 *
 * @package BusinessReview
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'GRBB_BFCM_Notice' ) ) {
    class GRBB_BFCM_Notice {

        private static $instance = null;

        /**
         * End date for BFCM 2026 promotion (December 5, 2026 23:59:59 UTC).
         */
        const EXPIRY_TIMESTAMP = 1796515199;

        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            add_action( 'all_admin_notices', [ $this, 'render_all_business_reviews_banner' ] );
            add_action( 'wp_ajax_grbb_dismiss_bfcm_notice', [ $this, 'ajax_dismiss' ] );
        }

        /**
         * Check if promotion is currently active and not dismissed.
         */
        public function is_active() {
            if ( time() > self::EXPIRY_TIMESTAMP ) {
                return false;
            }

            if ( get_user_meta( get_current_user_id(), 'grbb_bfcm_2026_dismissed', true ) ) {
                return false;
            }

            if ( function_exists( 'grbb_fs' ) && grbb_fs()->can_use_premium_code() ) {
                return false;
            }

            return true;
        }

        /**
         * AJAX handler to persist dismissal for the user.
         */
        public function ajax_dismiss() {
            check_ajax_referer( 'grbb_bfcm_dismiss_nonce', 'nonce' );

            if ( ! current_user_can( 'read' ) ) {
                wp_send_json_error( [ 'message' => 'Unauthorized' ] );
            }

            update_user_meta( get_current_user_id(), 'grbb_bfcm_2026_dismissed', 1 );
            wp_send_json_success();
        }

        /**
         * Render the BFCM banner on the All Business Reviews list page (edit.php?post_type=grbb).
         */
        public function render_all_business_reviews_banner() {
            global $pagenow;

            if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'grbb' !== $_GET['post_type'] || isset( $_GET['page'] ) ) {
                return;
            }

            $screen = get_current_screen();
            if ( $screen && 'edit-grbb' !== $screen->id ) {
                return;
            }

            if ( ! $this->is_active() ) {
                return;
            }

            $nonce       = wp_create_nonce( 'grbb_bfcm_dismiss_nonce' );
            $pricing_url = 'https://bplugins.com/products/business-reviews/pricing/?utm_source=all_business_reviews&utm_medium=bfcm_banner&utm_campaign=bfcm2026';
            ?>
            <div id="grbbBfcmBannerWrap" style="display:none;">
                <style>
                    /* ===== BFCM Banner: Full-width with 20px right gap ===== */
                    #grbbBfcmBannerWrap {
                        display: none;
                        width: 100%;
                        box-sizing: border-box;
                        padding-right: 20px;
                        margin: 0;
                        clear: both;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmBanner {
                        display: flex;
                        align-items: stretch;
                        background: #ffffff;
                        border: 1px solid #d1e1fb;
                        border-left: none;
                        border-radius: 0;
                        box-shadow: 0 2px 12px rgba(20, 110, 245, 0.08);
                        margin: 0;
                        width: 100%;
                        overflow: hidden;
                        position: relative;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        box-sizing: border-box;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmBanner * {
                        box-sizing: border-box;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmRibbon {
                        background: linear-gradient(135deg, #EEF4FF 0%, #E3EDFE 50%, #D8E6FD 100%);
                        border-right: 1px solid #d1e1fb;
                        padding: 12px 18px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-width: 140px;
                        flex-shrink: 0;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmRibbonIconWrap {
                        width: 44px;
                        height: 44px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmRibbonIcon,
                    #grbbBfcmBannerWrap .grbbBfcmRibbonIconWrap svg {
                        width: 100%;
                        height: 100%;
                        display: block;
                        object-fit: contain;
                        filter: drop-shadow(0 4px 10px rgba(20, 110, 245, 0.25));
                    }
                    #grbbBfcmBannerWrap .grbbBfcmRibbonTitle {
                        color: #001737;
                        font-size: 10px;
                        font-weight: 800;
                        letter-spacing: 0.8px;
                        margin-top: 6px;
                        text-transform: uppercase;
                        text-align: center;
                        line-height: 1.2;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmContent {
                        flex: 1;
                        padding: 12px 20px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        gap: 6px;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmHeaderRow {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        flex-wrap: wrap;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmTitle {
                        margin: 0;
                        font-size: 15px;
                        font-weight: 700;
                        color: #070127;
                        line-height: 1.3;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmDesc {
                        margin: 3px 0 8px 0;
                        font-size: 12px;
                        line-height: 1.5;
                        color: #4b5563;
                        max-width: 950px;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmFeaturesRow {
                        display: flex;
                        align-items: center;
                        flex-wrap: wrap;
                        gap: 16px;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmFeatureItem {
                        display: inline-flex;
                        align-items: center;
                        gap: 5px;
                        font-size: 12px;
                        color: #4b5563;
                        font-weight: 500;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmCheckmark {
                        color: #070127;
                        font-weight: 700;
                        font-size: 12px;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmAction {
                        display: flex;
                        align-items: center;
                        padding-right: 22px;
                        padding-left: 10px;
                        position: relative;
                        flex-shrink: 0;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmCloseBtn {
                        position: absolute;
                        top: 8px;
                        right: 10px;
                        background: transparent;
                        border: none;
                        cursor: pointer;
                        color: #0b4ec2;
                        font-size: 18px;
                        line-height: 1;
                        padding: 2px 6px;
                        border-radius: 4px;
                        transition: color 0.15s ease, background 0.15s ease;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmCloseBtn:hover {
                        color: #00378b;
                        background: rgba(11, 78, 194, 0.1);
                    }
                    #grbbBfcmBannerWrap .grbbBfcmCtaBtn {
                        margin-right: 20px;
                        background: #e3edfe;
                        color: #0b4ec2 !important;
                        text-decoration: none !important;
                        font-size: 13px;
                        font-weight: 600;
                        padding: 9px 18px;
                        border-radius: 6px;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        transition: all 0.2s ease;
                        box-shadow: none !important;
                        border: 1px solid rgba(11, 78, 194, 0.25);
                        white-space: nowrap;
                    }
                    #grbbBfcmBannerWrap .grbbBfcmCtaBtn:hover {
                        background: #d2e3fc;
                        color: #00378b !important;
                        transform: translateY(-1px);
                    }
                    @media (max-width: 900px) {
                        #grbbBfcmBannerWrap { padding-right: 12px; }
                        #grbbBfcmBannerWrap .grbbBfcmBanner { flex-direction: column; border-left: 1px solid #d1e1fb; }
                        #grbbBfcmBannerWrap .grbbBfcmRibbon { width: 100%; flex-direction: row; gap: 12px; padding: 8px 16px; border-right: none; border-bottom: 1px solid #d1e1fb; }
                        #grbbBfcmBannerWrap .grbbBfcmContent { padding: 12px 16px; }
                        #grbbBfcmBannerWrap .grbbBfcmAction { padding: 0 16px 12px; justify-content: flex-start; }
                        #grbbBfcmBannerWrap .grbbBfcmCloseBtn { top: 8px; right: 8px; }
                    }
                </style>
                <div class="grbbBfcmBanner">
                    <div class="grbbBfcmRibbon">
                        <div class="grbbBfcmRibbonIconWrap">
                            <?php echo $this->get_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <span class="grbbBfcmRibbonTitle">BUSINESS REVIEWS</span>
                    </div>

                    <div class="grbbBfcmContent">
                        <div class="grbbBfcmHeaderRow">
                            <h3 class="grbbBfcmTitle"><?php esc_html_e( "Business Reviews' Biggest Black Friday Sale is Live – Up to 45% OFF!", 'business-review' ); ?></h3>
                        </div>

                        <p class="grbbBfcmDesc"><?php esc_html_e( 'The biggest deal of the year just arrived early! This is your once-a-year opportunity to upgrade to Business Reviews Pro at an exclusive price. Unlock multi-platform reviews from Google, Facebook, Yelp, and Trustpilot with schema markup to build instant trust and boost conversions!', 'business-review' ); ?></p>

                        <div class="grbbBfcmFeaturesRow">
                            <span class="grbbBfcmFeatureItem"><span class="grbbBfcmCheckmark">✓</span> <?php esc_html_e( 'Google & Facebook Reviews', 'business-review' ); ?></span>
                            <span class="grbbBfcmFeatureItem"><span class="grbbBfcmCheckmark">✓</span> <?php esc_html_e( 'Yelp & Trustpilot Integration', 'business-review' ); ?></span>
                            <span class="grbbBfcmFeatureItem"><span class="grbbBfcmCheckmark">✓</span> <?php esc_html_e( 'SEO Review Schema Markup', 'business-review' ); ?></span>
                            <span class="grbbBfcmFeatureItem"><span class="grbbBfcmCheckmark">✓</span> <?php esc_html_e( 'Badge & Grid Layouts', 'business-review' ); ?></span>
                        </div>
                    </div>

                    <div class="grbbBfcmAction">
                        <button type="button" class="grbbBfcmCloseBtn" id="grbbBfcmCloseBtn" aria-label="Dismiss banner" title="Dismiss">&times;</button>
                        <a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank" rel="noopener noreferrer" class="grbbBfcmCtaBtn">
                            <?php esc_html_e( 'Unlock PRO Features →', 'business-review' ); ?>
                        </a>
                    </div>
                </div>
            </div>
            <script>
            (function($) {
                $(document).ready(function() {
                    if (localStorage.getItem('grbb_bfcm_2026_dismissed')) {
                        return;
                    }

                    $('#grbbBfcmBannerWrap').show();

                    $('#grbbBfcmCloseBtn').on('click', function(e) {
                        e.preventDefault();
                        $('#grbbBfcmBannerWrap').slideUp(200, function() {
                            $(this).remove();
                        });

                        localStorage.setItem('grbb_bfcm_2026_dismissed', '1');

                        $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                            action: 'grbb_dismiss_bfcm_notice',
                            nonce: '<?php echo esc_js( $nonce ); ?>'
                        });
                    });
                });
            })(jQuery);
            </script>
            <?php
        }

        /**
         * Return inlined official SVG logo of Business Reviews.
         */
        private function get_logo_svg() {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="44" height="44" role="img" aria-label="Business Reviews">
  <defs>
    <linearGradient id="brv-p-bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#146ef5"/><stop offset="1" stop-color="#6155f5"/></linearGradient>
    <linearGradient id="brv-p-av" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3f86ff"/><stop offset="1" stop-color="#6155f5"/></linearGradient>
    <linearGradient id="brv-p-gold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd45a"/><stop offset="1" stop-color="#ffb400"/></linearGradient>
    <radialGradient id="brv-p-glow" cx="0.5" cy="0.45" r="0.6"><stop offset="0" stop-color="#ffffff" stop-opacity="0.35"/><stop offset="1" stop-color="#ffffff" stop-opacity="0"/></radialGradient>
    <clipPath id="brv-p-clip"><rect width="256" height="256" rx="56"/></clipPath>
    <path id="brv-p-star" d="M0.0,-12.0 L2.82,-3.88 L11.41,-3.71 L4.57,1.48 L7.05,9.71 L0.0,4.8 L-7.05,9.71 L-4.57,1.48 L-11.41,-3.71 L-2.82,-3.88 Z"/>
  </defs>
  <g clip-path="url(#brv-p-clip)">
    <rect width="256" height="256" fill="url(#brv-p-bg)"/>
    <circle cx="70" cy="60" r="90" fill="url(#brv-p-glow)"/>
    <g id="brv-p-card">
      <rect x="60" y="72" width="136" height="132" rx="20" fill="#ffffff"/>
      <circle cx="86" cy="100" r="15" fill="url(#brv-p-av)"/>
      <circle cx="86" cy="95" r="5.4" fill="#ffffff"/>
      <path d="M76 108a10 10 0 0 1 20 0z" fill="#ffffff"/>
      <rect x="108" y="90" width="60" height="9" rx="4.5" fill="#c9d0ec"/>
      <rect x="108" y="104" width="40" height="7" rx="3.5" fill="#e2e6f5"/>
      <g fill="url(#brv-p-gold)">
        <g transform="translate(83,140) scale(0.8)"><use href="#brv-p-star"/></g>
        <g transform="translate(105,140) scale(0.8)"><use href="#brv-p-star"/></g>
        <g transform="translate(127,140) scale(0.8)"><use href="#brv-p-star"/></g>
        <g transform="translate(149,140) scale(0.8)"><use href="#brv-p-star"/></g>
        <g transform="translate(171,140) scale(0.8)"><use href="#brv-p-star"/></g>
      </g>
      <rect x="76" y="164" width="104" height="8" rx="4" fill="#e2e6f5"/>
      <rect x="76" y="178" width="104" height="8" rx="4" fill="#e2e6f5"/>
      <rect x="76" y="192" width="66" height="8" rx="4" fill="#eef0f8"/>
    </g>
  </g>
</svg>';
        }
    }

    GRBB_BFCM_Notice::get_instance();
}

