<?php 
/**
 * Show Tags Again @ Single Product Page - WooCommerce
 */
add_action( 'woocommerce_single_product_summary', 'bbloomer_show_tags_again_single_product', 40 );
function bbloomer_show_tags_again_single_product() { 
  global $product; ?>
  <div class="product_meta"><?php 
    echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?> 
  </div><?php
}

/**
 * Show Categories Again @ Single Product Page - WooCommerce
 */
add_action( 'woocommerce_single_product_summary', 'bbloomer_show_cats_again_single_product', 40 );
function bbloomer_show_cats_again_single_product() {
  global $product; ?>
  <div class="product_meta"><?php 
    echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?> 
  </div><?php
}

/**
 * Image to External URL - WooCommerce Single Product
 */
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'bbloomer_image_link_external_url', 100, 2 );
function bbloomer_image_link_external_url( $html, $post_thumbnail_id ) {
  global $product;
  if ( ! $product->is_type( 'external' ) ) return $html;
  $url = $product->add_to_cart_url();
  $pattern = "/(?<=href=(\"|'))[^\"']+(?=(\"|'))/";
  $html = preg_replace( $pattern, $url, $html );  
  return $html;
}

/**
 * Hide Product ID Based on Geolocated Country @ WooCommerce Shop
 */
add_filter( 'woocommerce_product_is_visible', 'bbloomer_hide_product_if_country', 999, 2 );
function bbloomer_hide_product_if_country( $visible, $product_id ){
  $location = WC_Geolocation::geolocate_ip();
  $country = $location['country'];
  if ( $country == "IT" && $product_id === 344 ) {
    $visible = false;
  }
  return $visible;
}
/**
 * Remove Zoom, Gallery @ Single Product Page
 */
add_action( 'wp', 'bbloomer_remove_zoom_lightbox_theme_support', 99 );
function bbloomer_remove_zoom_lightbox_theme_support() { 
  remove_theme_support( 'wc-product-gallery-zoom' );
  remove_theme_support( 'wc-product-gallery-lightbox' );
  remove_theme_support( 'wc-product-gallery-slider' );
}

/**
 * Hide SKU, Cats, Tags @ Single Product Page - WooCommerce
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

/**
 * Show SKU Again @ Single Product Page - WooCommerce
 */
add_action( 'woocommerce_single_product_summary', 'bbloomer_show_sku_again_single_product', 40 );
function bbloomer_show_sku_again_single_product() {
  global $product; ?>
  <div class="product_meta"><?php 
    if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>
      <span class="sku_wrapper"><?php 
        esc_html_e( 'SKU:', 'woocommerce' ); ?> 
        <span class="sku"><?php 
          echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?>
        </span>
      </span><?php 
    endif; ?>
  </div> <?php
}

/**
 * Grey-out Out of Stock Variations @ WooCommerce Single Product Page
 */
add_filter( 'woocommerce_variation_is_active', 'bbloomer_grey_out_variations_out_of_stock', 10, 2 );
function bbloomer_grey_out_variations_out_of_stock( $is_active, $variation ) {
  if ( ! $variation->is_in_stock() ) return false;
  return $is_active;
}


/**
 * Programmatically Truncate Short Description & Replace With “Read More” Link @ WooCommerce Single Product Page
 */
add_action( 'woocommerce_after_single_product', 'bbloomer_woocommerce_short_description_truncate_read_more' );
function bbloomer_woocommerce_short_description_truncate_read_more() { 
  wc_enqueue_js('
    var show_char = 40;
    var ellipses = "... ";
    var content = $(".woocommerce-product-details__short-description").html();
    if (content.length > show_char) {
      var a = content.substr(0, show_char);
      var b = content.substr(show_char - content.length);
      var html = a + "<span class=\'truncated\'>" + ellipses + "<a class=\'read-more\'>Read more</a></span><span class=\'truncated\' style=\'display:none\'>" + b + "</span>";
      $(".woocommerce-product-details__short-description").html(html);
    }
    $(".read-more").click(function(e) {
      e.preventDefault();
      $(".woocommerce-product-details__short-description .truncated").toggle();
    });'
  );
}

/**
 * Get Customers Who Purchased Product ID
 */
function customer_purchase_particular_product(){
  global $wpdb;// Access WordPress database
  $product_id = 282; // Select Product ID    
  $statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
  $customer_emails = $wpdb->get_col(" SELECT DISTINCT pm.meta_value FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' ) AND pm.meta_key IN ( '_billing_email' ) AND im.meta_key IN ( '_product_id', '_variation_id' ) AND im.meta_value = $product_id"
  ); 
  // Print array on screen
  print_r( $customer_emails );
}

/**
 * Redirect Customers Who Purchased Away From the Single Product Page
 */
add_action( 'template_redirect', 'bbloomer_single_product_redirect_logged_in_purchased' );
function bbloomer_single_product_redirect_logged_in_purchased() { 
  if ( ! is_product() && ! is_user_logged_in() ) return;  
  $current_user = wp_get_current_user();
  $product_id = get_queried_object_id();
  if ( wc_customer_bought_product( $current_user->user_email, $current_user->ID, $product_id ) ) {
    wp_safe_redirect('/custom-url');
    exit;
  }
}

/**
 * Change Product Price Based on Quantity Added to Cart (Bulk Pricing)
 * In our example, our product price is €34 and I want to apply a 5% discount above 100 units and a 10% discount above 1000 units. Screenshots for threshold 1 and threshold 2 are below the snippet.
 */
add_action( 'woocommerce_before_calculate_totals', 'bbloomer_quantity_based_pricing', 9999 );
function bbloomer_quantity_based_pricing( $cart ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
  if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;
  // Define discount rules and thresholds
  $threshold1 = 101; // Change price if items > 100
  $discount1 = 0.05; // Reduce unit price by 5%
  $threshold2 = 1001; // Change price if items > 1000
  $discount2 = 0.1; // Reduce unit price by 10%
  foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
    if ( $cart_item['quantity'] >= $threshold1 && $cart_item['quantity'] < $threshold2 ) {
      $price = round( $cart_item['data']->get_price() * ( 1 - $discount1 ), 2 );
      $cart_item['data']->set_price( $price );
    } elseif ( $cart_item['quantity'] >= $threshold2 ) {
      $price = round( $cart_item['data']->get_price() * ( 1 - $discount2 ), 2 );
      $cart_item['data']->set_price( $price );
    }    
  }
}