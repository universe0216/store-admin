/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80043 (8.0.43)
 Source Host           : localhost:3306
 Source Schema         : my_stock

 Target Server Type    : MySQL
 Target Server Version : 80043 (8.0.43)
 File Encoding         : 65001

 Date: 08/06/2026 14:24:25
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for accounts
-- ----------------------------
DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts`  (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `account_type` enum('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `tag` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'cash',
  `currency_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of accounts
-- ----------------------------
INSERT INTO `accounts` VALUES (1, '1000', 'Cash-USD', 'ASSET', '[\"Capital\"]', 'cash', 'USD', 1, '2026-05-30 06:45:48');
INSERT INTO `accounts` VALUES (2, '1010', 'Bank', 'ASSET', NULL, 'cash', 'USD', 1, '2026-05-30 06:45:48');
INSERT INTO `accounts` VALUES (3, '1200', 'Inventory', 'ASSET', NULL, 'cash', 'USD', 1, '2026-05-30 06:45:48');
INSERT INTO `accounts` VALUES (4, '4000', 'Sales Revenue', 'REVENUE', NULL, 'cash', 'USD', 1, '2026-05-30 06:45:48');
INSERT INTO `accounts` VALUES (5, '5000', 'Cost of Goods Sold', 'EXPENSE', NULL, 'cash', 'USD', 1, '2026-06-02 16:11:19');
INSERT INTO `accounts` VALUES (6, '9000', 'Expense', 'EXPENSE', NULL, 'cash', 'USD', 1, '2026-06-02 16:13:34');
INSERT INTO `accounts` VALUES (7, '5100', 'Transfer fee', 'EXPENSE', NULL, 'cash', 'USD', 1, '2026-06-02 16:43:52');
INSERT INTO `accounts` VALUES (8, '3000', 'Salary', 'REVENUE', NULL, 'cash', 'USD', 1, '2026-06-02 17:07:47');
INSERT INTO `accounts` VALUES (9, '1001', 'php', 'ASSET', NULL, 'cash', 'PHP', 1, '2026-06-02 17:25:45');
INSERT INTO `accounts` VALUES (10, '1100', 'Unpaid sales', 'ASSET', '[\"Other\"]', 'cash', 'USD', 1, '2026-06-04 12:59:39');
INSERT INTO `accounts` VALUES (11, '5110', 'Shipping Fee', 'EXPENSE', NULL, 'cash', 'USD', 1, '2026-06-05 03:09:12');
INSERT INTO `accounts` VALUES (12, '1210', 'Inventory COG', 'ASSET', '[\"Other\"]', 'cash', 'USD', 1, '2026-06-04 15:30:55');

-- ----------------------------
-- Table structure for categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int NULL DEFAULT 1,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `department` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_categories_name`(`name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of categories
-- ----------------------------
INSERT INTO `categories` VALUES (2, NULL, 'Shirts', 'apparel', '2026-05-05 15:56:31', '2026-06-03 14:45:05');
INSERT INTO `categories` VALUES (3, NULL, 'Sneakers', 'footwear', '2026-05-05 15:59:54', '2026-06-03 14:45:55');
INSERT INTO `categories` VALUES (7, NULL, 'Sweatshirts', 'apparel', '2026-05-05 16:14:44', '2026-06-03 14:45:16');
INSERT INTO `categories` VALUES (8, NULL, 'Shorts', 'apparel', '2026-05-05 16:16:22', '2026-06-03 14:42:50');
INSERT INTO `categories` VALUES (9, NULL, 'T-Shirts', 'apparel', '2026-05-05 16:29:44', '2026-06-03 14:42:31');
INSERT INTO `categories` VALUES (11, NULL, 'Sandals', 'footwear', '2026-06-03 14:46:08', '2026-06-03 14:46:08');
INSERT INTO `categories` VALUES (12, NULL, 'Casual Shoes', 'footwear', '2026-06-03 14:46:25', '2026-06-03 14:46:25');

-- ----------------------------
-- Table structure for currencies
-- ----------------------------
DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies`  (
  `code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `decimals` tinyint UNSIGNED NOT NULL DEFAULT 2,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of currencies
-- ----------------------------
INSERT INTO `currencies` VALUES ('PHP', 'Peso', 'P', 2);
INSERT INTO `currencies` VALUES ('RMB', 'Yen', 'Y', 2);
INSERT INTO `currencies` VALUES ('RUB', 'rubble', 'P', 2);
INSERT INTO `currencies` VALUES ('USD', 'Dollar', '$', 2);

-- ----------------------------
-- Table structure for department_sizes
-- ----------------------------
DROP TABLE IF EXISTS `department_sizes`;
CREATE TABLE `department_sizes`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `department` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_department_value`(`department` ASC, `value` ASC) USING BTREE,
  INDEX `idx_department`(`department` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of department_sizes
-- ----------------------------
INSERT INTO `department_sizes` VALUES (1, 'Footwear', '220', 10, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (2, 'Footwear', '225', 20, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (3, 'Footwear', '230', 30, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (4, 'Footwear', '235', 40, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (5, 'Footwear', '240', 50, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (6, 'Footwear', '245', 60, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (7, 'Footwear', '250', 70, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (8, 'Apparel', 'XS', 10, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (9, 'Apparel', 'S', 20, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (10, 'Apparel', 'M', 30, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (11, 'Apparel', 'L', 40, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (12, 'Apparel', 'XL', 50, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (13, 'Apparel', 'XXL', 60, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (14, 'Apparel', '2XL', 70, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');
INSERT INTO `department_sizes` VALUES (15, 'Other', 'One Size', 10, 1, '2026-06-08 14:13:24', '2026-06-08 14:13:24');

-- ----------------------------
-- Table structure for exchange_rates
-- ----------------------------
DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `base_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `quote_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `rate` decimal(20, 8) NOT NULL,
  `effective_at` datetime NOT NULL,
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_exchange_rates_pair_date`(`base_currency` ASC, `quote_currency` ASC, `effective_at` ASC) USING BTREE,
  INDEX `fk_exchange_rates_quote_currency`(`quote_currency` ASC) USING BTREE,
  CONSTRAINT `fk_exchange_rates_base_currency` FOREIGN KEY (`base_currency`) REFERENCES `currencies` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_exchange_rates_quote_currency` FOREIGN KEY (`quote_currency`) REFERENCES `currencies` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of exchange_rates
-- ----------------------------
INSERT INTO `exchange_rates` VALUES (1, 'USD', 'RMB', 7.20000000, '2026-05-29 17:47:11', 'manual', '2026-05-29 17:47:11');
INSERT INTO `exchange_rates` VALUES (2, 'USD', 'PHP', 110.00000000, '2026-05-29 17:49:36', 'manual', '2026-05-29 17:49:36');
INSERT INTO `exchange_rates` VALUES (3, 'USD', 'RMB', 7.20000000, '2026-05-29 17:52:05', 'manual', '2026-05-29 17:52:05');
INSERT INTO `exchange_rates` VALUES (4, 'USD', 'RMB', 7.60000000, '2026-06-02 15:52:13', 'manual', '2026-06-02 15:52:13');
INSERT INTO `exchange_rates` VALUES (5, 'USD', 'PHP', 110.00000000, '2026-06-02 17:26:46', 'manual', '2026-06-02 17:26:46');
INSERT INTO `exchange_rates` VALUES (6, 'USD', 'RMB', 7.20000000, '2026-06-02 19:21:08', 'manual', '2026-06-02 19:21:08');
INSERT INTO `exchange_rates` VALUES (7, 'USD', 'PHP', 120.00000000, '2026-06-02 19:23:11', 'manual', '2026-06-02 19:23:11');
INSERT INTO `exchange_rates` VALUES (8, 'USD', 'RUB', 100.00000000, '2026-06-02 19:24:56', 'manual', '2026-06-02 19:24:56');
INSERT INTO `exchange_rates` VALUES (9, 'USD', 'RMB', 7.30000000, '2026-06-04 13:08:55', 'manual', '2026-06-04 13:08:55');
INSERT INTO `exchange_rates` VALUES (10, 'USD', 'RMB', 6.90000000, '2026-06-07 18:20:46', 'manual', '2026-06-07 18:20:46');
INSERT INTO `exchange_rates` VALUES (11, 'USD', 'RMB', 6.95000000, '2026-06-07 18:22:20', 'manual', '2026-06-07 18:22:20');
INSERT INTO `exchange_rates` VALUES (12, 'USD', 'RMB', 7.00000000, '2026-06-07 18:22:26', 'manual', '2026-06-07 18:22:26');

-- ----------------------------
-- Table structure for inventory
-- ----------------------------
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `variant_id` bigint UNSIGNED NOT NULL,
  `warehouse_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT 0,
  `reserved_quantity` int NOT NULL DEFAULT 0,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_inventory_variant_id`(`variant_id` ASC) USING BTREE,
  INDEX `idx_inventory_warehouse_id`(`warehouse_id` ASC) USING BTREE,
  CONSTRAINT `fk_inventory_variant_id` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_warehouse_id` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 41 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of inventory
-- ----------------------------
INSERT INTO `inventory` VALUES (1, 1, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (2, 2, 1, 0, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (3, 3, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (4, 4, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (5, 5, 1, 0, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (6, 6, 1, 0, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (7, 7, 1, 1, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (8, 8, 1, 1, 0, '2026-06-07 19:27:47');
INSERT INTO `inventory` VALUES (9, 9, 1, 1, 0, '2026-06-07 19:27:47');
INSERT INTO `inventory` VALUES (10, 10, 1, 1, 0, '2026-06-07 19:27:47');
INSERT INTO `inventory` VALUES (11, 11, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (12, 12, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (13, 13, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (14, 14, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (15, 15, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (16, 16, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (17, 17, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (18, 18, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (19, 19, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (20, 20, 1, 0, 0, '2026-06-04 15:44:14');
INSERT INTO `inventory` VALUES (21, 21, 1, 0, 0, '2026-06-07 19:14:06');
INSERT INTO `inventory` VALUES (22, 22, 1, 2, 0, '2026-06-07 20:44:55');
INSERT INTO `inventory` VALUES (23, 23, 1, 1, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (24, 24, 1, 0, 0, '2026-06-07 20:44:55');
INSERT INTO `inventory` VALUES (25, 23, 2, 0, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (26, 2, 2, 1, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (27, 6, 2, 0, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (28, 7, 2, 1, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (29, 5, 2, 1, 0, '2026-06-07 21:50:08');
INSERT INTO `inventory` VALUES (30, 25, 1, 1, 0, '2026-06-08 02:18:04');
INSERT INTO `inventory` VALUES (31, 26, 1, 1, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (32, 27, 1, 1, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (33, 28, 1, 0, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (34, 29, 1, 0, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (35, 30, 1, 0, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (36, 31, 1, 1, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (37, 32, 1, 1, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (38, 33, 1, 1, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (39, 34, 1, 1, 0, '2026-06-08 02:19:35');
INSERT INTO `inventory` VALUES (40, 35, 1, 1, 0, '2026-06-08 02:19:35');

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------

-- ----------------------------
-- Table structure for payment_methods
-- ----------------------------
DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `account_id` bigint UNSIGNED NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_payment_methods_code`(`code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of payment_methods
-- ----------------------------
INSERT INTO `payment_methods` VALUES (1, 'cash_usd', 'Cash USD', NULL, 1, 1, '2026-06-04 15:40:33', '2026-06-04 15:40:33');
INSERT INTO `payment_methods` VALUES (2, 'bank_usd', 'Bank USD', NULL, 2, 1, '2026-06-04 15:40:49', '2026-06-04 15:40:49');

-- ----------------------------
-- Table structure for product_variants
-- ----------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int UNSIGNED NOT NULL,
  `style` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `sku` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `barcode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `cost_price` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `stock_qty` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_product_variants_sku`(`sku` ASC) USING BTREE,
  UNIQUE INDEX `uq_product_variants_barcode`(`barcode` ASC) USING BTREE,
  INDEX `idx_product_variants_product_id`(`product_id` ASC) USING BTREE,
  CONSTRAINT `fk_product_variants_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 36 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of product_variants
-- ----------------------------
INSERT INTO `product_variants` VALUES (1, 1, '', '220', 'L23313-220-298', NULL, 14.27, 20.00, 0, 1, '2026-06-04 15:44:13', '2026-06-05 03:55:57');
INSERT INTO `product_variants` VALUES (2, 1, '', '225', 'L23313-225-861', NULL, 14.27, 20.00, 1, 1, '2026-06-04 15:44:13', '2026-06-05 03:55:57');
INSERT INTO `product_variants` VALUES (3, 1, '', '230', 'L23313-230-224', NULL, 14.27, 20.00, 0, 1, '2026-06-04 15:44:14', '2026-06-05 03:55:57');
INSERT INTO `product_variants` VALUES (4, 1, '', '235', 'L23313-235-572', NULL, 14.27, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (5, 1, '', '240', 'L23313-240-653', NULL, 14.27, 20.00, 1, 1, '2026-06-04 15:44:14', '2026-06-05 03:55:57');
INSERT INTO `product_variants` VALUES (6, 2, '', '220', 'xxxxxx-220-790', NULL, 12.14, 20.00, 1, 1, '2026-06-04 15:44:14', '2026-06-07 19:27:47');
INSERT INTO `product_variants` VALUES (7, 2, '', '225', 'xxxxxx-225-154', NULL, 12.14, 20.00, 2, 1, '2026-06-04 15:44:14', '2026-06-07 19:27:47');
INSERT INTO `product_variants` VALUES (8, 2, '', '230', 'xxxxxx-230-399', NULL, 12.14, 20.00, 1, 1, '2026-06-04 15:44:14', '2026-06-07 19:27:47');
INSERT INTO `product_variants` VALUES (9, 2, '', '235', 'xxxxxx-235-864', NULL, 12.14, 20.00, 1, 1, '2026-06-04 15:44:14', '2026-06-07 19:27:47');
INSERT INTO `product_variants` VALUES (10, 2, '', '240', 'xxxxxx-240-750', NULL, 12.14, 20.00, 1, 1, '2026-06-04 15:44:14', '2026-06-07 19:27:47');
INSERT INTO `product_variants` VALUES (11, 3, '', '230', 'xxxxxx-230-537', NULL, 17.13, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (12, 3, '', '235', 'xxxxxx-235-201', NULL, 17.13, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (13, 3, '', '240', 'xxxxxx-240-658', NULL, 17.13, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (14, 3, '', '245', 'xxxxxx-245-312', NULL, 17.13, 25.00, 0, 1, '2026-06-04 15:44:14', '2026-06-05 03:55:50');
INSERT INTO `product_variants` VALUES (15, 3, '', '250', 'xxxxxx-250-451', NULL, 17.13, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (16, 4, 'BM', '230', 'bnbnb-230-521', NULL, 22.84, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (17, 4, 'BM', '235', 'bnbnb-235-987', NULL, 22.84, 30.00, 0, 1, '2026-06-04 15:44:14', '2026-06-05 03:55:53');
INSERT INTO `product_variants` VALUES (18, 4, 'BM', '240', 'bnbnb-240-726', NULL, 22.84, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (19, 4, 'BM', '245', 'bnbnb-245-950', NULL, 22.84, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (20, 4, 'BM', '250', 'bnbnb-250-784', NULL, 22.84, 0.00, 0, 1, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `product_variants` VALUES (21, 11, '', '225', 'xx-225-171', NULL, 17.14, 0.00, 0, 1, '2026-06-07 19:14:06', '2026-06-07 19:14:06');
INSERT INTO `product_variants` VALUES (22, 3, '', '220', 'xxxxxx-220-463', NULL, 14.29, 0.00, 2, 1, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `product_variants` VALUES (23, 3, '', '230', 'xxxxxx-230-884', NULL, 14.29, 0.00, 2, 1, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `product_variants` VALUES (24, 3, '', '235', 'xxxxxx-235-539', NULL, 14.29, 0.00, 0, 1, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `product_variants` VALUES (25, 12, '', 'L', 'XXXX-L-587', NULL, 28.57, 0.00, 1, 1, '2026-06-08 02:18:04', '2026-06-08 02:18:04');
INSERT INTO `product_variants` VALUES (26, 13, '01', '220', '1010101-220-367', NULL, 15.00, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (27, 13, '01', '225', '1010101-225-555', NULL, 15.00, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (28, 13, '01', '230', '1010101-230-954', NULL, 15.00, 0.00, 0, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (29, 13, '01', '235', '1010101-235-496', NULL, 15.00, 0.00, 0, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (30, 13, '01', '240', '1010101-240-102', NULL, 15.00, 0.00, 0, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (31, 14, '', '220', '1111-220-569', NULL, 16.43, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (32, 14, '', '225', '1111-225-538', NULL, 16.43, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (33, 14, '', '230', '1111-230-611', NULL, 16.43, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (34, 14, '', '235', '1111-235-874', NULL, 16.43, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `product_variants` VALUES (35, 14, '', '240', '1111-240-720', NULL, 16.43, 0.00, 1, 1, '2026-06-08 02:19:35', '2026-06-08 02:19:35');

-- ----------------------------
-- Table structure for products
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `serial_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `category_id` int UNSIGNED NOT NULL,
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `department` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `gender` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `season` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `reference_cost` decimal(10, 2) NULL DEFAULT NULL,
  `reference_currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `cost` decimal(10, 2) NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_products_name`(`name` ASC) USING BTREE,
  INDEX `idx_products_category_id`(`category_id` ASC) USING BTREE,
  CONSTRAINT `fk_products_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of products
-- ----------------------------
INSERT INTO `products` VALUES (1, 'Women Sandals (Summer)', 'L23313', 11, 'Nike', 'footwear', 'women', 'summer', NULL, 100.00, 'RMB', NULL, 1, '2026-06-04 15:41:32', '2026-06-04 15:44:13');
INSERT INTO `products` VALUES (2, 'Women Sneakers (Summer)', 'xxxxxx', 3, 'xxx', 'footwear', 'women', 'summer', NULL, 85.00, 'RMB', NULL, 1, '2026-06-04 15:41:58', '2026-06-07 19:27:47');
INSERT INTO `products` VALUES (3, 'Men Casual Shoes (Summer)', 'xxxxxx', 12, 'nnnn', 'footwear', 'men', 'summer', NULL, 100.00, 'RMB', NULL, 1, '2026-06-04 15:42:28', '2026-06-07 20:44:55');
INSERT INTO `products` VALUES (4, 'Men Sneakers (Summer)', 'bnbnb', 3, 'bn', 'footwear', 'men', 'summer', NULL, 160.00, 'RMB', NULL, 1, '2026-06-04 15:43:05', '2026-06-04 15:44:14');
INSERT INTO `products` VALUES (5, 'Women Sandals (Spring)', '2212121', 11, '2121', 'footwear', 'women', 'spring', NULL, 150.00, 'RMB', NULL, 1, '2026-06-07 18:21:35', '2026-06-07 18:21:35');
INSERT INTO `products` VALUES (6, 'Women Sandals (Spring)', 'ccccc', 11, NULL, 'footwear', 'women', 'spring', NULL, 120.00, 'RMB', NULL, 1, '2026-06-07 18:31:08', '2026-06-07 18:31:08');
INSERT INTO `products` VALUES (7, 'Men Casual Shoes (Summer)', '123123', 12, '123', 'footwear', 'men', 'summer', NULL, 120.00, 'RMB', NULL, 1, '2026-06-07 18:50:19', '2026-06-07 18:50:19');
INSERT INTO `products` VALUES (8, 'Men Casual Shoes (Summer)', '234234', 12, '2342', 'footwear', 'men', 'summer', NULL, 0.00, 'RMB', NULL, 1, '2026-06-07 18:50:38', '2026-06-07 18:50:38');
INSERT INTO `products` VALUES (9, 'Men Casual Shoes (Summer)', '123', 12, '12', 'footwear', 'men', 'summer', NULL, 120.00, 'RMB', NULL, 1, '2026-06-07 18:50:53', '2026-06-07 18:50:53');
INSERT INTO `products` VALUES (10, 'Women Casual Shoes (Spring)', 'sdfsdf', 12, NULL, 'footwear', 'women', 'spring', NULL, 80.00, 'RMB', NULL, 1, '2026-06-07 18:58:27', '2026-06-07 18:58:27');
INSERT INTO `products` VALUES (11, 'Men Shorts (Spring)', 'xx', 8, 'xx', 'apparel', 'men', 'spring', NULL, 120.00, 'RMB', NULL, 1, '2026-06-07 19:14:02', '2026-06-07 19:14:06');
INSERT INTO `products` VALUES (12, 'Men Shorts (Summer)', 'XXXX', 8, NULL, 'apparel', 'men', 'summer', NULL, 200.00, 'RMB', NULL, 1, '2026-06-08 02:17:53', '2026-06-08 02:18:04');
INSERT INTO `products` VALUES (13, 'Men Sandals (Summer)', '1010101', 11, '101', 'footwear', 'men', 'summer', NULL, 105.00, 'RMB', NULL, 1, '2026-06-08 02:19:10', '2026-06-08 02:19:35');
INSERT INTO `products` VALUES (14, 'Men Sandals (Summer)', '1111', 11, '111', 'footwear', 'men', 'summer', NULL, 115.00, 'RMB', NULL, 1, '2026-06-08 02:19:24', '2026-06-08 02:19:35');

-- ----------------------------
-- Table structure for purchase_items
-- ----------------------------
DROP TABLE IF EXISTS `purchase_items`;
CREATE TABLE `purchase_items`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `unit_price` decimal(12, 2) NULL DEFAULT NULL,
  `allocated_discount` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `allocated_shipping` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `allocated_transfer_fee` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12, 2) NOT NULL,
  `line_total` decimal(12, 2) NOT NULL,
  `reference_cost` decimal(10, 2) NULL DEFAULT NULL,
  `reference_currency` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `exchange_rate` decimal(10, 2) NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_purchase_items_purchase_id`(`purchase_id` ASC) USING BTREE,
  INDEX `idx_purchase_items_product_variant_id`(`product_variant_id` ASC) USING BTREE,
  CONSTRAINT `fk_purchase_items_product_variant_id` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_items_purchase_id` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 41 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of purchase_items
-- ----------------------------
INSERT INTO `purchase_items` VALUES (1, 1, 1, 1, 13.70, -0.03, 0.55, 0.00, 0.00, 14.27, 14.27, 100.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (2, 1, 2, 1, 13.70, -0.03, 0.55, 0.00, 0.00, 14.27, 14.27, 100.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (3, 1, 3, 1, 13.70, -0.03, 0.55, 0.00, 0.00, 14.27, 14.27, 100.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (4, 1, 4, 1, 13.70, -0.03, 0.55, 0.00, 0.00, 14.27, 14.27, 100.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (5, 1, 5, 1, 13.70, -0.03, 0.55, 0.00, 0.00, 14.27, 14.27, 100.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (6, 1, 6, 1, 13.01, -0.03, 0.52, 0.00, 0.00, 13.56, 13.56, 95.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (7, 1, 7, 1, 13.01, -0.03, 0.52, 0.00, 0.00, 13.56, 13.56, 95.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (8, 1, 8, 1, 13.01, -0.03, 0.52, 0.00, 0.00, 13.56, 13.56, 95.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (9, 1, 9, 1, 13.01, -0.03, 0.52, 0.00, 0.00, 13.56, 13.56, 95.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (10, 1, 10, 1, 13.01, -0.03, 0.52, 0.00, 0.00, 13.56, 13.56, 95.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (11, 1, 11, 1, 16.44, -0.03, 0.66, 0.00, 0.00, 17.13, 17.13, 120.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (12, 1, 12, 1, 16.44, -0.03, 0.66, 0.00, 0.00, 17.13, 17.13, 120.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (13, 1, 13, 1, 16.44, -0.03, 0.66, 0.00, 0.00, 17.13, 17.13, 120.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (14, 1, 14, 1, 16.44, -0.03, 0.66, 0.00, 0.00, 17.13, 17.13, 120.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (15, 1, 15, 1, 16.44, -0.03, 0.66, 0.00, 0.00, 17.13, 17.13, 120.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (16, 1, 16, 1, 21.92, -0.04, 0.88, 0.00, 0.00, 22.84, 22.84, 160.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (17, 1, 17, 1, 21.92, -0.04, 0.88, 0.00, 0.00, 22.84, 22.84, 160.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (18, 1, 18, 1, 21.92, -0.04, 0.88, 0.00, 0.00, 22.84, 22.84, 160.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (19, 1, 19, 1, 21.92, -0.04, 0.88, 0.00, 0.00, 22.84, 22.84, 160.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (20, 1, 20, 1, 21.92, -0.04, 0.88, 0.00, 0.00, 22.84, 22.84, 160.00, 'RMB', 7.30, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_items` VALUES (21, 2, 21, 1, 17.14, 0.00, 0.00, 0.00, 0.00, 17.14, 17.14, 120.00, 'RMB', 7.00, '2026-06-07 19:14:06', '2026-06-07 19:14:06');
INSERT INTO `purchase_items` VALUES (22, 3, 6, 1, 12.14, 0.00, 0.00, 0.00, 0.00, 12.14, 12.14, 85.00, 'RMB', 7.00, '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `purchase_items` VALUES (23, 3, 7, 1, 12.14, 0.00, 0.00, 0.00, 0.00, 12.14, 12.14, 85.00, 'RMB', 7.00, '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `purchase_items` VALUES (24, 3, 8, 1, 12.14, 0.00, 0.00, 0.00, 0.00, 12.14, 12.14, 85.00, 'RMB', 7.00, '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `purchase_items` VALUES (25, 3, 9, 1, 12.14, 0.00, 0.00, 0.00, 0.00, 12.14, 12.14, 85.00, 'RMB', 7.00, '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `purchase_items` VALUES (26, 3, 10, 1, 12.14, 0.00, 0.00, 0.00, 0.00, 12.14, 12.14, 85.00, 'RMB', 7.00, '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `purchase_items` VALUES (27, 4, 22, 2, 14.29, 0.00, 0.00, 0.00, 0.00, 14.29, 28.58, 100.00, 'RMB', 7.00, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `purchase_items` VALUES (28, 4, 23, 3, 14.29, 0.00, 0.00, 0.00, 0.00, 14.29, 42.87, 100.00, 'RMB', 7.00, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `purchase_items` VALUES (29, 4, 24, 1, 14.29, 0.00, 0.00, 0.00, 0.00, 14.29, 14.29, 100.00, 'RMB', 7.00, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `purchase_items` VALUES (30, 5, 25, 1, 28.57, 0.00, 0.00, 0.00, 0.00, 28.57, 28.57, 200.00, 'RMB', 7.00, '2026-06-08 02:18:04', '2026-06-08 02:18:04');
INSERT INTO `purchase_items` VALUES (31, 6, 26, 1, 15.00, 0.00, 0.00, 0.00, 0.00, 15.00, 15.00, 105.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (32, 6, 27, 1, 15.00, 0.00, 0.00, 0.00, 0.00, 15.00, 15.00, 105.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (33, 6, 28, 1, 15.00, 0.00, 0.00, 0.00, 0.00, 15.00, 15.00, 105.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (34, 6, 29, 1, 15.00, 0.00, 0.00, 0.00, 0.00, 15.00, 15.00, 105.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (35, 6, 30, 1, 15.00, 0.00, 0.00, 0.00, 0.00, 15.00, 15.00, 105.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (36, 6, 31, 1, 16.43, 0.00, 0.00, 0.00, 0.00, 16.43, 16.43, 115.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (37, 6, 32, 1, 16.43, 0.00, 0.00, 0.00, 0.00, 16.43, 16.43, 115.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (38, 6, 33, 1, 16.43, 0.00, 0.00, 0.00, 0.00, 16.43, 16.43, 115.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (39, 6, 34, 1, 16.43, 0.00, 0.00, 0.00, 0.00, 16.43, 16.43, 115.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `purchase_items` VALUES (40, 6, 35, 1, 16.43, 0.00, 0.00, 0.00, 0.00, 16.43, 16.43, 115.00, 'RMB', 7.00, '2026-06-08 02:19:35', '2026-06-08 02:19:35');

-- ----------------------------
-- Table structure for purchase_payments
-- ----------------------------
DROP TABLE IF EXISTS `purchase_payments`;
CREATE TABLE `purchase_payments`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_purchase_payments_purchase_id`(`purchase_id` ASC) USING BTREE,
  CONSTRAINT `fk_purchase_payments_purchase_id` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of purchase_payments
-- ----------------------------
INSERT INTO `purchase_payments` VALUES (1, 1, 'cash_usd', 300.00, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_payments` VALUES (2, 1, 'bank_usd', 39.00, '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `purchase_payments` VALUES (3, 2, 'bank_usd', 17.14, '2026-06-07 19:14:06', '2026-06-07 19:14:06');
INSERT INTO `purchase_payments` VALUES (4, 3, 'bank_usd', 60.70, '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `purchase_payments` VALUES (5, 4, 'bank_usd', 85.74, '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `purchase_payments` VALUES (6, 5, 'bank_usd', 28.57, '2026-06-08 02:18:04', '2026-06-08 02:18:04');
INSERT INTO `purchase_payments` VALUES (7, 6, 'bank_usd', 157.15, '2026-06-08 02:19:35', '2026-06-08 02:19:35');

-- ----------------------------
-- Table structure for purchases
-- ----------------------------
DROP TABLE IF EXISTS `purchases`;
CREATE TABLE `purchases`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `purchase_date` datetime NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'draft',
  `sub_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `transfer_fee` decimal(12, 2) NULL DEFAULT 0.00,
  `shipping_fee` decimal(12, 2) NULL DEFAULT NULL,
  `grand_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `paid_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `payment_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_purchases_purchase_no`(`purchase_no` ASC) USING BTREE,
  INDEX `idx_purchases_purchase_date`(`purchase_date` ASC) USING BTREE,
  INDEX `idx_purchases_supplier_id`(`supplier_id` ASC) USING BTREE,
  CONSTRAINT `fk_purchases_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of purchases
-- ----------------------------
INSERT INTO `purchases` VALUES (1, 'PO-20260604-154414', '2026-06-05 00:00:00', 4, 'received', 339.00, -0.65, 0.00, 13.00, 339.00, 326.00, NULL, '2026-06-04 15:44:14', '2026-06-04 15:44:14', 'cash_usd');
INSERT INTO `purchases` VALUES (2, 'PO-20260607-191406', '2026-06-08 00:00:00', 2, 'received', 17.14, 0.00, 0.00, 0.00, 17.14, 17.14, NULL, '2026-06-07 19:14:06', '2026-06-07 19:14:06', 'bank_usd');
INSERT INTO `purchases` VALUES (3, 'PO-20260607-192747', '2026-06-08 00:00:00', 4, 'received', 60.70, 0.00, 0.00, 0.00, 60.70, 60.70, NULL, '2026-06-07 19:27:47', '2026-06-07 19:27:47', 'bank_usd');
INSERT INTO `purchases` VALUES (4, 'PO-20260607-204455', '2026-06-08 00:00:00', 3, 'received', 85.74, 0.00, 0.00, 0.00, 85.74, 85.74, NULL, '2026-06-07 20:44:55', '2026-06-07 20:44:55', 'bank_usd');
INSERT INTO `purchases` VALUES (5, 'PO-20260608-021804', '2026-06-08 00:00:00', 3, 'received', 28.57, 0.00, 0.00, 0.00, 28.57, 28.57, NULL, '2026-06-08 02:18:04', '2026-06-08 02:18:04', 'bank_usd');
INSERT INTO `purchases` VALUES (6, 'PO-20260608-021935', '2026-06-08 00:00:00', 3, 'received', 157.15, 0.00, 0.00, 0.00, 157.15, 157.15, NULL, '2026-06-08 02:19:35', '2026-06-08 02:19:35', 'bank_usd');

-- ----------------------------
-- Table structure for sale_items
-- ----------------------------
DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `unit_price` decimal(12, 2) NOT NULL,
  `discount_amount` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12, 2) NOT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sale_items_sale_id`(`sale_id` ASC) USING BTREE,
  INDEX `idx_sale_items_product_variant_id`(`product_variant_id` ASC) USING BTREE,
  CONSTRAINT `fk_sale_items_product_variant_id` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sale_items_sale_id` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 53 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sale_items
-- ----------------------------
INSERT INTO `sale_items` VALUES (30, 10, 13, 1, 25.00, 0.00, 25.00, '2026-06-04 15:47:11', '2026-06-04 15:47:11');
INSERT INTO `sale_items` VALUES (31, 11, 19, 1, 28.00, 0.00, 28.00, '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `sale_items` VALUES (32, 11, 11, 1, 24.00, 0.00, 24.00, '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `sale_items` VALUES (33, 11, 12, 1, 24.00, 0.00, 24.00, '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `sale_items` VALUES (34, 12, 20, 1, 30.00, 0.00, 30.00, '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `sale_items` VALUES (35, 12, 16, 1, 30.00, 0.00, 30.00, '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `sale_items` VALUES (36, 12, 4, 1, 22.00, 2.00, 20.00, '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `sale_items` VALUES (37, 13, 15, 1, 20.00, 2.22, 17.78, '2026-06-05 01:14:44', '2026-06-05 01:14:44');
INSERT INTO `sale_items` VALUES (38, 13, 18, 1, 25.00, 2.78, 22.22, '2026-06-05 01:14:44', '2026-06-05 01:14:44');
INSERT INTO `sale_items` VALUES (39, 14, 17, 1, 30.00, 0.00, 30.00, '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `sale_items` VALUES (40, 14, 1, 1, 20.00, 0.00, 20.00, '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `sale_items` VALUES (41, 14, 9, 1, 19.00, 0.00, 19.00, '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `sale_items` VALUES (42, 15, 14, 1, 25.00, 0.00, 25.00, '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `sale_items` VALUES (43, 15, 3, 1, 20.00, 0.00, 20.00, '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `sale_items` VALUES (44, 15, 8, 1, 20.00, 0.00, 20.00, '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `sale_items` VALUES (45, 16, 21, 1, 30.00, 0.00, 30.00, '2026-06-07 19:14:38', '2026-06-07 19:14:38');
INSERT INTO `sale_items` VALUES (46, 17, 23, 1, 20.00, 0.00, 20.00, '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `sale_items` VALUES (47, 17, 6, 1, 20.00, 0.00, 20.00, '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `sale_items` VALUES (48, 17, 10, 1, 20.00, 0.00, 20.00, '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `sale_items` VALUES (49, 18, 24, 1, 20.00, 0.00, 20.00, '2026-06-08 02:20:00', '2026-06-08 02:20:00');
INSERT INTO `sale_items` VALUES (50, 18, 28, 1, 20.00, 0.00, 20.00, '2026-06-08 02:20:00', '2026-06-08 02:20:00');
INSERT INTO `sale_items` VALUES (51, 18, 30, 1, 20.00, 0.00, 20.00, '2026-06-08 02:20:00', '2026-06-08 02:20:00');
INSERT INTO `sale_items` VALUES (52, 18, 29, 1, 20.00, 0.00, 20.00, '2026-06-08 02:20:00', '2026-06-08 02:20:00');

-- ----------------------------
-- Table structure for sale_payments
-- ----------------------------
DROP TABLE IF EXISTS `sale_payments`;
CREATE TABLE `sale_payments`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` bigint UNSIGNED NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sale_payments_sale_id`(`sale_id` ASC) USING BTREE,
  CONSTRAINT `fk_sale_payments_sale_id` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sale_payments
-- ----------------------------
INSERT INTO `sale_payments` VALUES (10, 10, 'bank_usd', 20.00, '2026-06-05 00:00:00', '2026-06-04 15:47:11');
INSERT INTO `sale_payments` VALUES (11, 10, 'bank_usd', 5.00, '2026-06-05 00:00:00', '2026-06-04 15:48:11');
INSERT INTO `sale_payments` VALUES (12, 11, 'bank_usd', 76.00, '2026-06-05 00:00:00', '2026-06-04 16:02:02');
INSERT INTO `sale_payments` VALUES (13, 12, 'bank_usd', 80.00, '2026-06-02 00:00:00', '2026-06-04 16:16:28');
INSERT INTO `sale_payments` VALUES (14, 13, 'bank_usd', 40.00, '2026-06-05 00:00:00', '2026-06-05 01:14:44');
INSERT INTO `sale_payments` VALUES (15, 14, 'bank_usd', 69.00, '2026-06-05 00:00:00', '2026-06-05 03:56:45');
INSERT INTO `sale_payments` VALUES (16, 15, 'cash_usd', 65.00, '2026-06-08 00:00:00', '2026-06-07 19:06:30');
INSERT INTO `sale_payments` VALUES (17, 16, 'bank_usd', 30.00, '2026-06-08 00:00:00', '2026-06-07 19:14:38');
INSERT INTO `sale_payments` VALUES (18, 17, 'cash_usd', 20.00, '2026-06-08 00:00:00', '2026-06-08 02:11:35');
INSERT INTO `sale_payments` VALUES (19, 18, 'bank_usd', 80.00, '2026-06-02 00:00:00', '2026-06-08 02:20:00');

-- ----------------------------
-- Table structure for sales
-- ----------------------------
DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sale_date` datetime NOT NULL,
  `customer_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `warehouse_id` bigint UNSIGNED NULL DEFAULT NULL,
  `sub_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `paid_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `unpaid_total` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'cash',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'completed',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_sales_sale_no`(`sale_no` ASC) USING BTREE,
  INDEX `idx_sales_sale_date`(`sale_date` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sales
-- ----------------------------
INSERT INTO `sales` VALUES (10, 'SO-20260604-154711', '2026-06-05 00:00:00', NULL, 1, 25.00, 0.00, 25.00, 25.00, 0.00, 'bank_usd', 'completed', '2026-06-04 15:47:11', '2026-06-04 15:48:11');
INSERT INTO `sales` VALUES (11, 'SO-20260604-160202', '2026-06-05 00:00:00', NULL, 2, 76.00, 0.00, 76.00, 76.00, 0.00, 'bank_usd', 'completed', '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `sales` VALUES (12, 'SO-20260604-161628', '2026-06-02 00:00:00', NULL, 2, 82.00, 2.00, 80.00, 80.00, 0.00, 'bank_usd', 'completed', '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `sales` VALUES (13, 'SO-20260605-011444', '2026-06-05 00:00:00', NULL, 1, 45.00, 5.00, 40.00, 40.00, 0.00, 'bank_usd', 'completed', '2026-06-05 01:14:44', '2026-06-05 01:14:44');
INSERT INTO `sales` VALUES (14, 'SO-20260605-035645', '2026-06-05 00:00:00', NULL, 2, 69.00, 0.00, 69.00, 69.00, 0.00, 'bank_usd', 'completed', '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `sales` VALUES (15, 'SO-20260607-190630', '2026-06-08 00:00:00', NULL, 2, 65.00, 0.00, 65.00, 65.00, 0.00, 'cash_usd', 'completed', '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `sales` VALUES (16, 'SO-20260607-191438', '2026-06-08 00:00:00', NULL, 2, 30.00, 0.00, 30.00, 30.00, 0.00, 'bank_usd', 'completed', '2026-06-07 19:14:38', '2026-06-07 19:14:38');
INSERT INTO `sales` VALUES (17, 'SO-20260608-021135', '2026-06-08 00:00:00', NULL, 2, 60.00, 0.00, 60.00, 20.00, 40.00, 'cash_usd', 'incomplete', '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `sales` VALUES (18, 'SO-20260608-022000', '2026-06-02 00:00:00', NULL, 2, 80.00, 0.00, 80.00, 80.00, 0.00, 'bank_usd', 'completed', '2026-06-08 02:20:00', '2026-06-08 02:20:00');

-- ----------------------------
-- Table structure for stock_movements
-- ----------------------------
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `movement_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `qty_change` int NOT NULL,
  `reference_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `reference_id` bigint UNSIGNED NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_stock_movements_product_variant_id`(`product_variant_id` ASC) USING BTREE,
  INDEX `idx_stock_movements_movement_type`(`movement_type` ASC) USING BTREE,
  INDEX `idx_stock_movements_created_at`(`created_at` ASC) USING BTREE,
  CONSTRAINT `fk_stock_movements_product_variant_id` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 74 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of stock_movements
-- ----------------------------
INSERT INTO `stock_movements` VALUES (1, 1, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (2, 2, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (3, 3, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (4, 4, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (5, 5, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (6, 6, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (7, 7, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (8, 8, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (9, 9, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (10, 10, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (11, 11, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (12, 12, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (13, 13, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (14, 14, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (15, 15, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (16, 16, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (17, 17, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (18, 18, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (19, 19, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (20, 20, 'purchase', 1, 'purchase', 1, 'Stock received from purchase.', '2026-06-04 15:44:14', '2026-06-04 15:44:14');
INSERT INTO `stock_movements` VALUES (21, 13, 'sale', -1, 'sale', 10, 'Stock deducted from sale.', '2026-06-04 15:47:11', '2026-06-04 15:47:11');
INSERT INTO `stock_movements` VALUES (22, 19, 'sale', -1, 'sale', 11, 'Stock deducted from sale.', '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `stock_movements` VALUES (23, 11, 'sale', -1, 'sale', 11, 'Stock deducted from sale.', '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `stock_movements` VALUES (24, 12, 'sale', -1, 'sale', 11, 'Stock deducted from sale.', '2026-06-04 16:02:02', '2026-06-04 16:02:02');
INSERT INTO `stock_movements` VALUES (25, 20, 'sale', -1, 'sale', 12, 'Stock deducted from sale.', '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `stock_movements` VALUES (26, 16, 'sale', -1, 'sale', 12, 'Stock deducted from sale.', '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `stock_movements` VALUES (27, 4, 'sale', -1, 'sale', 12, 'Stock deducted from sale.', '2026-06-04 16:16:28', '2026-06-04 16:16:28');
INSERT INTO `stock_movements` VALUES (28, 15, 'sale', -1, 'sale', 13, 'Stock deducted from sale.', '2026-06-05 01:14:44', '2026-06-05 01:14:44');
INSERT INTO `stock_movements` VALUES (29, 18, 'sale', -1, 'sale', 13, 'Stock deducted from sale.', '2026-06-05 01:14:44', '2026-06-05 01:14:44');
INSERT INTO `stock_movements` VALUES (30, 17, 'sale', -1, 'sale', 14, 'Stock deducted from sale.', '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `stock_movements` VALUES (31, 1, 'sale', -1, 'sale', 14, 'Stock deducted from sale.', '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `stock_movements` VALUES (32, 9, 'sale', -1, 'sale', 14, 'Stock deducted from sale.', '2026-06-05 03:56:45', '2026-06-05 03:56:45');
INSERT INTO `stock_movements` VALUES (33, 14, 'sale', -1, 'sale', 15, 'Stock deducted from sale.', '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `stock_movements` VALUES (34, 3, 'sale', -1, 'sale', 15, 'Stock deducted from sale.', '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `stock_movements` VALUES (35, 8, 'sale', -1, 'sale', 15, 'Stock deducted from sale.', '2026-06-07 19:06:30', '2026-06-07 19:06:30');
INSERT INTO `stock_movements` VALUES (36, 21, 'purchase', 1, 'purchase', 2, 'Stock received from purchase.', '2026-06-07 19:14:06', '2026-06-07 19:14:06');
INSERT INTO `stock_movements` VALUES (37, 21, 'sale', -1, 'sale', 16, 'Stock deducted from sale.', '2026-06-07 19:14:38', '2026-06-07 19:14:38');
INSERT INTO `stock_movements` VALUES (38, 6, 'purchase', 1, 'purchase', 3, 'Stock received from purchase.', '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `stock_movements` VALUES (39, 7, 'purchase', 1, 'purchase', 3, 'Stock received from purchase.', '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `stock_movements` VALUES (40, 8, 'purchase', 1, 'purchase', 3, 'Stock received from purchase.', '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `stock_movements` VALUES (41, 9, 'purchase', 1, 'purchase', 3, 'Stock received from purchase.', '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `stock_movements` VALUES (42, 10, 'purchase', 1, 'purchase', 3, 'Stock received from purchase.', '2026-06-07 19:27:47', '2026-06-07 19:27:47');
INSERT INTO `stock_movements` VALUES (43, 22, 'purchase', 2, 'purchase', 4, 'Stock received from purchase.', '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `stock_movements` VALUES (44, 23, 'purchase', 3, 'purchase', 4, 'Stock received from purchase.', '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `stock_movements` VALUES (45, 24, 'purchase', 1, 'purchase', 4, 'Stock received from purchase.', '2026-06-07 20:44:55', '2026-06-07 20:44:55');
INSERT INTO `stock_movements` VALUES (46, 23, 'transfer_out', -1, 'transfer', 1, 'Stock transferred out from warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (47, 23, 'transfer_in', 1, 'transfer', 1, 'Stock transferred in to warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (48, 2, 'transfer_out', -1, 'transfer', 1, 'Stock transferred out from warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (49, 2, 'transfer_in', 1, 'transfer', 1, 'Stock transferred in to warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (50, 6, 'transfer_out', -1, 'transfer', 1, 'Stock transferred out from warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (51, 6, 'transfer_in', 1, 'transfer', 1, 'Stock transferred in to warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (52, 7, 'transfer_out', -1, 'transfer', 1, 'Stock transferred out from warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (53, 7, 'transfer_in', 1, 'transfer', 1, 'Stock transferred in to warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (54, 5, 'transfer_out', -1, 'transfer', 1, 'Stock transferred out from warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (55, 5, 'transfer_in', 1, 'transfer', 1, 'Stock transferred in to warehouse.', '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `stock_movements` VALUES (56, 23, 'sale', -1, 'sale', 17, 'Stock deducted from sale.', '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `stock_movements` VALUES (57, 6, 'sale', -1, 'sale', 17, 'Stock deducted from sale.', '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `stock_movements` VALUES (58, 10, 'sale', -1, 'sale', 17, 'Stock deducted from sale.', '2026-06-08 02:11:35', '2026-06-08 02:11:35');
INSERT INTO `stock_movements` VALUES (59, 25, 'purchase', 1, 'purchase', 5, 'Stock received from purchase.', '2026-06-08 02:18:04', '2026-06-08 02:18:04');
INSERT INTO `stock_movements` VALUES (60, 26, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (61, 27, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (62, 28, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (63, 29, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (64, 30, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (65, 31, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (66, 32, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (67, 33, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (68, 34, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (69, 35, 'purchase', 1, 'purchase', 6, 'Stock received from purchase.', '2026-06-08 02:19:35', '2026-06-08 02:19:35');
INSERT INTO `stock_movements` VALUES (70, 24, 'sale', -1, 'sale', 18, 'Stock deducted from sale.', '2026-06-08 02:20:00', '2026-06-08 02:20:00');
INSERT INTO `stock_movements` VALUES (71, 28, 'sale', -1, 'sale', 18, 'Stock deducted from sale.', '2026-06-08 02:20:00', '2026-06-08 02:20:00');
INSERT INTO `stock_movements` VALUES (72, 30, 'sale', -1, 'sale', 18, 'Stock deducted from sale.', '2026-06-08 02:20:00', '2026-06-08 02:20:00');
INSERT INTO `stock_movements` VALUES (73, 29, 'sale', -1, 'sale', 18, 'Stock deducted from sale.', '2026-06-08 02:20:00', '2026-06-08 02:20:00');

-- ----------------------------
-- Table structure for suppliers
-- ----------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `default_currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_suppliers_name`(`name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of suppliers
-- ----------------------------
INSERT INTO `suppliers` VALUES (2, 'Company3', '', '', '', NULL, '2026-05-05 15:29:32', '2026-06-03 14:35:48');
INSERT INTO `suppliers` VALUES (3, 'Company 2', '', '', '', NULL, '2026-05-05 15:29:37', '2026-06-03 14:35:36');
INSERT INTO `suppliers` VALUES (4, 'Company1', '', '', '', 'USD', '2026-05-28 14:48:21', '2026-06-04 11:29:50');

-- ----------------------------
-- Table structure for taggings
-- ----------------------------
DROP TABLE IF EXISTS `taggings`;
CREATE TABLE `taggings`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_id` int UNSIGNED NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` bigint UNSIGNED NOT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `tag_id_entity_type_entity_id`(`tag_id` ASC, `entity_type` ASC, `entity_id` ASC) USING BTREE,
  INDEX `entity_type_entity_id`(`entity_type` ASC, `entity_id` ASC) USING BTREE,
  CONSTRAINT `taggings_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of taggings
-- ----------------------------

-- ----------------------------
-- Table structure for tags
-- ----------------------------
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name` ASC) USING BTREE,
  UNIQUE INDEX `slug`(`slug` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tags
-- ----------------------------
INSERT INTO `tags` VALUES (1, 'test', 'test', NULL, '2026-05-29 17:00:31', '2026-05-29 17:00:31');
INSERT INTO `tags` VALUES (2, 'asdfa', 'asdfa', NULL, '2026-05-29 17:00:35', '2026-05-29 17:00:35');
INSERT INTO `tags` VALUES (3, 'xxx', 'xxx', NULL, '2026-05-29 17:03:02', '2026-05-29 17:03:02');

-- ----------------------------
-- Table structure for transactions
-- ----------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions`  (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `account_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `reference_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  `debit` decimal(12, 2) NULL DEFAULT 0.00,
  `credit` decimal(12, 2) NULL DEFAULT 0.00,
  `original_amount` decimal(18, 8) NOT NULL DEFAULT 0.00000000,
  `currency` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(18, 8) NOT NULL DEFAULT 1.00000000,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 63 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of transactions
-- ----------------------------
INSERT INTO `transactions` VALUES (1, '2026-06-05', '1200', 'PO-20260604-154414', 'Purchase PO-20260604-154414', 326.00, 0.00, 326.00000000, 'USD', 1.00000000, '2026-06-04 15:44:14');
INSERT INTO `transactions` VALUES (2, '2026-06-05', '5110', 'PO-20260604-154414', 'Purchase PO-20260604-154414', 13.00, 0.00, 13.00000000, 'USD', 1.00000000, '2026-06-04 15:44:14');
INSERT INTO `transactions` VALUES (3, '2026-06-05', '1000', 'PO-20260604-154414', 'Purchase PO-20260604-154414', 0.00, 300.00, 300.00000000, 'USD', 1.00000000, '2026-06-04 15:44:14');
INSERT INTO `transactions` VALUES (4, '2026-06-05', '1010', 'PO-20260604-154414', 'Purchase PO-20260604-154414', 0.00, 39.00, 39.00000000, 'USD', 1.00000000, '2026-06-04 15:44:14');
INSERT INTO `transactions` VALUES (5, '2026-06-05', '4000', 'SO-20260604-154711', 'Sale SO-20260604-154711', 0.00, 25.00, 25.00000000, 'USD', 1.00000000, '2026-06-04 15:47:11');
INSERT INTO `transactions` VALUES (6, '2026-06-05', '1010', 'SO-20260604-154711', 'Sale SO-20260604-154711', 20.00, 0.00, 20.00000000, 'USD', 1.00000000, '2026-06-04 15:47:11');
INSERT INTO `transactions` VALUES (7, '2026-06-05', '1100', 'SO-20260604-154711', 'Sale SO-20260604-154711 (unpaid)', 5.00, 0.00, 5.00000000, 'USD', 1.00000000, '2026-06-04 15:47:11');
INSERT INTO `transactions` VALUES (8, '2026-06-05', '5000', 'SO-20260604-154711', 'COGS SO-20260604-154711', 17.13, 0.00, 17.13000000, 'USD', 1.00000000, '2026-06-04 15:47:11');
INSERT INTO `transactions` VALUES (9, '2026-06-05', '1210', 'SO-20260604-154711', 'COGS SO-20260604-154711', 0.00, 17.13, 17.13000000, 'USD', 1.00000000, '2026-06-04 15:47:11');
INSERT INTO `transactions` VALUES (10, '2026-06-05', '1010', 'SO-20260604-154711', 'Sale payment SO-20260604-154711', 5.00, 0.00, 5.00000000, 'USD', 1.00000000, '2026-06-04 15:48:11');
INSERT INTO `transactions` VALUES (11, '2026-06-05', '1100', 'SO-20260604-154711', 'Sale payment SO-20260604-154711', 0.00, 5.00, 5.00000000, 'USD', 1.00000000, '2026-06-04 15:48:11');
INSERT INTO `transactions` VALUES (12, '2026-06-05', '4000', 'SO-20260604-160202', 'Sale SO-20260604-160202', 0.00, 76.00, 76.00000000, 'USD', 1.00000000, '2026-06-04 16:02:02');
INSERT INTO `transactions` VALUES (13, '2026-06-05', '1010', 'SO-20260604-160202', 'Sale SO-20260604-160202', 76.00, 0.00, 76.00000000, 'USD', 1.00000000, '2026-06-04 16:02:02');
INSERT INTO `transactions` VALUES (14, '2026-06-05', '5000', 'SO-20260604-160202', 'COGS SO-20260604-160202', 57.10, 0.00, 57.10000000, 'USD', 1.00000000, '2026-06-04 16:02:02');
INSERT INTO `transactions` VALUES (15, '2026-06-05', '1210', 'SO-20260604-160202', 'COGS SO-20260604-160202', 0.00, 57.10, 57.10000000, 'USD', 1.00000000, '2026-06-04 16:02:02');
INSERT INTO `transactions` VALUES (16, '2026-06-02', '4000', 'SO-20260604-161628', 'Sale SO-20260604-161628', 0.00, 80.00, 80.00000000, 'USD', 1.00000000, '2026-06-04 16:16:28');
INSERT INTO `transactions` VALUES (17, '2026-06-02', '1010', 'SO-20260604-161628', 'Sale SO-20260604-161628', 80.00, 0.00, 80.00000000, 'USD', 1.00000000, '2026-06-04 16:16:28');
INSERT INTO `transactions` VALUES (18, '2026-06-02', '5000', 'SO-20260604-161628', 'COGS SO-20260604-161628', 59.95, 0.00, 59.95000000, 'USD', 1.00000000, '2026-06-04 16:16:28');
INSERT INTO `transactions` VALUES (19, '2026-06-02', '1210', 'SO-20260604-161628', 'COGS SO-20260604-161628', 0.00, 59.95, 59.95000000, 'USD', 1.00000000, '2026-06-04 16:16:28');
INSERT INTO `transactions` VALUES (20, '2026-06-01', '9000', 'JE-20260604-0001', 'Tax', 50.00, 0.00, 50.00000000, 'USD', 1.00000000, '2026-06-04 16:29:25');
INSERT INTO `transactions` VALUES (21, '2026-06-01', '1010', 'JE-20260604-0001', 'Tax', 0.00, 50.00, 50.00000000, 'USD', 1.00000000, '2026-06-04 16:29:25');
INSERT INTO `transactions` VALUES (22, '2026-06-05', '9000', 'JE-20260604-0002', 'tax another', 10.00, 0.00, 10.00000000, 'USD', 1.00000000, '2026-06-04 16:30:06');
INSERT INTO `transactions` VALUES (23, '2026-06-05', '1000', 'JE-20260604-0002', 'tax another', 0.00, 10.00, 10.00000000, 'USD', 1.00000000, '2026-06-04 16:30:06');
INSERT INTO `transactions` VALUES (24, '2026-06-05', '1000', 'SW-20260605-0001', 'Swap SW-20260605-0001', 100.00, 0.00, 100.00000000, 'USD', 1.00000000, '2026-06-05 00:44:46');
INSERT INTO `transactions` VALUES (25, '2026-06-05', '1010', 'SW-20260605-0001', 'Swap SW-20260605-0001', 0.00, 100.00, 100.00000000, 'USD', 1.00000000, '2026-06-05 00:44:46');
INSERT INTO `transactions` VALUES (26, '2026-06-05', '4000', 'SO-20260605-011444', 'Sale SO-20260605-011444', 0.00, 40.00, 40.00000000, 'USD', 1.00000000, '2026-06-05 01:14:44');
INSERT INTO `transactions` VALUES (27, '2026-06-05', '1010', 'SO-20260605-011444', 'Sale SO-20260605-011444', 40.00, 0.00, 40.00000000, 'USD', 1.00000000, '2026-06-05 01:14:44');
INSERT INTO `transactions` VALUES (28, '2026-06-05', '5000', 'SO-20260605-011444', 'COGS SO-20260605-011444', 39.97, 0.00, 39.97000000, 'USD', 1.00000000, '2026-06-05 01:14:44');
INSERT INTO `transactions` VALUES (29, '2026-06-05', '1210', 'SO-20260605-011444', 'COGS SO-20260605-011444', 0.00, 39.97, 39.97000000, 'USD', 1.00000000, '2026-06-05 01:14:44');
INSERT INTO `transactions` VALUES (30, '2026-06-05', '4000', 'SO-20260605-035645', 'Sale SO-20260605-035645', 0.00, 69.00, 69.00000000, 'USD', 1.00000000, '2026-06-05 03:56:45');
INSERT INTO `transactions` VALUES (31, '2026-06-05', '1010', 'SO-20260605-035645', 'Sale SO-20260605-035645', 69.00, 0.00, 69.00000000, 'USD', 1.00000000, '2026-06-05 03:56:45');
INSERT INTO `transactions` VALUES (32, '2026-06-05', '5000', 'SO-20260605-035645', 'COGS SO-20260605-035645', 50.67, 0.00, 50.67000000, 'USD', 1.00000000, '2026-06-05 03:56:45');
INSERT INTO `transactions` VALUES (33, '2026-06-05', '1210', 'SO-20260605-035645', 'COGS SO-20260605-035645', 0.00, 50.67, 50.67000000, 'USD', 1.00000000, '2026-06-05 03:56:45');
INSERT INTO `transactions` VALUES (34, '2026-06-08', '5110', 'JE-20260607-0001', '111', 10.00, 0.00, 10.00000000, 'USD', 1.00000000, '2026-06-07 19:04:22');
INSERT INTO `transactions` VALUES (35, '2026-06-08', '1000', 'JE-20260607-0001', '111', 0.00, 10.00, 10.00000000, 'USD', 1.00000000, '2026-06-07 19:04:22');
INSERT INTO `transactions` VALUES (36, '2026-06-08', '4000', 'SO-20260607-190630', 'Sale SO-20260607-190630', 0.00, 65.00, 65.00000000, 'USD', 1.00000000, '2026-06-07 19:06:30');
INSERT INTO `transactions` VALUES (37, '2026-06-08', '1000', 'SO-20260607-190630', 'Sale SO-20260607-190630', 65.00, 0.00, 65.00000000, 'USD', 1.00000000, '2026-06-07 19:06:30');
INSERT INTO `transactions` VALUES (38, '2026-06-08', '5000', 'SO-20260607-190630', 'COGS SO-20260607-190630', 44.96, 0.00, 44.96000000, 'USD', 1.00000000, '2026-06-07 19:06:30');
INSERT INTO `transactions` VALUES (39, '2026-06-08', '1210', 'SO-20260607-190630', 'COGS SO-20260607-190630', 0.00, 44.96, 44.96000000, 'USD', 1.00000000, '2026-06-07 19:06:30');
INSERT INTO `transactions` VALUES (40, '2026-06-08', '1200', 'PO-20260607-191406', 'Purchase PO-20260607-191406', 17.14, 0.00, 17.14000000, 'USD', 1.00000000, '2026-06-07 19:14:06');
INSERT INTO `transactions` VALUES (41, '2026-06-08', '1010', 'PO-20260607-191406', 'Purchase PO-20260607-191406', 0.00, 17.14, 17.14000000, 'USD', 1.00000000, '2026-06-07 19:14:06');
INSERT INTO `transactions` VALUES (42, '2026-06-08', '4000', 'SO-20260607-191438', 'Sale SO-20260607-191438', 0.00, 30.00, 30.00000000, 'USD', 1.00000000, '2026-06-07 19:14:38');
INSERT INTO `transactions` VALUES (43, '2026-06-08', '1010', 'SO-20260607-191438', 'Sale SO-20260607-191438', 30.00, 0.00, 30.00000000, 'USD', 1.00000000, '2026-06-07 19:14:38');
INSERT INTO `transactions` VALUES (44, '2026-06-08', '5000', 'SO-20260607-191438', 'COGS SO-20260607-191438', 17.14, 0.00, 17.14000000, 'USD', 1.00000000, '2026-06-07 19:14:38');
INSERT INTO `transactions` VALUES (45, '2026-06-08', '1210', 'SO-20260607-191438', 'COGS SO-20260607-191438', 0.00, 17.14, 17.14000000, 'USD', 1.00000000, '2026-06-07 19:14:38');
INSERT INTO `transactions` VALUES (46, '2026-06-08', '1200', 'PO-20260607-192747', 'Purchase PO-20260607-192747', 60.70, 0.00, 60.70000000, 'USD', 1.00000000, '2026-06-07 19:27:47');
INSERT INTO `transactions` VALUES (47, '2026-06-08', '1010', 'PO-20260607-192747', 'Purchase PO-20260607-192747', 0.00, 60.70, 60.70000000, 'USD', 1.00000000, '2026-06-07 19:27:47');
INSERT INTO `transactions` VALUES (48, '2026-06-08', '1200', 'PO-20260607-204455', 'Purchase PO-20260607-204455', 85.74, 0.00, 85.74000000, 'USD', 1.00000000, '2026-06-07 20:44:55');
INSERT INTO `transactions` VALUES (49, '2026-06-08', '1010', 'PO-20260607-204455', 'Purchase PO-20260607-204455', 0.00, 85.74, 85.74000000, 'USD', 1.00000000, '2026-06-07 20:44:55');
INSERT INTO `transactions` VALUES (50, '2026-06-08', '4000', 'SO-20260608-021135', 'Sale SO-20260608-021135', 0.00, 60.00, 60.00000000, 'USD', 1.00000000, '2026-06-08 02:11:35');
INSERT INTO `transactions` VALUES (51, '2026-06-08', '1000', 'SO-20260608-021135', 'Sale SO-20260608-021135', 20.00, 0.00, 20.00000000, 'USD', 1.00000000, '2026-06-08 02:11:35');
INSERT INTO `transactions` VALUES (52, '2026-06-08', '1100', 'SO-20260608-021135', 'Sale SO-20260608-021135 (unpaid)', 40.00, 0.00, 40.00000000, 'USD', 1.00000000, '2026-06-08 02:11:35');
INSERT INTO `transactions` VALUES (53, '2026-06-08', '5000', 'SO-20260608-021135', 'COGS SO-20260608-021135', 38.57, 0.00, 38.57000000, 'USD', 1.00000000, '2026-06-08 02:11:35');
INSERT INTO `transactions` VALUES (54, '2026-06-08', '1210', 'SO-20260608-021135', 'COGS SO-20260608-021135', 0.00, 38.57, 38.57000000, 'USD', 1.00000000, '2026-06-08 02:11:35');
INSERT INTO `transactions` VALUES (55, '2026-06-08', '1200', 'PO-20260608-021804', 'Purchase PO-20260608-021804', 28.57, 0.00, 28.57000000, 'USD', 1.00000000, '2026-06-08 02:18:04');
INSERT INTO `transactions` VALUES (56, '2026-06-08', '1010', 'PO-20260608-021804', 'Purchase PO-20260608-021804', 0.00, 28.57, 28.57000000, 'USD', 1.00000000, '2026-06-08 02:18:04');
INSERT INTO `transactions` VALUES (57, '2026-06-08', '1200', 'PO-20260608-021935', 'Purchase PO-20260608-021935', 157.15, 0.00, 157.15000000, 'USD', 1.00000000, '2026-06-08 02:19:35');
INSERT INTO `transactions` VALUES (58, '2026-06-08', '1010', 'PO-20260608-021935', 'Purchase PO-20260608-021935', 0.00, 157.15, 157.15000000, 'USD', 1.00000000, '2026-06-08 02:19:35');
INSERT INTO `transactions` VALUES (59, '2026-06-02', '4000', 'SO-20260608-022000', 'Sale SO-20260608-022000', 0.00, 80.00, 80.00000000, 'USD', 1.00000000, '2026-06-08 02:20:00');
INSERT INTO `transactions` VALUES (60, '2026-06-02', '1010', 'SO-20260608-022000', 'Sale SO-20260608-022000', 80.00, 0.00, 80.00000000, 'USD', 1.00000000, '2026-06-08 02:20:00');
INSERT INTO `transactions` VALUES (61, '2026-06-02', '5000', 'SO-20260608-022000', 'COGS SO-20260608-022000', 59.29, 0.00, 59.29000000, 'USD', 1.00000000, '2026-06-08 02:20:00');
INSERT INTO `transactions` VALUES (62, '2026-06-02', '1210', 'SO-20260608-022000', 'COGS SO-20260608-022000', 0.00, 59.29, 59.29000000, 'USD', 1.00000000, '2026-06-08 02:20:00');

-- ----------------------------
-- Table structure for transfer_items
-- ----------------------------
DROP TABLE IF EXISTS `transfer_items`;
CREATE TABLE `transfer_items`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `transfer_id`(`transfer_id` ASC) USING BTREE,
  INDEX `product_variant_id`(`product_variant_id` ASC) USING BTREE,
  CONSTRAINT `transfer_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `transfer_items_transfer_id_foreign` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of transfer_items
-- ----------------------------
INSERT INTO `transfer_items` VALUES (1, 1, 23, 1, '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `transfer_items` VALUES (2, 1, 2, 1, '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `transfer_items` VALUES (3, 1, 6, 1, '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `transfer_items` VALUES (4, 1, 7, 1, '2026-06-07 21:50:08', '2026-06-07 21:50:08');
INSERT INTO `transfer_items` VALUES (5, 1, 5, 1, '2026-06-07 21:50:08', '2026-06-07 21:50:08');

-- ----------------------------
-- Table structure for transfers
-- ----------------------------
DROP TABLE IF EXISTS `transfers`;
CREATE TABLE `transfers`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `transfer_date` datetime NOT NULL,
  `from_warehouse_id` int UNSIGNED NOT NULL,
  `to_warehouse_id` int UNSIGNED NOT NULL,
  `total_qty` int UNSIGNED NOT NULL DEFAULT 0,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `transfer_no`(`transfer_no` ASC) USING BTREE,
  INDEX `transfer_date`(`transfer_date` ASC) USING BTREE,
  INDEX `from_warehouse_id`(`from_warehouse_id` ASC) USING BTREE,
  INDEX `to_warehouse_id`(`to_warehouse_id` ASC) USING BTREE,
  CONSTRAINT `transfers_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `transfers_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of transfers
-- ----------------------------
INSERT INTO `transfers` VALUES (1, 'TR-20260607-215008-418', '2026-06-08 00:00:00', 1, 2, 5, NULL, '2026-06-07 21:50:08', '2026-06-07 21:50:08');

-- ----------------------------
-- Table structure for warehouses
-- ----------------------------
DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `can_store` tinyint(1) NULL DEFAULT 1,
  `can_sell` tinyint(1) NULL DEFAULT 1,
  `is_deleted` tinyint(1) NULL DEFAULT 0,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of warehouses
-- ----------------------------
INSERT INTO `warehouses` VALUES (1, 'Warehouse1', '', 1, 1, 0, '2026-05-28 14:55:27', '2026-05-28 14:55:27');
INSERT INTO `warehouses` VALUES (2, 'Warehouse2', 'ADDRESS', 1, 1, 0, '2026-05-28 14:55:38', '2026-05-28 14:55:38');

SET FOREIGN_KEY_CHECKS = 1;
