# CSV Variation Product Importer Plugin

This WordPress plugin helps you create WooCommerce variation products by reading data from a CSV file. The plugin automatically downloads images from third-party image URLs provided in the CSV and uploads them to the WordPress media library. It then assigns these images to the corresponding products and their variations.

## Features

### 1. **CSV File Handling**
- The plugin reads product and variation data from a CSV file located in the `csv` folder of your WordPress installation.
- The CSV file should contain product information such as product titles, SKUs, prices, variation attributes, and image URLs.

### 2. **Variation Product Creation**
- The plugin automatically creates WooCommerce variation products based on the data from the CSV file.
- The product variations (such as size, color, etc.) are created after the main product is uploaded.

### 3. **Backend Menu Page**
- A new menu page titled **CSV Reader** is added to the WordPress admin dashboard.
- This page includes a **Read CSV** button to start the process of creating variation products from the CSV file.

### 4. **Two-Step Process for Variation Products**
- To create variation products, you may need to click the **Read CSV** button twice:
  1. The first click uploads the products.
  2. The second click ensures that all variations are successfully created and assigned to the product.

### 5. **Image Download and Upload**
- The plugin automatically downloads images from external URLs (provided in the CSV file) and uploads them to the WordPress media library.
- These images are then assigned to the product variations as per the data in the CSV.

## CSV File
- The CSV file used for product creation is located in the `csv` folder in the root directory of your WordPress installation.
- The CSV file is named `jew.csv`.

You can view the file here: [csv/jew.csv](csv/jew.csv)

## How It Works

1. **Upload CSV File**: Place the CSV file (`jew.csv`) in the `csv` folder in the root directory of your WordPress installation.
2. **Access the Plugin**: Navigate to the **CSV Reader** page in the WordPress admin dashboard.
3. **Read CSV**: Click the **Read CSV** button to begin the import process.
4. **Two-Step Process**: Click the button twice:
    - The first click uploads the main products.
    - The second click uploads and assigns variations to the products.
5. **Download and Upload Images**: If the CSV contains image URLs, the plugin will download and upload them to the WordPress media library.

## Requirements
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.2 or higher

## Installation

1. Download and extract the plugin files.
2. Upload the plugin folder to your WordPress `wp-content/plugins/` directory.
3. Activate the plugin from the **Plugins** menu in WordPress.