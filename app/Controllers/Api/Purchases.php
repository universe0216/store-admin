<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\PurchaseItemModel;
use App\Models\PurchaseModel;
use App\Models\StockMovementModel;
use App\Models\SupplierModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Purchases extends BaseController
{
    public function index(): ResponseInterface
    {
        $purchaseModel = new PurchaseModel();
        $rows          = $purchaseModel
            ->select('purchases.*, suppliers.name AS supplier_name')
            ->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left')
            ->orderBy('purchases.id', 'DESC')
            ->findAll(200);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $purchaseModel = new PurchaseModel();
        $purchase      = $purchaseModel
            ->select('purchases.*, suppliers.name AS supplier_name')
            ->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left')
            ->where('purchases.id', $id)
            ->first();

        if ($purchase === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Purchase not found.']);
        }

        $itemModel = new PurchaseItemModel();
        $items     = $itemModel
            ->select(
                "products.id AS product_id, products.name AS product_name, products.serial_number AS product_number, " .
                "products.brand AS brand, " .
                "GROUP_CONCAT(DISTINCT CASE WHEN variant_attributes.name <> 'Size' " .
                "THEN CONCAT(variant_attributes.name, ': ', variant_attribute_values.value) END " .
                "ORDER BY variant_attributes.name, variant_attribute_values.value SEPARATOR ', ') AS style, " .
                "purchase_items.unit_cost, " .
                "GROUP_CONCAT(DISTINCT CASE WHEN variant_attributes.name = 'Size' THEN variant_attribute_values.value END ORDER BY variant_attribute_values.value SEPARATOR ', ') AS size_value, " .
                "MAX(purchase_items.qty) AS sets_count, " .
                "SUM(purchase_items.qty) AS units_count, " .
                "SUM(purchase_items.line_total) AS total_price"
            )
            ->join('product_variants', 'product_variants.id = purchase_items.product_variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('product_variant_values', 'product_variant_values.product_variant_id = product_variants.id', 'left')
            ->join('variant_attributes', 'variant_attributes.id = product_variant_values.attribute_id', 'left')
            ->join('variant_attribute_values', 'variant_attribute_values.id = product_variant_values.attribute_value_id', 'left')
            ->where('purchase_items.purchase_id', $id)
            ->groupBy('products.id, products.name, products.serial_number, products.brand, purchase_items.unit_cost')
            ->findAll();

        return $this->response->setJSON([
            'data' => [
                'purchase' => $purchase,
                'items'    => $items,
            ],
        ]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $supplierId   = (int) ($payload['supplier_id'] ?? 0);
        $purchaseDate = (string) ($payload['purchase_date'] ?? '');
        $status       = 'received';
        $notes        = (string) ($payload['notes'] ?? '');
        $items        = $payload['items'] ?? [];

        if ($supplierId < 1 || $purchaseDate === '' || ! is_array($items) || $items === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'supplier_id, purchase_date and at least one item are required.',
            ]);
        }

        $purchaseModel       = new PurchaseModel();
        $purchaseItemModel   = new PurchaseItemModel();
        $productVariantModel = new ProductVariantModel();
        $stockMovementModel  = new StockMovementModel();
        $productModel        = new ProductModel();

        $subTotal      = 0.0;
        $discountTotal = 0.0;
        $normalized    = [];
        $db            = db_connect();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId      = (int) ($item['product_id'] ?? 0);
            $variantId      = (int) ($item['product_variant_id'] ?? 0);
            $setsCount      = (int) ($item['sets_count'] ?? 0);
            $qty            = (int) ($item['qty'] ?? 0);
            $unitCost       = (float) ($item['unit_cost'] ?? 0);
            $discountAmount = 0.0;
            $sizes          = $item['sizes'] ?? [];
            $warehouseId    = (int) ($item['warehouse_id'] ?? 0);

            if ($productId > 0 && is_array($sizes) && $sizes !== [] && $setsCount > 0) {
                $product = $productModel->find($productId);
                if (! is_array($product)) {
                    continue;
                }

                foreach ($sizes as $size) {
                    $sizeText = trim((string) $size);
                    if ($sizeText === '') {
                        continue;
                    }

                    $resolvedVariantId = $this->findOrCreateVariantBySize(
                        $db,
                        $productVariantModel,
                        $product,
                        $sizeText,
                        $unitCost
                    );
                    if ($resolvedVariantId < 1) {
                        continue;
                    }

                    $lineBase  = $setsCount * $unitCost;
                    $lineTotal = max($lineBase - $discountAmount, 0);

                    $subTotal      += $lineBase;
                    $discountTotal += $discountAmount;

                    $normalized[] = [
                        'product_variant_id' => $resolvedVariantId,
                        'qty'                => $setsCount,
                        'warehouse_id'       => $warehouseId,
                        'unit_cost'          => $unitCost,
                        'discount_amount'    => $discountAmount,
                        'line_total'         => $lineTotal,
                    ];
                }

                continue;
            }

            if ($variantId < 1 || $qty < 1 || $unitCost < 0) {
                continue;
            }

            $lineBase  = $qty * $unitCost;
            $lineTotal = max($lineBase - $discountAmount, 0);

            $subTotal      += $lineBase;
            $discountTotal += $discountAmount;

            $normalized[] = [
                'product_variant_id' => $variantId,
                'qty'                => $qty,
                'warehouse_id'       => $warehouseId,
                'unit_cost'          => $unitCost,
                'discount_amount'    => $discountAmount,
                'line_total'         => $lineTotal,
            ];
        }

        if ($normalized === []) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No valid purchase items.']);
        }

        $grandTotal = $subTotal - $discountTotal;
        $db->transBegin();

        try {
            $purchaseId = $purchaseModel->createOne([
                'purchase_no'    => $this->generatePurchaseNo(),
                'purchase_date'  => $purchaseDate,
                'supplier_id'    => $supplierId,
                'status'         => $status,
                'sub_total'      => $subTotal,
                'discount_total' => $discountTotal,
                'grand_total'    => $grandTotal,
                'paid_total'     => 0,
                'notes'          => $notes !== '' ? $notes : null,
            ]);

            foreach ($normalized as $item) {
                $purchaseItemModel->createOne([
                    'purchase_id'        => $purchaseId,
                    'product_variant_id' => $item['product_variant_id'],
                    'qty'                => $item['qty'],
                    'unit_cost'          => $item['unit_cost'],
                    'discount_amount'    => $item['discount_amount'],
                    'line_total'         => $item['line_total'],
                ]);

                $productVariantModel
                    ->set('stock_qty', 'stock_qty + ' . (int) $item['qty'], false)
                    ->set('cost_price', $item['unit_cost'])
                    ->where('id', $item['product_variant_id'])
                    ->update();

                $stockMovementModel->createOne([
                    'product_variant_id' => $item['product_variant_id'],
                    'movement_type'      => 'purchase',
                    'qty_change'         => $item['qty'],
                    'reference_type'     => 'purchase',
                    'reference_id'       => $purchaseId,
                    'notes'              => 'Stock received from purchase.',
                ]);

                if ((int) $item['warehouse_id'] > 0) {
                    $existingInventory = $db->table('inventory')
                        ->select('id')
                        ->where('variant_id', $item['product_variant_id'])
                        ->where('warehouse_id', $item['warehouse_id'])
                        ->get()
                        ->getFirstRow('array');

                    if (is_array($existingInventory) && isset($existingInventory['id'])) {
                        $db->table('inventory')
                            ->set('quantity', 'quantity + ' . (int) $item['qty'], false)
                            ->set('updated_at', date('Y-m-d H:i:s'))
                            ->where('id', (int) $existingInventory['id'])
                            ->update();
                    } else {
                        $db->table('inventory')->insert([
                            'variant_id'         => (int) $item['product_variant_id'],
                            'warehouse_id'       => (int) $item['warehouse_id'],
                            'quantity'           => (int) $item['qty'],
                            'reserved_quantity'  => 0,
                            'updated_at'         => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to save purchase transaction.');
            }

            $db->transCommit();

            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Purchase created successfully.',
                'data'    => ['purchase_id' => $purchaseId],
            ]);
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Failed to create purchase.',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function suppliers(): ResponseInterface
    {
        $rows = (new SupplierModel())
            ->orderBy('name', 'ASC')
            ->findAll(500);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function createSupplier(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Supplier name is required.']);
        }

        $model = new SupplierModel();
        $id    = $model->createOne([
            'name'    => $name,
            'phone'   => $payload['phone'] ?? null,
            'email'   => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
        ]);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Supplier created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function products(): ResponseInterface
    {
        $rows = (new ProductModel())
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll(1000);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function createProduct(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name       = trim((string) ($payload['name'] ?? ''));
        $categoryId = (int) ($payload['category_id'] ?? 0);
        if ($name === '' || $categoryId < 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'name and category_id are required.',
            ]);
        }

        $id = (new ProductModel())->createOne([
            'name'          => $name,
            'category_id'   => $categoryId,
            'serial_number' => $payload['serial_number'] ?? null,
            'brand'         => $payload['brand'] ?? null,
            'description'   => null,
            'is_active'     => 1,
        ]);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Product created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function variants(): ResponseInterface
    {
        $productId = (int) ($this->request->getGet('product_id') ?? 0);

        $builder = (new ProductVariantModel())
            ->select(
                "product_variants.id, product_variants.product_id, product_variants.sku, product_variants.stock_qty, " .
                "product_variants.selling_price, products.name AS product_name, " .
                "MAX(CASE WHEN variant_attributes.name = 'Size' THEN variant_attribute_values.value END) AS size_value"
            )
            ->join('products', 'products.id = product_variants.product_id')
            ->join('product_variant_values', 'product_variant_values.product_variant_id = product_variants.id', 'left')
            ->join('variant_attributes', 'variant_attributes.id = product_variant_values.attribute_id', 'left')
            ->join('variant_attribute_values', 'variant_attribute_values.id = product_variant_values.attribute_value_id', 'left')
            ->where('product_variants.is_active', 1)
            ->groupBy('product_variants.id, product_variants.product_id, product_variants.sku, product_variants.stock_qty, product_variants.selling_price, products.name');

        if ($productId > 0) {
            $builder->where('product_variants.product_id', $productId);
        }

        $rows = $builder
            ->orderBy('products.name', 'ASC')
            ->findAll(1000);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function inventory(): ResponseInterface
    {
        $productName   = trim((string) ($this->request->getGet('product_name') ?? ''));
        $productNumber = trim((string) ($this->request->getGet('product_number') ?? ''));

        $builder = db_connect()->table('inventory')
            ->select(
                "inventory.id, inventory.variant_id, inventory.warehouse_id, inventory.quantity, inventory.reserved_quantity, inventory.updated_at, " .
                "product_variants.sku, product_variants.cost_price, product_variants.selling_price, " .
                "products.name AS product_name, products.serial_number AS product_number, products.brand, " .
                "warehouses.name AS warehouse_name, warehouses.location AS warehouse_location, " .
                "MAX(CASE WHEN variant_attributes.name = 'Size' THEN variant_attribute_values.value END) AS size_value, " .
                "GROUP_CONCAT(DISTINCT CASE WHEN variant_attributes.name <> 'Size' " .
                "THEN CONCAT(variant_attributes.name, ': ', variant_attribute_values.value) END " .
                "ORDER BY variant_attributes.name, variant_attribute_values.value SEPARATOR ', ') AS style"
            )
            ->join('product_variants', 'product_variants.id = inventory.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('warehouses', 'warehouses.id = inventory.warehouse_id')
            ->join('product_variant_values', 'product_variant_values.product_variant_id = product_variants.id', 'left')
            ->join('variant_attributes', 'variant_attributes.id = product_variant_values.attribute_id', 'left')
            ->join('variant_attribute_values', 'variant_attribute_values.id = product_variant_values.attribute_value_id', 'left')
            ->where('product_variants.is_active', 1)
            ->groupBy(
                'inventory.id, inventory.variant_id, inventory.warehouse_id, inventory.quantity, inventory.reserved_quantity, inventory.updated_at, ' .
                'product_variants.sku, product_variants.cost_price, product_variants.selling_price, products.name, products.serial_number, products.brand, ' .
                'warehouses.name, warehouses.location'
            )
            ->orderBy('products.name', 'ASC');

        if ($productName !== '') {
            $builder->like('products.name', $productName);
        }
        if ($productNumber !== '') {
            $builder->like('products.serial_number', $productNumber);
        }

        $rows = $builder->get(5000)->getResultArray();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function stock(): ResponseInterface
    {
        return $this->inventory();
    }

    public function updateVariantWarehouse(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $variantId      = (int) ($payload['variant_id'] ?? 0);
        $fromWarehouseId = (int) ($payload['from_warehouse_id'] ?? 0);
        $toWarehouseId   = (int) ($payload['to_warehouse_id'] ?? 0);
        $qty            = (int) ($payload['qty'] ?? 0);

        if ($variantId < 1 || $fromWarehouseId < 1 || $toWarehouseId < 1 || $qty < 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'variant_id, from_warehouse_id, to_warehouse_id and qty are required.',
            ]);
        }
        if ($fromWarehouseId === $toWarehouseId) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Source and destination warehouses must be different.',
            ]);
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $sourceInventory = $db->table('inventory')
                ->select('id, quantity')
                ->where('variant_id', $variantId)
                ->where('warehouse_id', $fromWarehouseId)
                ->get()
                ->getFirstRow('array');

            if (! is_array($sourceInventory) || (int) ($sourceInventory['quantity'] ?? 0) < $qty) {
                throw new RuntimeException('Insufficient source warehouse stock.');
            }

            $db->table('inventory')
                ->set('quantity', 'quantity - ' . $qty, false)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('id', (int) $sourceInventory['id'])
                ->update();

            $targetInventory = $db->table('inventory')
                ->select('id')
                ->where('variant_id', $variantId)
                ->where('warehouse_id', $toWarehouseId)
                ->get()
                ->getFirstRow('array');

            if (is_array($targetInventory) && isset($targetInventory['id'])) {
                $db->table('inventory')
                    ->set('quantity', 'quantity + ' . $qty, false)
                    ->set('updated_at', date('Y-m-d H:i:s'))
                    ->where('id', (int) $targetInventory['id'])
                    ->update();
            } else {
                $db->table('inventory')->insert([
                    'variant_id'        => $variantId,
                    'warehouse_id'      => $toWarehouseId,
                    'quantity'          => $qty,
                    'reserved_quantity' => 0,
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to move variant stock between warehouses.');
            }

            $db->transCommit();

            return $this->response->setJSON([
                'message' => 'Warehouse stock updated successfully.',
            ]);
        } catch (Throwable $e) {
            $db->transRollback();

            if ($e->getMessage() === 'Insufficient source warehouse stock.') {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => $e->getMessage(),
                ]);
            }

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Failed to update warehouse stock.',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function generatePurchaseNo(): string
    {
        return 'PO-' . date('Ymd-His') . '-' . random_int(100, 999);
    }

    private function findOrCreateVariantBySize(
        BaseConnection $db,
        ProductVariantModel $productVariantModel,
        array $product,
        string $sizeValue,
        float $unitCost
    ): int {
        $sizeAttributeId = $this->getOrCreateSizeAttributeId($db);
        $sizeValueId     = $this->getOrCreateSizeValueId($db, $sizeAttributeId, $sizeValue);

        $existing = $db->table('product_variants pv')
            ->select('pv.id')
            ->join('product_variant_values pvv', 'pvv.product_variant_id = pv.id')
            ->where('pv.product_id', (int) $product['id'])
            ->where('pv.is_active', 1)
            ->where('pvv.attribute_id', $sizeAttributeId)
            ->where('pvv.attribute_value_id', $sizeValueId)
            ->get()
            ->getFirstRow('array');

        if (is_array($existing) && isset($existing['id'])) {
            return (int) $existing['id'];
        }

        $sku = $this->generateVariantSku($db, $product, $sizeValue);

        $variantId = (int) $productVariantModel->createOne([
            'product_id'     => (int) $product['id'],
            'sku'            => $sku,
            'barcode'        => null,
            'cost_price'     => $unitCost,
            'selling_price'  => 0,
            'stock_qty'      => 0,
            'is_active'      => 1,
        ]);

        $db->table('product_variant_values')->insert([
            'product_variant_id' => $variantId,
            'attribute_id'       => $sizeAttributeId,
            'attribute_value_id' => $sizeValueId,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        return $variantId;
    }

    private function getOrCreateSizeAttributeId(BaseConnection $db): int
    {
        $row = $db->table('variant_attributes')
            ->select('id')
            ->where('name', 'Size')
            ->get()
            ->getFirstRow('array');

        if (is_array($row) && isset($row['id'])) {
            return (int) $row['id'];
        }

        $db->table('variant_attributes')->insert([
            'name'       => 'Size',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    private function getOrCreateSizeValueId(BaseConnection $db, int $attributeId, string $sizeValue): int
    {
        $row = $db->table('variant_attribute_values')
            ->select('id')
            ->where('attribute_id', $attributeId)
            ->where('value', $sizeValue)
            ->get()
            ->getFirstRow('array');

        if (is_array($row) && isset($row['id'])) {
            return (int) $row['id'];
        }

        $db->table('variant_attribute_values')->insert([
            'attribute_id' => $attributeId,
            'value'        => $sizeValue,
            'sort_order'   => 0,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    private function generateVariantSku(BaseConnection $db, array $product, string $sizeValue): string
    {
        $serial = trim((string) ($product['serial_number'] ?? ''));
        $base   = $serial !== '' ? $serial : ('P' . (int) ($product['id'] ?? 0));
        $size   = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($sizeValue)) ?: 'SZ';

        do {
            $rand = random_int(100, 999);
            $sku  = $base . '-' . $size . '-' . $rand;
            $exists = $db->table('product_variants')
                ->select('id')
                ->where('sku', $sku)
                ->get()
                ->getFirstRow('array');
        } while (is_array($exists));

        return $sku;
    }
}
