<?php
/*
Plugin Name: Bulk Product Editor
Description: A plugin to bulk edit WooCommerce products.
Version: 1.2
Author: Brmja.Tech
Author URI: https://brmja.tech
*/

// Check if WooCommerce is installed and activated
function bulk_product_editor_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        // WooCommerce is not installed or activated, display admin notice
        add_action('admin_notices', 'bulk_product_editor_woocommmerce_not_installed_notice');

        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
    }
}
add_action('admin_init', 'bulk_product_editor_check_woocommerce');

// Display admin notice if WooCommerce is not installed
function bulk_product_editor_woocommmerce_not_installed_notice()
{
?>
    <div class="error">
        <p><?php echo esc_html__('Bulk Product Editor requires WooCommerce to be installed and activated. Please install and activate WooCommerce to use this plugin.', 'bulk-product-editor'); ?></p>
    </div>
<?php
}

// Enqueue scripts and styles
function bulk_product_editor_enqueue_scripts()
{
    // Enqueue CSS
    wp_enqueue_style('bulk-product-editor-css', plugins_url('css/style.css', __FILE__));

    // Enqueue JavaScript
    wp_enqueue_script('bulk-product-editor-js', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'bulk_product_editor_enqueue_scripts');

// Add menu item
function bulk_product_editor_menu()
{
    add_menu_page('Bulk Product Editor', 'Bulk Product Editor', 'manage_options', 'bulk-product-editor', 'bulk_product_editor_page');
}
add_action('admin_menu', 'bulk_product_editor_menu');

// Bulk Product Editor page with pagination and product per page dropdown
function bulk_product_editor_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get weight unit from WooCommerce settings
    $weight_unit = get_option('woocommerce_weight_unit');

    // Get height unit from WooCommerce settings
    $height_unit = get_option('woocommerce_dimension_unit');

    // Pagination variables
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $products_per_page = isset($_GET['products_per_page']) ? intval($_GET['products_per_page']) : 10;

    // Sorting variables
    $sort_column = isset($_GET['sort_column']) ? sanitize_text_field($_GET['sort_column']) : 'title';
    $sort_order = isset($_GET['sort_order']) ? strtoupper(sanitize_text_field($_GET['sort_order'])) : 'ASC';

    // Process form submission
    if (isset($_POST['bulk_edit_products'])) {
        // Verify nonce
        if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bulk-edit-products')) {
            // Handle bulk edit here
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $products_per_page,
                'paged' => $current_page,
                'orderby' => 'title',
                'order' => 'ASC'
            );
            $products_query = new WP_Query($args);

            if ($products_query->have_posts()) :
                while ($products_query->have_posts()) : $products_query->the_post();
                    $product_id = get_the_ID();

                    // Update product data
                    $product_data = array(
                        'ID' => $product_id,
                        'post_title' => isset($_POST['new_title'][$product_id]) ? sanitize_text_field($_POST['new_title'][$product_id]) : get_the_title($product_id), // Title
                        'post_content' => isset($_POST['new_description'][$product_id]) ? wp_kses_post($_POST['new_description'][$product_id]) : get_the_content($product_id), // Description
                    );

                    // Regular Price
                    if (isset($_POST['new_regular_price'][$product_id])) {
                        $regular_price = wc_format_decimal($_POST['new_regular_price'][$product_id]);
                        update_post_meta($product_id, '_regular_price', $regular_price);
                    }

                    // Sale Price
                    if (isset($_POST['new_sale_price'][$product_id])) {
                        $sale_price = wc_format_decimal($_POST['new_sale_price'][$product_id]);
                        update_post_meta($product_id, '_sale_price', $sale_price);
                    } else {
                        // If no sale price is provided, remove the existing sale price
                        delete_post_meta($product_id, '_sale_price');
                    }

                    // SKU
                    if (isset($_POST['new_sku'][$product_id])) {
                        $sku = sanitize_text_field($_POST['new_sku'][$product_id]);
                        update_post_meta($product_id, '_sku', $sku);
                    }

                    // Weight
                    if (isset($_POST['new_weight'][$product_id])) {
                        $weight = wc_format_decimal($_POST['new_weight'][$product_id]);
                        update_post_meta($product_id, '_weight', $weight);
                    }

                    // Height
                    if (isset($_POST['new_height'][$product_id])) {
                        $height = wc_format_decimal($_POST['new_height'][$product_id]);
                        update_post_meta($product_id, '_height', $height);
                    }

                    // Update post
                    wp_update_post($product_data);
                endwhile;
            endif;

            // Redirect to avoid resubmission
            wp_redirect(admin_url('admin.php?page=bulk-product-editor&bulk_edit_success=1&paged=' . $current_page . '&products_per_page=' . $products_per_page));
            exit();
        } else {
            // Nonce verification failed, display error message
            wp_die('Security check failed. Please try again.');
        }
    }

    // Display bulk edit form
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Bulk Product Editor</h1>
        <hr class="wp-header-end">

        <?php
        // Check if bulk edit was successful
        if (isset($_GET['bulk_edit_success']) && $_GET['bulk_edit_success'] == '1') {
            echo '<div class="updated"><p>Bulk edit successful!</p></div>';
        }
        ?>

        <form method="get" action="">
            <input type="hidden" name="page" value="bulk-product-editor"> <!-- Add hidden input for page parameter -->
            <label for="products_per_page">Products per page:</label>
            <select name="products_per_page" id="products_per_page">
                <option value="10" <?php selected($products_per_page, 10); ?>>10</option>
                <option value="20" <?php selected($products_per_page, 20); ?>>20</option>
                <option value="50" <?php selected($products_per_page, 50); ?>>50</option>
                <option value="100" <?php selected($products_per_page, 100); ?>>100</option>
            </select>

            <input type="submit" class="button" value="Apply">
        </form>

        <form method="post" action="">
            <input type="hidden" name="page" value="bulk-product-editor"> <!-- Add hidden input for page parameter -->
            <?php wp_nonce_field('bulk-edit-products'); ?>
            <label for="sort_column">Sort by:</label>
            <select name="sort_column" id="sort_column">
                <option value="title" <?php selected($sort_column, 'title'); ?>>Title</option>
                <option value="description" <?php selected($sort_column, 'description'); ?>>Description</option>
                <option value="regular_price" <?php selected($sort_column, 'regular_price'); ?>>Regular Price</option>
                <option value="sale_price" <?php selected($sort_column, 'sale_price'); ?>>Sale Price</option>
                <option value="sku" <?php selected($sort_column, 'sku'); ?>>SKU</option>
                <option value="weight" <?php selected($sort_column, 'weight'); ?>>Weight</option>
                <option value="height" <?php selected($sort_column, 'height'); ?>>Height</option>
            </select>

            <label for="sort_order">Order:</label>
            <select name="sort_order" id="sort_order">
                <option value="ASC" <?php selected($sort_order, 'ASC'); ?>>ASC</option>
                <option value="DESC" <?php selected($sort_order, 'DESC'); ?>>DESC</option>
            </select>

            <input type="submit" class="button" value="Apply">
        </form>

        <form method="post" action="">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th class="sortable" data-column="title">Title</th>
                        <th class="sortable" data-column="description">Description</th>
                        <th class="sortable" data-column="regular_price">Regular Price</th>
                        <th class="sortable" data-column="sale_price">Sale Price</th>
                        <th class="sortable" data-column="sku">SKU</th>
                        <th class="sortable" data-column="weight">Weight (<?php echo esc_html($weight_unit); ?>)</th>
                        <th class="sortable" data-column="height">Height (<?php echo esc_html($height_unit); ?>)</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // Retrieve products with pagination and sorting
                    $args = array(
                        'post_type'      => 'product',
                        'posts_per_page' => $products_per_page,
                        'paged'          => $current_page,
                        'orderby'        => $sort_column,
                        'order'          => $sort_order,
                    );
                    $products_query = new WP_Query($args);

                    if ($products_query->have_posts()) :
                        while ($products_query->have_posts()) : $products_query->the_post();
                            $product_id = get_the_ID();
                    ?>
                            <tr>
                                <td><input type="text" name="new_title[<?php echo $product_id; ?>]" value="<?php echo get_the_title(); ?>"></td>
                                <td><textarea name="new_description[<?php echo $product_id; ?>]"><?php echo get_the_content(); ?></textarea></td>
                                <td><input type="text" name="new_regular_price[<?php echo $product_id; ?>]" value="<?php echo get_post_meta($product_id, '_regular_price', true); ?>"></td>
                                <td><input type="text" name="new_sale_price[<?php echo $product_id; ?>]" value="<?php echo get_post_meta($product_id, '_sale_price', true); ?>"></td>
                                <td><input type="text" name="new_sku[<?php echo $product_id; ?>]" value="<?php echo get_post_meta($product_id, '_sku', true); ?>"></td>
                                <td><input type="text" name="new_weight[<?php echo $product_id; ?>]" value="<?php echo get_post_meta($product_id, '_weight', true); ?>"></td>
                                <td><input type="text" name="new_height[<?php echo $product_id; ?>]" value="<?php echo get_post_meta($product_id, '_height', true); ?>"></td>
                            </tr>
                    <?php
                        endwhile;
                    endif;
                    wp_reset_postdata();
                    ?>
                </tbody>
            </table>

            <br />

            <?php
            // Display pagination links
            $pagination_args = array(
                'total'   => $products_query->max_num_pages,
                'current' => $current_page,
                'format'  => '?paged=%#%',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            );

            $pagination_links = paginate_links($pagination_args);

            if ($pagination_links) {
                // Append existing query parameters to pagination links without duplication
                $pagination_links = str_replace('href=\'', 'href=\'admin.php?' . http_build_query(array_merge($_GET, array('sort_column' => $sort_column, 'sort_order' => $sort_order))) . '&', $pagination_links);
                echo $pagination_links;
            }
            ?>

            <input type="submit" name="bulk_edit_products" value="Bulk Edit" class="button-primary" />
            <?php wp_nonce_field('bulk-edit-products'); ?>
        </form>
    </div>
    <?php
}
