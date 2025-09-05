<?php
/**
 * Class WooCategoryOrdering
 *
 * @Author  : Aleksandr BespredeL Kireev
 * @Author  URI: https://bespredel.name
 * @License : MIT
 * @License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

class WooCategoryOrdering
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_woo_category_ordering_save', [$this, 'save_category_ordering']);
        add_action('pre_get_posts', [$this, 'apply_sorting']);
        add_action('plugins_loaded', function () {
            load_plugin_textdomain(
                'woo-category-ordering',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages/'
            );
        });

        // Frontend sorting
        add_filter('woocommerce_get_catalog_ordering_args', [$this, 'frontend_sorting']);
    }

    public function add_admin_page()
    {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Product Sorting', 'woo-category-ordering'),
            __('Product Sorting', 'woo-category-ordering'),
            'manage_woocommerce',
            'woo-category-ordering',
            [$this, 'render_admin_page']
        );
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'product_page_woo-category-ordering') {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script(
            'woo-category-ordering-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            '1.1',
            true
        );

        wp_enqueue_style(
            'woo-category-ordering-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            [],
            '1.1'
        );

        wp_localize_script('woo-category-ordering-admin', 'WooCategoryOrdering', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('woo_category_ordering_nonce'),
            'saving_text' => __('Saving order...', 'woo-category-ordering'),
            'saved_text'  => __('Order saved!', 'woo-category-ordering'),
        ]);
    }

    public function frontend_sorting($args)
    {
        if (is_product_category()) {
            $term = get_queried_object();
            if ($term && isset($term->term_id)) {
                $meta_key = 'wco_cat_order_' . $term->term_id;

                $products = get_posts([
                    'post_type'   => 'product',
                    'numberposts' => -1,
                    'tax_query'   => [
                        [
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $term->term_id,
                        ],
                    ],
                    'orderby'     => 'menu_order title',
                    'order'       => 'ASC',
                ]);

                $max_order = 0;
                foreach ($products as $p) {
                    $val = get_post_meta($p->ID, $meta_key, true);
                    if ($val !== '') {
                        $max_order = max($max_order, (int)$val);
                    }
                }

                foreach ($products as $p) {
                    if (get_post_meta($p->ID, $meta_key, true) === '') {
                        $max_order++;
                        update_post_meta($p->ID, $meta_key, $max_order);
                    }
                }

                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = $meta_key;
                $args['order'] = 'ASC';
            }
        }

        return $args;
    }

    public function render_admin_page()
    {
        $selected_term = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;

        echo '<div class="wrap"><h1>' . __('Product Sorting by Category', 'woo-category-ordering') . '</h1>';

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        echo '<form method="get" id="woo-cat-ordering-form">';
        echo '<input type="hidden" name="post_type" value="product">';
        echo '<input type="hidden" name="page" value="woo-category-ordering">';
        echo '<select name="term_id" onchange="this.form.submit()">';
        echo '<option value="">' . __('-- Select a Category --', 'woo-category-ordering') . '</option>';
        $this->list_terms_with_indent($terms, 0, 0, $selected_term);
        echo '</select>';
        echo '</form>';

        if ($selected_term) {
            $meta_key = 'wco_cat_order_' . $selected_term;
            $products_all = get_posts([
                'post_type'   => 'product',
                'numberposts' => -1,
                'tax_query'   => [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $selected_term,
                    ],
                ],
                'orderby'     => 'menu_order title',
                'order'       => 'ASC',
            ]);

            $max_order = 0;
            foreach ($products_all as $p) {
                $val = get_post_meta($p->ID, $meta_key, true);
                if ($val !== '') {
                    $max_order = max($max_order, (int)$val);
                }
            }

            foreach ($products_all as $p) {
                if (get_post_meta($p->ID, $meta_key, true) === '') {
                    $max_order++;
                    update_post_meta($p->ID, $meta_key, $max_order);
                }
            }

            $products = get_posts([
                'post_type'   => 'product',
                'numberposts' => -1,
                'tax_query'   => [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $selected_term,
                    ],
                ],
                'meta_key'    => $meta_key,
                'orderby'     => 'meta_value_num',
                'order'       => 'ASC',
            ]);

            if ($products) {
                echo '<p><em>' . __('Order is saved automatically when changed.', 'woo-category-ordering') . '</em></p>';
                echo '<ul id="woo-cat-ordering-list" data-term="' . $selected_term . '">';
                foreach ($products as $product) {
                    echo '<li data-id="' . $product->ID . '">' . esc_html($product->post_title) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . __('There are no products in this category.', 'woo-category-ordering') . '</p>';
            }
        }

        echo '</div>';
    }

    private function list_terms_with_indent($terms, $parent = 0, $depth = 0, $selected_term = 0)
    {
        foreach ($terms as $term) {
            if ((int)$term->parent !== (int)$parent) {
                continue;
            }

            $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);
            printf(
                '<option value="%d" %s>%s%s</option>',
                $term->term_id,
                selected($selected_term, $term->term_id, false),
                $indent,
                esc_html($term->name)
            );
            $this->list_terms_with_indent($terms, $term->term_id, $depth + 1, $selected_term);
        }
    }

    public function save_category_ordering()
    {
        check_ajax_referer('woo_category_ordering_nonce', 'nonce');

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $term_id = (int)$_POST['term_id'];

        if (!$term_id || empty($order)) {
            wp_send_json_error();
        }

        foreach ($order as $index => $product_id) {
            update_post_meta($product_id, 'wco_cat_order_' . $term_id, $index);
        }

        wp_send_json_success();
    }

    public function apply_sorting($query)
    {
        if (!is_admin() && $query->is_main_query() && is_product_category()) {
            $term = get_queried_object();
            if ($term && isset($term->term_id)) {
                $query->set('meta_key', 'wco_cat_order_' . $term->term_id);
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'ASC');
            }
        }
    }
}