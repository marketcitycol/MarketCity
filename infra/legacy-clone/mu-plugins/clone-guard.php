<?php
/**
 * Plugin Name: MarketCity clone guard
 * Description: Keeps the discovery clone from reaching the outside world. Loaded as a must-use plugin; cannot be deactivated from wp-admin.
 */
if (!defined('ABSPATH')) { exit; }

// 1. Every outgoing e-mail goes to the local Mailpit catcher.
add_action('phpmailer_init', function ($mailer) {
    $mailer->isSMTP();
    $mailer->Host = 'mailpit';
    $mailer->Port = 1025;
    $mailer->SMTPAuth = false;
    $mailer->SMTPSecure = '';
    $mailer->SMTPAutoTLS = false;
}, PHP_INT_MAX);
// A local site URL yields an invalid default sender (wordpress@localhost); keep the real one when valid.
add_filter('wp_mail_from', function ($from) {
    return is_email($from) ? $from : 'clon@marketcity.invalid';
}, PHP_INT_MAX);

// 2. Block every outgoing HTTP request (defense in depth on top of the internal network).
add_filter('pre_http_request', function ($pre, $args, $url) {
    $host = wp_parse_url($url, PHP_URL_HOST);
    if (in_array($host, ['localhost', '127.0.0.1', 'wordpress', 'mailpit'], true)) {
        return $pre;
    }
    error_log('[clone-guard] blocked outgoing request to ' . $host);
    return new WP_Error('clone_guard_blocked', 'Outgoing requests are disabled in the MarketCity clone.');
}, PHP_INT_MAX, 3);

// 3. Never indexable.
add_filter('pre_option_blog_public', fn () => '0');
add_filter('wp_robots', fn ($robots) => array_merge($robots, ['noindex' => true, 'nofollow' => true]));

// 4. Visible banner so nobody confuses the clone with production.
$banner = function () {
    echo '<div style="position:fixed;bottom:0;left:0;right:0;z-index:999999;background:#b00020;color:#fff;'
       . 'font:600 13px/1.4 sans-serif;padding:6px 12px;text-align:center">'
       . 'CLON DE DESCUBRIMIENTO — no productivo. Pagos, correos y conexiones externas desactivados.</div>';
};
add_action('wp_footer', $banner);
add_action('admin_footer', $banner);
