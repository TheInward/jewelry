<?php defined('ABSPATH') || exit;
// home_url already contains the installation path; use the current relative URL
// for preferences to work on both a domain root and a WordPress subdirectory.
$request_url = remove_query_arg('study_language', wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
$market = study_market(); $markets = study_markets();
$market_label = function($code) use ($markets) {
    $row = $markets[$code];
    return $row['name'].' ('.$row['currency'].' '.get_woocommerce_currency_symbol($row['currency']).')';
};
?>
<div class="market-controls notranslate">
<details class="market-dropdown"><summary><?php echo esc_html($market_label($market)); ?> <span aria-hidden="true">▾</span></summary><div class="market-menu country-menu"><input type="search" class="market-search" aria-label="<?php echo esc_attr(study_t('Search')); ?>" placeholder="<?php echo esc_attr(study_t('Search')); ?>">
<?php foreach ($markets as $code=>$row): ?><a data-country href="<?php echo esc_url(add_query_arg('study_market',$code,$request_url)); ?>" <?php if ($code===$market) echo 'aria-current="true"'; ?>><?php echo esc_html($market_label($code)); ?></a><?php endforeach; ?>
</div></details></div>
