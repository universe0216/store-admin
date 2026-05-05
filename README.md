# CodeIgniter 4 Framework

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds the distributable version of the framework.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Contributing

We welcome contributions from the community.

Please read the [*Contributing to CodeIgniter*](https://github.com/codeigniter4/CodeIgniter4/blob/develop/CONTRIBUTING.md) section in the development repository.

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library

## Store App Database SQL

The following SQL schema matches the migration file `app/Database/Migrations/2026-04-25-000001_CreateStoreSchema.php`.

```sql
CREATE TABLE categories (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT(11) UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_categories_name (name),
    KEY idx_categories_parent_id (parent_id),
    CONSTRAINT fk_categories_parent_id
        FOREIGN KEY (parent_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

CREATE TABLE products (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT(11) UNSIGNED NOT NULL,
    brand VARCHAR(100) NULL,
    serial_number VARCHAR(255) NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_products_name (name),
    KEY idx_products_category_id (category_id),
    CONSTRAINT fk_products_category_id
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE variant_attributes (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_variant_attributes_name (name)
);

CREATE TABLE variant_attribute_values (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id INT(11) UNSIGNED NOT NULL,
    value VARCHAR(100) NOT NULL,
    sort_order INT(11) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_variant_attribute_values_attribute_value (attribute_id, value),
    KEY idx_variant_attribute_values_attribute_id (attribute_id),
    CONSTRAINT fk_variant_attribute_values_attribute_id
        FOREIGN KEY (attribute_id) REFERENCES variant_attributes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE product_variants (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT(11) UNSIGNED NOT NULL,
    sku VARCHAR(50) NOT NULL,
    barcode VARCHAR(50) NULL,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock_qty INT(11) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_product_variants_sku (sku),
    UNIQUE KEY uq_product_variants_barcode (barcode),
    KEY idx_product_variants_product_id (product_id),
    CONSTRAINT fk_product_variants_product_id
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE product_variant_values (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_variant_id BIGINT(20) UNSIGNED NOT NULL,
    attribute_id INT(11) UNSIGNED NOT NULL,
    attribute_value_id INT(11) UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_product_variant_values_variant_attribute (product_variant_id, attribute_id),
    UNIQUE KEY uq_product_variant_values_variant_value (product_variant_id, attribute_value_id),
    KEY idx_product_variant_values_attribute_value_id (attribute_value_id),
    CONSTRAINT fk_product_variant_values_variant_id
        FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_product_variant_values_attribute_id
        FOREIGN KEY (attribute_id) REFERENCES variant_attributes(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_product_variant_values_attribute_value_id
        FOREIGN KEY (attribute_value_id) REFERENCES variant_attribute_values(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE suppliers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    address TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_suppliers_name (name)
);

CREATE TABLE purchases (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_no VARCHAR(30) NOT NULL,
    purchase_date DATETIME NOT NULL,
    supplier_id INT(11) UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    sub_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_purchases_purchase_no (purchase_no),
    KEY idx_purchases_purchase_date (purchase_date),
    KEY idx_purchases_supplier_id (supplier_id),
    CONSTRAINT fk_purchases_supplier_id
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE purchase_items (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT(20) UNSIGNED NOT NULL,
    product_variant_id BIGINT(20) UNSIGNED NOT NULL,
    qty INT(11) UNSIGNED NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(12,2) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_purchase_items_purchase_id (purchase_id),
    KEY idx_purchase_items_product_variant_id (product_variant_id),
    CONSTRAINT fk_purchase_items_purchase_id
        FOREIGN KEY (purchase_id) REFERENCES purchases(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_purchase_items_product_variant_id
        FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE sales (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_no VARCHAR(30) NOT NULL,
    sale_date DATETIME NOT NULL,
    customer_name VARCHAR(120) NULL,
    sub_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'cash',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_sales_sale_no (sale_no),
    KEY idx_sales_sale_date (sale_date)
);

CREATE TABLE sale_items (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT(20) UNSIGNED NOT NULL,
    product_variant_id BIGINT(20) UNSIGNED NOT NULL,
    qty INT(11) UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(12,2) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_sale_items_sale_id (sale_id),
    KEY idx_sale_items_product_variant_id (product_variant_id),
    CONSTRAINT fk_sale_items_sale_id
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product_variant_id
        FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE stock_movements (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_variant_id BIGINT(20) UNSIGNED NOT NULL,
    movement_type VARCHAR(20) NOT NULL,
    qty_change INT(11) NOT NULL,
    reference_type VARCHAR(30) NULL,
    reference_id BIGINT(20) UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_stock_movements_product_variant_id (product_variant_id),
    KEY idx_stock_movements_movement_type (movement_type),
    KEY idx_stock_movements_created_at (created_at),
    CONSTRAINT fk_stock_movements_product_variant_id
        FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);
```
