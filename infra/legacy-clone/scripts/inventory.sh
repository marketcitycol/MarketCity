#!/usr/bin/env bash
# Phase 0 technical inventory (prompt §7.3). Aggregates only: no names, e-mails, phones, addresses or order details.
source "$(dirname "$0")/lib.sh"
out="data/reports/inventory-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$out"
log "Inventory -> $out"
wp core version --extra                                > "$out/core.txt"
wp eval 'echo "PHP ".PHP_VERSION.PHP_EOL; global $wpdb; echo "DB ".$wpdb->db_version().PHP_EOL;' >> "$out/core.txt"
wp plugin list --fields=name,status,version,update_version,auto_update --format=csv > "$out/plugins.csv"
wp theme list --fields=name,status,version,update_version --format=csv            > "$out/themes.csv"
wp plugin list --status=must-use --fields=name,version --format=csv                > "$out/mu-plugins.csv"
wp cron event list --fields=hook,recurrence,next_run_relative --format=csv         > "$out/cron.csv"
wp eval 'foreach (count_users()["avail_roles"] as $r=>$n) echo "$r,$n\n";'        > "$out/roles-count.csv"
wp eval '
global $wpdb;
$rows = $wpdb->get_results("SELECT table_name t, table_rows r, ROUND((data_length+index_length)/1024/1024,2) mb FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY mb DESC");
echo "table,rows_estimate,size_mb\n"; foreach ($rows as $x) echo "{$x->t},{$x->r},{$x->mb}\n";' > "$out/tables.csv"
wp eval '
global $wpdb;
$q = fn($sql) => $wpdb->get_results($sql, ARRAY_N);
echo "post_type,post_status,count\n";
foreach ($q("SELECT post_type, post_status, COUNT(*) FROM {$wpdb->posts} GROUP BY 1,2 ORDER BY 1,2") as $r) echo implode(",", $r)."\n";
' > "$out/posts-by-type-status.csv"
wp eval '
global $wpdb;
$t = $wpdb->prefix."wc_orders";
if ($wpdb->get_var("SHOW TABLES LIKE \"$t\"")) {
  echo "source,status,count,total_cop,first,last\n";
  foreach ($wpdb->get_results("SELECT \"hpos\", status, COUNT(*), ROUND(SUM(total_amount)), MIN(date_created_gmt), MAX(date_created_gmt) FROM $t WHERE type=\"shop_order\" GROUP BY status", ARRAY_N) as $r) echo implode(",", $r)."\n";
} else { echo "HPOS table not present\n"; }
echo "payment_method,count\n";
$pm = $wpdb->get_var("SHOW TABLES LIKE \"$t\"") ? "SELECT payment_method, COUNT(*) FROM $t WHERE type=\"shop_order\" GROUP BY 1" : "SELECT meta_value, COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key=\"_payment_method\" GROUP BY 1";
foreach ($wpdb->get_results($pm, ARRAY_N) as $r) echo implode(",", $r)."\n";
' > "$out/orders-aggregate.csv"
wp option get woocommerce_custom_orders_table_enabled > "$out/hpos.txt" 2>&1 || true
wp eval 'global $wpdb; echo "autoload_kb,".round($wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN (\"yes\",\"on\",\"auto-on\",\"auto\")")/1024).PHP_EOL;' > "$out/options-autoload.csv"
( cd data/site/wp-content && find plugins themes mu-plugins -maxdepth 1 -mindepth 1 -type d 2>/dev/null | sort ) > "$out/code-dirs.txt"
( cd data/site/wp-content && du -sh uploads 2>/dev/null ) > "$out/uploads-size.txt" || true
log "Inventory done: $(ls "$out" | wc -l) files"
