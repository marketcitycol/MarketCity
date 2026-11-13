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
  if (isset($v["enabled"]) && $v["enabled"] === "yes" && preg_match("/(gateway|payment|addi|sistecredito|bold|wompi|pse|nequi|daviplata|mercadopago|payu|epayco)/i", $name . " " . ($v["title"] ?? ""))) { $v["enabled"] = "no"; $touched = true; }
  foreach ($v as $k => $val) {
    if (is_string($val) && $val !== "" && preg_match("/(key|secret|token|password|client_id|merchant|signature|webhook)/i", $k)) { $v[$k] = "REDACTED_BY_CLONE"; $touched = true; }
  }
  if ($touched) { update_option($name, $v); $changed[] = $name; }
}
// Never indexable, staging mode.
update_option("blog_public", "0");
echo "Options neutralized: " . count($changed) . PHP_EOL . implode(PHP_EOL, $changed) . PHP_EOL;
' | tee -a data/reports/neutralize.txt
log "Scheduled events kept but WP-Cron is disabled (DISABLE_WP_CRON). Review with: wp cron event list"
