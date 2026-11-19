#!/usr/bin/env bash
# Turn off everything that could act on the outside world, and record what was changed.
# Network isolation already blocks egress; this is the second layer.
source "$(dirname "$0")/lib.sh"
log "Neutralizing clone"
wp eval '
$changed = [];
// Payment gateways: disable all and blank any credential-like field.
foreach ($GLOBALS["wpdb"]->get_col("SELECT option_name FROM {$GLOBALS["wpdb"]->options} WHERE option_name LIKE \"woocommerce\\_%\\_settings\"") as $name) {
  $v = get_option($name);
  if (!is_array($v)) continue;
  $touched = false;
  if (isset($v["enabled"]) && $v["enabled"] === "yes" && preg_match("/(gateway|payment|addi|sistecredito|bold|wompi|pse|nequi|daviplata|mercadopago|mercado|payu|epayco)/i", $name . " " . ($v["title"] ?? ""))) { $v["enabled"] = "no"; $touched = true; }
  foreach ($v as $k => $val) {
    if (is_string($val) && $val !== "" && preg_match("/(key|secret|token|password|client_id|merchant|signature|webhook)/i", $k)) { $v[$k] = "REDACTED_BY_CLONE"; $touched = true; }
  }
  if ($touched) { update_option($name, $v); $changed[] = $name; }
}
// Standalone options that hold credentials (seen in the real dump: Mercado Pago, Bold, Facebook,
// Tidio, Weglot, Firebase service account inside mcn_settings, Hostinger tokens, etc.).
$skip = "/(endpoint|generate_password|_valid_token|using_application_passwords|db_version|google_maps)/i";
foreach ($GLOBALS["wpdb"]->get_col("SELECT option_name FROM {$GLOBALS["wpdb"]->options} WHERE option_name NOT LIKE \"\\_transient%\" AND option_name NOT LIKE \"\\_site\\_transient%\" AND option_name NOT LIKE \"woocommerce\\_%\\_settings\" AND option_name REGEXP \"(secret|token|api_key|apikey|password|service_account|secretos|credential|jwt)\"") as $name) {
  if (preg_match($skip, $name)) continue;
  $v = get_option($name);
  if (is_string($v) && strlen($v) > 3) { update_option($name, "REDACTED_BY_CLONE"); $changed[] = $name; }
  elseif (is_array($v)) { update_option($name, ["redacted_by_clone" => true]); $changed[] = $name; }
}
// Settings arrays of in-house plugins that embed credentials.
foreach (["mcn_settings"] as $name) {
  $v = get_option($name);
  if (!is_array($v)) continue;
  foreach ($v as $k => $val) {
    if (is_string($val) && $val !== "" && preg_match("/(key|secret|token|password|service_account|server_key|credential)/i", $k)) { $v[$k] = "REDACTED_BY_CLONE"; }
  }
  update_option($name, $v); $changed[] = $name;
}
// Never indexable, staging mode.
update_option("blog_public", "0");
echo "Options neutralized: " . count($changed) . PHP_EOL . implode(PHP_EOL, $changed) . PHP_EOL;
' | tee -a data/reports/neutralize.txt
# Google Listings & Ads blocks wp-admin for ~9 s and then renders a blank page when Google is unreachable.
wp plugin deactivate google-listings-and-ads >/dev/null 2>&1 && log "Deactivated google-listings-and-ads (needs internet; blanks wp-admin in the clone)"
log "Scheduled events kept but WP-Cron is disabled (DISABLE_WP_CRON). Review with: wp cron event list"
