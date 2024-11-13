<?php 
/*
* Plugin Name: CSV Upload Jewellery
* Description: This is custom plugin for creation products.
* Author: Tushar K.
* Version: 1.0
* Plugin URI: https://example.com
* Author URI: https://example.com
* Requires at least: 6.0
* Tested up to: 6.3        // Latest WordPress version tested
* Requires PHP: 7.4        // PHP version required
* WC requires at least: 5.0 // WooCommerce version required
* WC tested up to: 8.0 
*/

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Check if WooCommerce is active

// Add menu page for CSV Import
add_action('admin_menu', 'custom_csv_menu_page');

function custom_csv_menu_page() {
    add_menu_page(
        'CSV Reader',            // Page title
        'CSV Reader',            // Menu title
        'manage_options',        // Capability
        'csv-reader',            // Menu slug
        'csv_reader_callback',   // Callback function
        'dashicons-media-spreadsheet', // Icon
        6                        // Position
    );
}

function csv_reader_callback() {
    ?>
    <div class="wrap">
        <h1>CSV Reader</h1>
        <form method="post">
            <input type="hidden" name="read_csv" value="1">
            <button type="submit" class="button button-primary">Read CSV</button>
        </form>
        <?php
        if (isset($_POST['read_csv'])) {
            $csv_file = ABSPATH . 'csv/jew.csv';
            create_or_update_products_from_csv($csv_file);
        }
         if (isset($_SESSION['csv_import_progress'])) {
            echo '<div class="notice notice-info"><p>Progress: ' . $_SESSION['csv_import_progress']['imported'] . ' of ' . $_SESSION['csv_import_progress']['total'] . ' products imported.</p></div>';
        }
        ?>
    </div>
    <?php
}


// function create_or_update_products_from_csv($csv_file_path) {
//     if (($handle = fopen($csv_file_path, 'r')) !== FALSE) {
//         $headers = fgetcsv($handle, 1000, ','); // Get the header row
        
//         $products_data = [];
        
//         // Read the CSV into an array
//         while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
//             $product_data = array_combine($headers, $data);
//             $products_data[] = $product_data; // Collect product data
//         }
        
//         fclose($handle);
        
//         // Prepare to merge side products into their parent
//         $merged_products = [];
//         $last_main_product = null; // Keep track of the last main product
        
//         foreach ($products_data as $product) {
//             // Check if it's a main product
//             if (!empty($product['DESIGN_CODE'])) {
//                 $last_main_product = $product; // Update the last main product
//                 $merged_products[] = $product; // Add main product to the merged list
//             } else {
//                 // Check if it's a side product with a non-empty SHAPE
//                 if (!empty($product['SHAPE']) && $last_main_product) {
//                     // Merge side product data into the last main product
//                     $last_main_product = array_merge($last_main_product, [
//                         'SIDE_DIAMOND_TYPE' => $product['DIAMOND_TYPE'],
//                         'SIDE_SHAPE' => $product['SHAPE'],
//                         'SIDE_COLOR' => $product['COLOR'],
//                         'SIDE_CLARITY' => $product['CLARITY'],
//                         'SIDE_PIECES' => $product['PIECES'],
//                         'SIDE_SIZE_MIN' => $product['SIZE_MIN'],
//                         'SIDE_SIZE_MAX' => $product['SIZE_MAX'],
//                         'SIDE_TOTAL_WGT' => $product['TOTAL_WGT'],
//                     ]);
//                 }
//             }
//             // Always update the merged products list with the latest product data
//             if (!empty($last_main_product)) {
//                 $merged_products[count($merged_products) - 1] = $last_main_product;
//             }
//         }

//         echo '<pre>';
//         print_r($merged_products);
//     }
// }





function create_or_update_products_from_csv($csv_file_path) {
    if (($handle = fopen($csv_file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle, 1000, ','); // Get the header row
        
        $products_data = [];
        
        // Read the CSV into an array
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $product_data = array_combine($headers, $data);
            $products_data[] = $product_data; // Collect product data
        }
        
        fclose($handle);

         $merged_products = [];
        $last_main_product = null;

        foreach ($products_data as $product) {
            // Check if it's a main product
            if (!empty($product['DESIGN_CODE'])) {
                $last_main_product = $product; // Update the last main product
                $merged_products[] = $product; // Add main product to the merged list
            } else {
                // Check if it's a side product with a non-empty SHAPE
                if (!empty($product['SHAPE']) && $last_main_product) {
                    // Merge side product data into the last main product
                    $last_main_product = array_merge($last_main_product, [
                        'SIDE_DIAMOND_TYPE' => $product['DIAMOND_TYPE'],
                        'SIDE_SHAPE' => $product['SHAPE'],
                        'SIDE_COLOR' => $product['COLOR'],
                        'SIDE_CLARITY' => $product['CLARITY'],
                        'SIDE_PIECES' => $product['PIECES'],
                        'SIDE_SIZE_MIN' => $product['SIZE_MIN'],
                        'SIDE_SIZE_MAX' => $product['SIZE_MAX'],
                        'SIDE_TOTAL_WGT' => $product['TOTAL_WGT'],
                    ]);
                }
            }
            // Always update the merged products list with the latest product data
            if (!empty($last_main_product)) {
                $merged_products[count($merged_products) - 1] = $last_main_product;
            }
        }

        echo '<pre>';
        print_r($merged_products);

        // Group by DESIGN_CODE
        $grouped_products = [];
        foreach ($merged_products as $product) {
            $design_code = $product['DESIGN_CODE'];
            $grouped_products[$design_code][] = $product;
        }

         // Count variable products
        $total_variable_products = count($grouped_products);
        $_SESSION['csv_import_progress'] = [
            'total' => $total_variable_products,
            'imported' => 0
        ];

        // Create or update variable products
        foreach ($grouped_products as $design_code => $products) {
            $first_product = $products[0]; // The first row will be the variable product
            
            // Generate a unique SKU for the variable product
            $variable_product_sku = $first_product['ITEM_CODE'] . '-parent';

            // Check if the variable product already exists
            $product_id = wc_get_product_id_by_sku($variable_product_sku);
            $variable_product = $product_id ? new WC_Product_Variable($product_id) : new WC_Product_Variable();

            // Set or update product details
            $variable_product->set_sku($variable_product_sku);
            $variable_product->set_name($first_product['TITLE']);            
            $long_description = !empty($first_product['LONG_DESC']) ? $first_product['LONG_DESC'] : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
            $variable_product->set_description($long_description);
            $variable_product->set_catalog_visibility('visible');
            $variable_product->set_status('publish');

            // Collect attributes for the variable product
            $attributes = [];

            // Metal Type Attribute
            $metal_types = get_terms_from_products($products, 'METAL_TYPE');
            if (!empty($metal_types)) {
                $metal_type_attr_id = create_product_attribute('Metal Type', 'metal-type', $metal_types);
                $metal_attribute = new WC_Product_Attribute();
                $metal_attribute->set_id($metal_type_attr_id);
                $metal_attribute->set_name('pa_metal-type');
                $metal_attribute->set_options($metal_types);
                $metal_attribute->set_position(0);
                $metal_attribute->set_visible(true);
                $metal_attribute->set_variation(true); // Mark as used for variations
                $attributes[] = $metal_attribute;
            }

            // Shape Attribute
            $shapes = get_terms_from_products($products, 'SHAPE');
            if (!empty($shapes)) {
                $shape_attr_id = create_product_attribute('Shape', 'shape', $shapes);
                $shape_attribute = new WC_Product_Attribute();
                $shape_attribute->set_id($shape_attr_id);
                $shape_attribute->set_name('pa_shape');
                $shape_attribute->set_options($shapes);
                $shape_attribute->set_position(1);
                $shape_attribute->set_visible(true);
                $shape_attribute->set_variation(true); // Mark as used for variations
                $attributes[] = $shape_attribute;
            }

            // Assign attributes to the variable product
            $variable_product->set_attributes($attributes);

            // Handle Categories and Subcategories
            $categories = [];
            foreach ($products as $product) {
                $category = $product['CATEGORY'];
                $subcategory = $product['SUB_CATEGORY'];

                // Create or get the category term
                $category_term = term_exists($category, 'product_cat') ? get_term_by('name', $category, 'product_cat') : wp_insert_term($category, 'product_cat');
                $category_id = is_array($category_term) ? $category_term['term_id'] : $category_term->term_id;

                // Create or get the subcategory term (set the parent as the category)
                $subcategory_term = term_exists($subcategory, 'product_cat') ? get_term_by('name', $subcategory, 'product_cat') : wp_insert_term($subcategory, 'product_cat', ['parent' => $category_id]);
                $subcategory_id = is_array($subcategory_term) ? $subcategory_term['term_id'] : $subcategory_term->term_id;

                // Collect category and subcategory IDs
                $categories[] = $category_id;
                $categories[] = $subcategory_id;
            }

            // Assign the categories to the variable product
            $variable_product->set_category_ids(array_unique($categories));


            if (!empty($first_product['IMG1'])) {
                $new_image_id = download_and_attach_image($first_product['IMG1']);
                if ($new_image_id) {
                    $variable_product->set_image_id($new_image_id);
                } else {
                    error_log('Failed to set featured image for product: ' . $first_product['ITEM_CODE']);
                }
            }

            // Collect gallery images
            $gallery_images = [];
            foreach ($products as $product) {
                if (!empty($product['IMG1'])) {
                    $image_id = download_and_attach_image($product['IMG1']);
                    if ($image_id) {
                        $gallery_images[] = $image_id;
                    }
                }
            }

            // Set gallery images if available
            if (!empty($gallery_images)) {
                $variable_product->set_gallery_image_ids(array_unique($gallery_images));
            }

            // Save the variable product
            $variable_product->save();

            $custom_meta_keys = [
                'DESIGN_CODE',
                'DIAMOND_TYPE',
                'SHAPE',
                'COLOR',
                'CLARITY',
                'PIECES',
                'SETTING_TYPE',
                'SIZE_MIN',
                'SIZE_MAX',
                'TOTAL_WGT',
                'METAL_TYPE',
                'METAL_STAMP',
                'APPX_WEIGHT'
            ];

            if (!empty($last_main_product)) {
                update_post_meta($variable_product->get_id(), 'side_diamond_type', $last_main_product['SIDE_DIAMOND_TYPE']);
                update_post_meta($variable_product->get_id(), 'side_shape', $last_main_product['SIDE_SHAPE']);
                update_post_meta($variable_product->get_id(), 'side_color', $last_main_product['SIDE_COLOR']);
                update_post_meta($variable_product->get_id(), 'side_clarity', $last_main_product['SIDE_CLARITY']);
                update_post_meta($variable_product->get_id(), 'side_pieces', $last_main_product['SIDE_PIECES']);
                update_post_meta($variable_product->get_id(), 'side_size_min', $last_main_product['SIDE_SIZE_MIN']);
                update_post_meta($variable_product->get_id(), 'side_size_max', $last_main_product['SIDE_SIZE_MAX']);
                update_post_meta($variable_product->get_id(), 'side_total_wgt', $last_main_product['SIDE_TOTAL_WGT']);
            }

            // Create or update variations
            $default_variation_id = null;
            $first_variation_attributes = null;
           foreach ($products as $index => $variation_data) {
                $variation_sku = $variation_data['ITEM_CODE']; // Original SKU for variation

                // Check if the variation already exists by SKU
                $variation_id = wc_get_product_id_by_sku($variation_sku);
                $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();

                // Set the parent ID to the variable product
                $variation->set_parent_id($variable_product->get_id());
                $variation->set_sku($variation_sku);

                // Set attributes for the variation
                $variation_attributes = [
                    'pa_metal-type' => create_or_get_term_slug('pa_metal-type', $variation_data['METAL_TYPE']),
                    'pa_shape' => create_or_get_term_slug('pa_shape', $variation_data['SHAPE']),
                ];
                $variation->set_attributes($variation_attributes);

                // Set price from LAB_PRICE
                $variation->set_regular_price($variation_data['LAB_PRICE']);

                // Handle the variation image
                if (!empty($variation_data['IMG1'])) {
                    $new_image_id = download_and_attach_image($variation_data['IMG1']);
                    
                    // If the image is already uploaded, get its ID
                    if ($new_image_id) {
                        $variation->set_image_id($new_image_id); // Set as variation image
                    } else {
                        // If the image could not be downloaded, try to find it by URL
                        $existing_image_id = attachment_url_to_postid($variation_data['IMG1']);
                        if ($existing_image_id) {
                            $variation->set_image_id($existing_image_id); // Use existing image ID
                        } else {
                            error_log('No image found for variation SKU: ' . $variation_sku);
                        }
                    }
                }

                // Handle gallery images (IMG2, IMG3, etc.)
                $gallery_images = [];
                for ($i = 2; $i <= 5; $i++) { // Adjust based on your columns
                    $image_key = 'IMG' . $i;
                    if (!empty($variation_data[$image_key])) {
                        $new_gallery_image_id = download_and_attach_image($variation_data[$image_key]);
                        if ($new_gallery_image_id) {
                            $gallery_images[] = $new_gallery_image_id;
                        } else {
                            $existing_gallery_image_id = attachment_url_to_postid($variation_data[$image_key]);
                            if ($existing_gallery_image_id) {
                                $gallery_images[] = $existing_gallery_image_id;
                            } else {
                                error_log('No gallery image found for variation SKU: ' . $variation_sku . ', Image Key: ' . $image_key);
                            }
                        }
                    }
                }

                // Dynamically add images to the variation gallery
                if (!empty($gallery_images)) {
                    $existing_gallery_ids = get_post_meta($variation->get_id(), '_product_image_gallery', true);
                    $existing_gallery_ids_array = !empty($existing_gallery_ids) ? explode(',', $existing_gallery_ids) : [];
                    
                    // Merge and clean up image IDs
                    $all_gallery_image_ids = array_merge($existing_gallery_ids_array, $gallery_images);
                    $all_gallery_image_ids = array_unique(array_filter($all_gallery_image_ids));

                    // Save RTWPVG gallery images
                    update_post_meta($variation->get_id(), 'rtwpvg_images', $all_gallery_image_ids);
                    
                    // Save WooCommerce gallery
                    update_post_meta($variation->get_id(), '_product_image_gallery', implode(',', $all_gallery_image_ids));

                    error_log('RTWPVG Gallery images updated for variation SKU: ' . $variation_sku . ', Image IDs: ' . implode(',', $all_gallery_image_ids));
                } else {
                    error_log('No gallery images found for variation SKU: ' . $variation_sku);
                }

                 foreach ($custom_meta_keys as $key) {
                    if (isset($variation_data[$key])) {
                        update_post_meta($variation->get_id(), strtolower($key), $variation_data[$key]);
                    }
                }

                // Add video URL to custom meta

                if (isset($variation_data['VIDEO'])) {
                    $video_id = $variation_data['VIDEO'];
                    $video_id = download_and_attach_video($video_id);

                    // Log the video ID for debugging
                    error_log('Processing VIDEO ID: ' . $video_id);

                    // Check if the video ID exists in the media library
                    $video_attachment = get_post($video_id);
                    if ($video_attachment && $video_attachment->post_type === 'attachment') {
                        // Get the URL of the video
                        error_log('come in this run');
                        $video_url = wp_get_attachment_url($video_id);

                        update_post_meta($variation->get_id(), 'variation_video_url', $video_url);
                    } else {
                        error_log('No valid video found for variation SKU: ' . $variation_sku . ', Video ID: ' . $video_id);
                    }
                }
                $video_url = get_post_meta($variation->get_id(), 'variation_video_url', true);
                // $video_url = wp_get_attachment_url($video_url1);

                error_log('VID URL : ' . $variation_sku .', Video URL: ' . $video_url);

                // Create an HTML table for variation description
                $variation_description = create_variation_table($variation_data);
                $variation->set_description($variation_description);

                if ($video_url) {
                    $variation_description .= '<div class="variation-video" style="margin-top: 10px;">';
                    // $variation_description .= '<h4>Product Video:</h4>';
                    $variation_description .= '<iframe width="560" height="315" src="' . esc_url($video_url) . '" frameborder="0" allowfullscreen></iframe>';
                    $variation_description .= '</div>';
                }
                
                $variation->set_description($variation_description);

                if ($index === 0) {
                    $default_variation_id = $variation->get_id();
                    $first_variation_attributes = $variation_attributes;
                }

                if ($default_variation_id) {
                    $variable_product->set_default_attributes($first_variation_attributes);
                     $variable_product->save();
                }
                $variation->save();

            }
              $_SESSION['csv_import_progress']['imported']++;

        }
        
        echo '<div class="notice notice-success"><p>Variable products created or updated successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Could not open the file.</p></div>';
    }
}

function get_terms_from_products($products, $key) {
    return array_unique(array_map(function($product) use ($key) {
        return $product[$key];
    }, $products));
}

function create_product_attribute($name, $slug, $terms) {
    global $wpdb;

    // Check if attribute already exists by slug
    $attribute_taxonomy_name = wc_attribute_taxonomy_name($slug);
    $attribute_taxonomy = $wpdb->get_row( $wpdb->prepare( "
        SELECT attribute_id 
        FROM {$wpdb->prefix}woocommerce_attribute_taxonomies 
        WHERE attribute_name = %s 
        LIMIT 1;", $slug) );

    if (!$attribute_taxonomy) {
        // If attribute doesn't exist, create it
        $attribute_id = wc_create_attribute([
            'name' => $name,
            'slug' => $slug,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ]);

        if (is_wp_error($attribute_id)) {
            error_log('Error creating attribute "' . $name . '": ' . $attribute_id->get_error_message());
            return null;
        }
    } else {
        $attribute_id = $attribute_taxonomy->attribute_id;
    }

    // Ensure terms are added and retrieve term IDs
    foreach ($terms as $term) {
        wp_insert_term($term, 'pa_' . $slug);
    }

    return $attribute_id;
}

function create_or_get_term_slug($taxonomy, $term_name) {
    // Check if the term exists in the specified taxonomy
    if (!term_exists($term_name, $taxonomy)) {
        // Create the term if it doesn't exist
        $term = wp_insert_term($term_name, $taxonomy);

        if (!is_wp_error($term)) {
            return $term_name; // Return the slug
        } else {
            error_log('Error creating term: ' . $term_name . ' - ' . $term->get_error_message());
            return '';
        }
    } else {
        $term_object = get_term_by('name', $term_name, $taxonomy);
        return $term_object ? $term_object->slug : '';
    }
}

function download_and_attach_image($image_url) {
    if (empty($image_url)) {
        return false; // Ensure the URL is not empty
    }

    // Get the file name and upload directory
    $file_name = basename($image_url);
    $upload_dir = wp_upload_dir();

    // Check if the file already exists
    $existing_file = $upload_dir['path'] . '/' . $file_name;
    if (file_exists($existing_file)) {
        return attachment_url_to_postid($upload_dir['url'] . '/' . $file_name);
    }

    // Download the image
    $image_data = wp_remote_get($image_url);
    if (is_wp_error($image_data)) {
        error_log('Failed to download image: ' . $image_url);
        return false;
    }

    $image_contents = wp_remote_retrieve_body($image_data);
    $image_type = wp_remote_retrieve_header($image_data, 'content-type');

    // Check if the content type is an image
    if (strpos($image_type, 'image/') !== false) {
        // Prepare the image for upload
        $file = wp_upload_bits($file_name, null, $image_contents);
        if ($file['error']) {
            error_log('Failed to upload image: ' . $file['error']);
            return false;
        }

        // Insert image into the WordPress media library
        $attachment = array(
            'post_mime_type' => $image_type,
            'post_title'     => sanitize_file_name($file_name),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment($attachment, $file['file']);
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            return $attachment_id; // Return the attachment ID
        }
    } else {
        error_log('Invalid image type: ' . $image_url);
    }

    return false; // Return false if the image could not be processed
}

function create_variation_table($data) {
    // Define custom titles for the attributes
    $custom_meta_keys = [
        'DESIGN_CODE' => 'Design Code',
        'METAL_TYPE' => 'Metal Type',
        'DIAMOND_TYPE' => 'Diamond Type',
        'SHAPE' => 'Shape',
        'COLOR' => 'Color',
        'CLARITY' => 'Clarity',
        'PIECES' => 'Pieces',
        'SETTING_TYPE' => 'Setting Type',
        'SIZE_MIN' => 'Size Min',
        'SIZE_MAX' => 'Size Max',        
        'TOTAL_WGT' => 'Total Weight',        
        'METAL_STAMP' => 'Metal Stamp',
        // 'SIDE_DIAMOND_TYPE' => 'Side Diamond Type',
        'SIDE_SHAPE' => 'Side Diamond Shape',
        'SIDE_COLOR' => 'Side Diamond Color',
        'SIDE_CLARITY' => 'Side Diamond Clarity',
        'SIDE_PIECES' => 'Side Diamond Pieces',
        'SIDE_SIZE_MIN' => 'Side Diamond Size Min',
        'SIDE_SIZE_MAX' => 'Side Diamond Size Max',
        'SIDE_TOTAL_WGT' => 'Side Diamond Total Weight',
        'APPX_WEIGHT' => 'Approx Weight'
    ];   

    // Prepare the data based on the custom keys
    $custom_meta_data = [];
    foreach ($custom_meta_keys as $key => $title) {
        $value = $data[$key] ?? ''; // Get value or empty string
        if (!empty($value)) { // Only add non-empty values
            $custom_meta_data[$title] = $value;
        }
    }

    // Split data into two columns
    $first_column = [];
    $second_column = [];

    foreach ($custom_meta_data as $key => $value) {
        if (in_array($key, ['Setting Type', 'Size Min', 'Size Max', 'Total Weight', 'Approx Weight', 'Metal Stamp', 'Side Diamond Clarity', 'Side Diamond Size Min', 'Side Diamond Total Weight'])) {
            $second_column[$key] = $value;
        } else {
            $first_column[$key] = $value;
        }
    }

    // Start the custom HTML output
    $output = '<div class="stone_detail_wrapper jew_detail">';
    $output .= '<h4>Jewellery Details</h4>';
    $output .= '<div class="stone_detail_wrapper_inner">';
    $output .= '<div class="g-2 row">';

    // First column
    if (!empty($first_column)) {
        $output .= '<div class="col-md-6">';
        $output .= '<div class="additional_detail_box"><table><tbody>';
        foreach ($first_column as $key => $value) {
            $output .= '<tr><td>' . esc_html($key) . '</td><td>' . esc_html($value) . '</td></tr>';
        }
        $output .= '</tbody></table></div></div>'; // Close first column
    }

    // Second column
    if (!empty($second_column)) {
        $output .= '<div class="col-md-6">';
        $output .= '<div class="additional_detail_box"><table><tbody>';
        foreach ($second_column as $key => $value) {
            $output .= '<tr><td>' . esc_html($key) . '</td><td>' . esc_html($value) . '</td></tr>';
        }
        $output .= '</tbody></table></div></div>'; // Close second column
    }

    $output .= '</div>'; // Close row
    $output .= '</div>'; // Close stone_detail_wrapper
    $output .= '</div>'; // Close main wrapper

    return $output;
}






function download_and_attach_video($video_url) {
    // Parse the video URL to get the file name
    $file_name = basename($video_url);
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . $file_name;

    // Check if the file already exists in the media library
    $existing_video_id = attachment_url_to_postid($upload_dir['url'] . '/' . $file_name);
    if ($existing_video_id) {
        return $existing_video_id; // Return the existing video ID
    }

    // Download the video
    $response = wp_remote_get($video_url);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        return false; // Handle error or non-200 response
    }

    // Save the file to the uploads directory
    file_put_contents($file_path, wp_remote_retrieve_body($response));

    // Prepare the file for attachment
    $attachment = array(
        'guid' => $upload_dir['url'] . '/' . $file_name,
        'post_mime_type' => 'video/mp4',
        'post_title' => sanitize_file_name($file_name),
        'post_content' => '',
        'post_status' => 'inherit',
    );

    // Insert the attachment into the media library
    $attachment_id = wp_insert_attachment($attachment, $file_path);

    // Include the necessary WordPress files
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return $attachment_id; // Return the new video ID
}