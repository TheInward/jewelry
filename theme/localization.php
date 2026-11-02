<?php
defined('ABSPATH') || exit;

function study_languages() {
    return ['en'=>['English','en_US'],'pl'=>['Polski','pl_PL'],'es'=>['Español','es_ES'],'fr'=>['Français','fr_FR'],'de'=>['Deutsch','de_DE'],'nl'=>['Nederlands','nl_NL'],'it'=>['Italiano','it_IT'],'sv'=>['Svenska','sv_SE'],'ja'=>['日本語','ja'],'ko'=>['한국어','ko_KR'],'pt-pt'=>['Português (Portugal)','pt_PT'],'el'=>['Ελληνικά','el'],'cs'=>['Čeština','cs_CZ'],'hu'=>['Magyar','hu_HU']];
}
function study_language() {
    $value = sanitize_key($_GET['study_language'] ?? $_COOKIE['study_language'] ?? 'en');
    return isset(study_languages()[$value]) ? $value : 'en';
}
function study_dictionary() {
    static $all, $extra;
    if ($all === null) $all = json_decode(file_get_contents(__DIR__.'/translations.json'), true) ?: [];
    if ($extra === null) $extra = json_decode(file_get_contents(__DIR__.'/translations-extra.json'), true) ?: [];
    $keys = explode('|', $all['keys'] ?? '');
    $values = isset($all[study_language()]) ? explode('|', $all[study_language()]) : $keys;
    $dictionary = count($keys) === count($values) ? array_combine($keys, $values) : [];
    $consent = ['en'=>'I agree to the','pl'=>'Akceptuję','es'=>'Acepto la','fr'=>'J’accepte la','de'=>'Ich akzeptiere die','nl'=>'Ik ga akkoord met het','it'=>'Accetto la','sv'=>'Jag godkänner','ja'=>'同意します：','ko'=>'다음에 동의합니다:','pt-pt'=>'Aceito a','el'=>'Συμφωνώ με την','cs'=>'Souhlasím s','hu'=>'Elfogadom:'];
    return array_merge($extra[study_language()] ?? [], $dictionary, ['I agree to the'=>$consent[study_language()]]);
}
function study_t($text) { return study_dictionary()[$text] ?? __($text, 'woocommerce'); }
add_filter('locale', function($locale) {
    return is_admin() && !wp_doing_ajax() ? $locale : study_languages()[study_language()][1];
});
add_filter('language_attributes', function($output) {
    return 'lang="'.esc_attr(str_replace('_','-',study_languages()[study_language()][1])).'"';
});
add_filter('woocommerce_product_get_name', function($name, $product) {
    if (is_admin() && !wp_doing_ajax()) return $name;
    $translations = $product->get_meta('_study_translations');
    return $translations[study_language()] ?? $name;
}, 20, 2);
add_filter('the_title', function($title, $id) {
    if (is_admin()) return $title;
    if (get_post_type($id) === 'product') {
        $translations = get_post_meta($id, '_study_translations', true);
        return $translations[study_language()] ?? $title;
    }
    return $title;
}, 20, 2);

// Only currencies explicitly configured in WCML can be selected. Amounts are
// converted by WCML on the server, including shipping, coupons and order totals.
function study_currency_options() {
    return array_merge(['USD'=>['rate'=>1,'num_decimals'=>2]], get_option('_wcml_settings', [])['currency_options'] ?? []);
}
function study_markets() {
    if (!function_exists('WC') || !WC()->countries) return ['US'=>['name'=>'United States','currency'=>'USD']];
    $out = []; $currencies = study_currency_options(); $map = [];
    $groups = [
        'USD'=>'US EC SV PA TL PR GU VI VG BQ TC', 'EUR'=>'AT BE BG CY DE EE ES FI FR GR HR IE IT LT LU LV MT NL PT SI SK AX AD MC SM VA BL GF GP MQ MF PM RE YT',
        'GBP'=>'GB GG IM JE', 'AUD'=>'AU CX CC KI NR NF TV', 'CAD'=>'CA', 'HKD'=>'HK', 'CNY'=>'CN', 'JPY'=>'JP', 'KRW'=>'KR',
        'AED'=>'AE','ALL'=>'AL','AWG'=>'AW','BAM'=>'BA','BSD'=>'BS','BZD'=>'BZ','CHF'=>'CH LI','CZK'=>'CZ','DKK'=>'DK FO GL','DOP'=>'DO','FJD'=>'FJ','HUF'=>'HU','IDR'=>'ID','ILS'=>'IL PS','ISK'=>'IS','JMD'=>'JM','KES'=>'KE','KYD'=>'KY','LBP'=>'LB','MAD'=>'MA EH','MDL'=>'MD','MKD'=>'MK','MOP'=>'MO','MUR'=>'MU','MYR'=>'MY','NGN'=>'NG','NZD'=>'NZ CK NU PN TK','PEN'=>'PE','PHP'=>'PH','PKR'=>'PK','PLN'=>'PL','QAR'=>'QA','RON'=>'RO','RSD'=>'RS','RWF'=>'RW','SAR'=>'SA','SEK'=>'SE','SGD'=>'SG','SHP'=>'SH','THB'=>'TH','TTD'=>'TT','TWD'=>'TW','TZS'=>'TZ','VND'=>'VN','XCD'=>'AG AI DM GD KN LC MS VC','XPF'=>'NC PF WF'
    ];
    foreach ($groups as $currency=>$countries) foreach (explode(' ', $countries) as $country) $map[$country] = $currency;
    foreach (WC()->countries->get_countries() as $country=>$name) {
        $currency = $map[$country] ?? '';
        if (isset($currencies[$currency])) $out[$country] = ['name'=>$name, 'currency'=>$currency];
    }
    return $out;
}
function study_market() {
    $country = strtoupper(sanitize_key($_GET['study_market'] ?? $_COOKIE['study_market'] ?? 'US'));
    return isset(study_markets()[$country]) ? $country : 'US';
}
add_filter('wcml_client_currency', function($currency) {
    if (is_admin() && !wp_doing_ajax()) return $currency;
    return study_markets()[study_market()]['currency'] ?? 'USD';
});
add_filter('wcml_multi_currency_ajax_actions', function($actions) { return array_merge($actions, ['study_cart','study_products']); });

// Language/currency pages must never share a public cached HTML response.
add_action('init', function() {
    if (!is_admin() && !defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
});
add_action('send_headers', function() { if (!is_admin()) nocache_headers(); });
add_action('template_redirect', function() {
    $changed = false;
    foreach (['study_language'=>study_language(), 'study_market'=>study_market()] as $key=>$value) {
        if (!isset($_GET[$key])) continue;
        wc_setcookie($key, $value, time()+YEAR_IN_SECONDS, is_ssl(), true);
        $_COOKIE[$key] = $value; $changed = true;
    }
    if (!$changed) return;
    if (WC()->session) {
        WC()->session->__unset('chosen_shipping_methods');
        foreach (array_keys(WC()->session->get_session_data()) as $key) {
            if (strpos($key, 'shipping_for_package_') === 0) WC()->session->__unset($key);
        }
    }
    wp_safe_redirect(remove_query_arg(['study_language','study_market'])); exit;
}, 1);

function study_refresh_exchange_rates() {
    if (!defined('WCML_MULTI_CURRENCIES_INDEPENDENT')) return false;
    $response = wp_remote_get('https://open.er-api.com/v6/latest/USD', ['timeout'=>25]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return false;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (($data['result'] ?? '') !== 'success' || ($data['base_code'] ?? '') !== 'USD' || ($data['rates']['USD'] ?? 0) != 1) return false;
    $settings = get_option('_wcml_settings', []);
    $supported = explode(' ', 'USD AED ALL AUD AWG BAM BSD BZD CAD CHF CNY CZK DKK DOP EUR FJD GBP HKD HUF IDR ILS ISK JMD JPY KES KRW KYD LBP MAD MDL MKD MOP MUR MYR NGN NZD PEN PHP PKR PLN QAR RON RSD RWF SAR SEK SGD SHP THB TTD TWD TZS VND XCD XPF');
    foreach ($supported as $code) {
        if (!isset($data['rates'][$code]) || !is_numeric($data['rates'][$code]) || $data['rates'][$code] <= 0) continue;
        $settings['currency_options'][$code] = array_merge($settings['currency_options'][$code] ?? [], [
            'rate'=>(float)$data['rates'][$code], 'updated'=>gmdate('Y-m-d H:i:s', $data['time_last_update_unix']),
            'position'=>'left', 'thousand_sep'=>',', 'decimal_sep'=>'.',
            'num_decimals'=>in_array($code,['JPY','KRW','RWF','VND','XPF'])?0:2,
            'rounding'=>'disabled', 'rounding_increment'=>1, 'auto_subtract'=>0,
            'languages'=>['en'=>1], 'location_mode'=>'all', 'countries'=>[]
        ]);
    }
    $settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;
    $settings['currency_mode'] = 'by_location';
    $settings['currencies_order'] = array_keys($settings['currency_options']);
    update_option('_wcml_settings', $settings);
    update_option('study_rates_updated', (int)$data['time_last_update_unix']);
    return true;
}
add_action('study_daily_rates', 'study_refresh_exchange_rates');
add_action('init', function() { if (!wp_next_scheduled('study_daily_rates')) wp_schedule_event(time()+DAY_IN_SECONDS, 'daily', 'study_daily_rates'); });

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('study-markets', get_template_directory_uri().'/markets.css', ['study'], filemtime(__DIR__.'/markets.css'));
    $currency = get_woocommerce_currency();
    $options = study_currency_options()[$currency] ?? [];
    wp_localize_script('study','studyLocale', [
        'language'=>study_language(), 'locale'=>str_replace('_','-',study_languages()[study_language()][1]),
        'currency'=>$currency, 'decimals'=>(int)($options['num_decimals']??2), 'strings'=>study_dictionary()
    ]);
}, 20);

// Select the eligible free method so the drawer and checkout never disagree.
add_filter('woocommerce_package_rates', function($rates) {
    $free = array_filter($rates, function($rate) { return $rate->method_id === 'free_shipping'; });
    return $free ?: $rates;
}, 100);
function study_checkout_currency_row() {
    echo '<tr class="study-order-currency"><th>'.esc_html__('Currency','woocommerce').'</th><td>'.esc_html(get_woocommerce_currency()).'</td></tr>';
}
add_action('woocommerce_review_order_before_order_total','study_checkout_currency_row');
add_action('woocommerce_cart_totals_before_order_total','study_checkout_currency_row');
add_filter('get_term', function($term, $taxonomy) {
    if ($taxonomy !== 'product_cat' || is_admin() || !($term instanceof WP_Term)) return $term;
    if (isset(study_collections()[$term->slug])) {
        $term = clone $term;
        $term->name = study_t(study_collections()[$term->slug]);
    }
    return $term;
},20,2);
