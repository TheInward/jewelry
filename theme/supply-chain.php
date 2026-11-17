<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Product Supply Chain',
        'Product Supply Chain',
        'manage_woocommerce',
        'seleneith-supply-chain',
        'seleneith_render_supply_chain'
    );
}, 90);

add_action('admin_post_seleneith_save_purchase_link', function() {
    if (!current_user_can('manage_woocommerce')) wp_die('You are not allowed to edit product supply links.', '', ['response'=>403]);
    check_admin_referer('seleneith_save_purchase_link');

    $product_id = isset($_POST['product_id']) && is_scalar($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $raw_url = isset($_POST['purchase_url']) && is_string($_POST['purchase_url']) ? trim(wp_unslash($_POST['purchase_url'])) : '';
    $product = $product_id ? wc_get_product($product_id) : false;
    if (!$product || $product->is_type('variation') || strlen($raw_url) > 2048) {
        wp_die('Invalid product or purchase link.', '', ['response'=>400]);
    }

    if ($raw_url === '') {
        delete_post_meta($product_id, '_seleneith_purchase_url');
    } else {
        $url = esc_url_raw($raw_url, ['http', 'https']);
        $parts = wp_parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed_hosts = ['1688.com', 'taobao.com', 'tmall.com', 'pinduoduo.com', 'yangkeduo.com'];
        $allowed = ($parts['scheme'] ?? '') === 'https' && $host !== '';
        $allowed = $allowed && (bool) array_filter($allowed_hosts, function($domain) use ($host) {
            return $host === $domain || str_ends_with($host, '.'.$domain);
        });
        if (!$url || !$allowed) wp_die('Use a secure purchase link from 1688, Taobao/Tmall, or Pinduoduo.', '', ['response'=>400]);
        update_post_meta($product_id, '_seleneith_purchase_url', $url);
    }

    $redirect = add_query_arg([
        'page'=>'seleneith-supply-chain',
        'updated'=>1,
        'paged'=>max(1, absint($_POST['paged'] ?? 1)),
        's'=>isset($_POST['s']) && is_string($_POST['s']) ? sanitize_text_field(wp_unslash($_POST['s'])) : '',
    ], admin_url('admin.php'));
    wp_safe_redirect($redirect);
    exit;
});

function seleneith_render_supply_chain() {
    if (!current_user_can('manage_woocommerce')) wp_die('You are not allowed to view product supply links.', '', ['response'=>403]);

    $page = max(1, absint($_GET['paged'] ?? 1));
    $search = isset($_GET['s']) && is_string($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $args = [
        'post_type'=>'product',
        'post_status'=>['publish','private','draft','pending','future'],
        'posts_per_page'=>50,
        'paged'=>$page,
        'orderby'=>'ID',
        'order'=>'ASC',
    ];
    if ($search !== '') {
        if (ctype_digit($search)) $args['p'] = (int) $search;
        else $args['s'] = $search;
    }
    $result = new WP_Query($args);
    $products = array_filter(array_map('wc_get_product', $result->posts));
    $total_pages = (int) $result->max_num_pages;
    ?>
    <div class="wrap">
      <h1>产品供应链</h1>
      <p>商品编号使用 WooCommerce 商品 ID。每个商品可登记一个国内平台采购链接；变量商品的链接可指向包含全部规格的供应商页面。</p>
      <?php if (isset($_GET['updated'])): ?><div class="notice notice-success is-dismissible"><p>采购链接已保存。</p></div><?php endif; ?>
      <form method="get" style="margin:16px 0">
        <input type="hidden" name="page" value="seleneith-supply-chain">
        <label class="screen-reader-text" for="seleneith-supply-search">搜索商品</label>
        <input id="seleneith-supply-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="搜索商品名称或编号">
        <button class="button">搜索商品</button>
      </form>
      <table class="widefat striped">
        <thead><tr><th style="width:90px">商品编号</th><th>商品</th><th style="width:130px">WooCommerce SKU</th><th>采购链接（1688 / 淘宝 / 拼多多）</th><th style="width:100px">操作</th></tr></thead>
        <tbody>
        <?php if (!$products): ?><tr><td colspan="5">没有找到商品。</td></tr><?php endif; ?>
        <?php foreach ($products as $product):
            $id = $product->get_id();
            $code = (string) $id;
            $field_id = 'purchase-url-'.$id;
            ?>
            <tr>
              <td><code><?php echo esc_html($code); ?></code></td>
              <td><a href="<?php echo esc_url(get_edit_post_link($id)); ?>"><?php echo esc_html($product->get_name()); ?></a><br><small><?php echo esc_html(get_post_status_object($product->get_status())->label ?? $product->get_status()); ?></small></td>
              <td><?php echo esc_html($product->get_sku() ?: '—'); ?></td>
              <td>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:8px;align-items:center">
                  <input type="hidden" name="action" value="seleneith_save_purchase_link">
                  <input type="hidden" name="product_id" value="<?php echo (int) $id; ?>">
                  <input type="hidden" name="paged" value="<?php echo (int) $page; ?>">
                  <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>">
                  <?php wp_nonce_field('seleneith_save_purchase_link'); ?>
                  <label class="screen-reader-text" for="<?php echo esc_attr($field_id); ?>">商品 <?php echo esc_html($code); ?> 采购链接</label>
                  <input id="<?php echo esc_attr($field_id); ?>" type="url" name="purchase_url" value="<?php echo esc_attr($product->get_meta('_seleneith_purchase_url')); ?>" placeholder="https://..." maxlength="2048" style="width:100%;max-width:680px">
                  <button class="button button-primary">保存</button>
                </form>
              </td>
              <td><?php $saved = $product->get_meta('_seleneith_purchase_url'); if ($saved): ?><a href="<?php echo esc_url($saved); ?>" target="_blank" rel="noopener noreferrer">打开采购页 ↗</a><?php else: ?>待补充<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($total_pages > 1): ?>
        <div class="tablenav"><div class="tablenav-pages">
          <?php echo wp_kses_post(paginate_links([
              'base'=>add_query_arg(['page'=>'seleneith-supply-chain','paged'=>'%#%','s'=>$search], admin_url('admin.php')),
              'format'=>'', 'current'=>$page, 'total'=>$total_pages, 'prev_text'=>'‹', 'next_text'=>'›',
          ])); ?>
        </div></div>
      <?php endif; ?>
    </div>
    <?php
}
