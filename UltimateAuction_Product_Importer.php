<?php
/**
 * Plugin Name: CSV Importer for 'Ultimay Auction for WooCommerce' - By RegorSec
 * Description: A plugin that imports auction products from a CSV file into WooCommerce using the Ultimate Auction For WooCommerce plugin.
 * Version: 1.11
 * Author: RegorSec
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_action( 'admin_menu', 'auction_product_importer_menu' );

function auction_product_importer_menu() {
    add_menu_page(
        'Auction Product Importer',
        'Auction Product Importer',
        'manage_options',
        'auction-product-importer',
        'auction_product_importer_page',
        'dashicons-store',
        30
    );
}


function auction_product_importer_page() {
    if ( isset( $_POST['submit'] ) ) {
        // Get the uploaded file.
        $file = $_FILES['file'];

        // Check if the file is a CSV.
        if ( $file['type'] != 'text/csv' ) {
            echo '<div class="notice notice-error"><p>The file must be a CSV.</p></div>';
            return;
        }

        // Open the file.
        $handle = fopen( $file['tmp_name'], 'r' );

        // Loop through the rows.
        while ( $row = fgetcsv( $handle ) ) {

            // Get the product data.
            $title = $row[1];
            $price = $row[2];
            $stime = $row[3] . " " . $row[4];
            $etime = $row[5] . " " . $row[6];
	        $category = $row[7];
	        $description = $row[8];
            $productname = $row[1];

            // Convert the start and end times to a proper format.
            $start_time = date( 'Y-m-d H:i:s', strtotime( $stime ) );
            $end_time = date( 'Y-m-d H:i:s', strtotime( $etime ) );
            

            // Create the product new
            $product = array(
            'post_title'    => $productname ,
            'post_content'  => $description,
            'post_status'   => 'publish',
            'post_type'     => 'product',
            'product_type'  => 'auction',
            'meta_input'    => array(
            'uwa_auction_proxy'     => '0',
            'woo_ua_auction_started' => '0',
            'uwa_auction_silent' => '0',
            'woo_ua_auction_end_date'   => $end_time,
            'woo_ua_auction_has_started'=> '0',
            'woo_ua_auction_selling_type'  => 'auction',
            'woo_ua_auction_start_date' => $start_time,
            'woo_ua_auction_type'     => 'normal',
            'woo_ua_bid_increment'=> '1',
            'woo_ua_buyer_level' => 'globally',
            'woo_ua_lowest_price'          => $price,
            'woo_ua_next_bids'        => '1',
            'woo_ua_opening_price'       => $price,
            'woo_ua_product_condition'=> 'used'
            ),
        );

        $product_id = wp_insert_post( $product );

        // Set the product_type to auction
        wp_set_object_terms( $product_id, 'auction', 'product_type' );

	    // Set the category type based on csv data
	    wp_set_object_terms( $product_id, $category, 'product_cat', false );

        // Add Images to Product
        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => 'image',
            's'              => $title
        );

        $attachments = get_posts($args);

        // Set product gallery images
        $attachment_ids = array();

        foreach ($attachments as $attachment) {
           $attachment_ids[] = $attachment->ID;
        }

	    if (count($attachment_ids) > 0) {

            update_post_meta($product_id, '_product_image_gallery', implode(',', $attachment_ids));

            // Set product primary image
            usort($attachments, function($a, $b) {
            $a_num = (int)substr($a->post_title, 1);
            $b_num = (int)substr($b->post_title, 1);
            return $b_num - $a_num;
            });

            $highest_attachment = $attachments[0];

            set_post_thumbnail($product_id, $highest_attachment->ID);

	    }
    }

        echo '<div class="notice notice-success"><p>The products have been imported.</p></div>';
    }
    ?>

    <div class="wrap">
        <h1>CSV Importer for 'Ultimay Auction for WooCommerce' - By RegorSec</h1>

        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label for="file">File</label></th>
                    <td><input type="file" name="file" required></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
