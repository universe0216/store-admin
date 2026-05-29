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
        $productName = trim((string) ($this->request->getGet('product_name') ?? ''));
        $dateFrom    = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo      = trim((string) ($this->request->getGet('date_to') ?? ''));
        $page        = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage     = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 20)));
        $offset      = ($page - 1) * $perPage;

        $grouped = $productName !== '';
        $builder = $this->buildPurchasesListBuilder($productName, $dateFrom, $dateTo);
        $total   = $this->countPurchasesList($productName, $dateFrom, $dateTo, $grouped);

        $rows = $builder
            ->orderBy('purchases.id', 'DESC')
            ->findAll($perPage, $offset);

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $summary    = $this->getPurchasesListSummary($productName, $dateFrom, $dateTo);

        return $this->response->setJSON([
            'data'       => $rows,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
            'summary'    => $summary,
        ]);
    }

    /**
     * @return array{
     *     total_purchases: int,
     *     total_products: int,
     *     total_product_variants: int,
     *     total_paid_total: float,
     *     total_transfer_fee: float,
     *     total_grand_total: float
     * }
     */
    private function getPurchasesListSummary(string $productName, string $dateFrom, string $dateTo): array
    {
        $empty = [
            'total_purchases'        => 0,
            'total_products'         => 0,
            'total_product_variants' => 0,
            'total_paid_total'       => 0.0,
            'total_transfer_fee'     => 0.0,
            'total_grand_total'      => 0.0,
        ];

        $subSql = $this->compiledPurchasesIdSubquery($productName, $dateFrom, $dateTo);
        $db     = db_connect();

        $purchaseAgg = $db->query(
            "SELECT COUNT(*) AS total_purchases, " .
            "COALESCE(SUM(paid_total), 0) AS total_paid_total, " .
            "COALESCE(SUM(transfer_fee), 0) AS total_transfer_fee, " .
            "COALESCE(SUM(grand_total), 0) AS total_grand_total " .
            "FROM purchases WHERE id IN ({$subSql})"
        )->getRowArray();

        if (! is_array($purchaseAgg) || (int) ($purchaseAgg['total_purchases'] ?? 0) === 0) {
            return $empty;
        }

        $itemAgg = $db->query(
            'SELECT COUNT(DISTINCT products.id) AS total_products, ' .
            'COUNT(DISTINCT purchase_items.product_variant_id) AS total_product_variants ' .
            'FROM purchase_items ' .
            'INNER JOIN product_variants ON product_variants.id = purchase_items.product_variant_id ' .
            'INNER JOIN products ON products.id = product_variants.product_id ' .
            "WHERE purchase_items.purchase_id IN ({$subSql})"
        )->getRowArray();

        return [
            'total_purchases'        => (int) ($purchaseAgg['total_purchases'] ?? 0),
            'total_products'         => (int) ($itemAgg['total_products'] ?? 0),
            'total_product_variants' => (int) ($itemAgg['total_product_variants'] ?? 0),
            'total_paid_total'       => (float) ($purchaseAgg['total_paid_total'] ?? 0),
            'total_transfer_fee'     => (float) ($purchaseAgg['total_transfer_fee'] ?? 0),
            'total_grand_total'      => (float) ($purchaseAgg['total_grand_total'] ?? 0),
        ];
    }

    private function buildPurchasesListBuilder(string $productName, string $dateFrom, string $dateTo): PurchaseModel
    {
        return $this->applyPurchasesListFilters(
            (new PurchaseModel())->select('purchases.*, suppliers.name AS supplier_name'),
            $productName,
            $dateFrom,
            $dateTo
        );
    }

    private function applyPurchasesListFilters(
        PurchaseModel $model,
        string $productName,
        string $dateFrom,
        string $dateTo
    ): PurchaseModel {
        $model->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left');

        if ($dateFrom !== '') {
            $model->where('DATE(purchases.purchase_date) >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $model->where('DATE(purchases.purchase_date) <=', $dateTo);
        }

        if ($productName !== '') {
            $model
                ->join('purchase_items', 'purchase_items.purchase_id = purchases.id')
                ->join('product_variants', 'product_variants.id = purchase_items.product_variant_id')
                ->join('products', 'products.id = product_variants.product_id')
                ->like('products.name', $productName)
                ->groupBy('purchases.id');
        }

        return $model;
    }

    private function compiledPurchasesIdSubquery(string $productName, string $dateFrom, string $dateTo): string
    {
        $model = $this->applyPurchasesListFilters(new PurchaseModel(), $productName, $dateFrom, $dateTo);

        return $model->builder()->select('purchases.id')->getCompiledSelect(false);
    }

    private function countPurchasesList(
        string $productName,
        string $dateFrom,
        string $dateTo,
        bool $grouped
    ): int {
        if (! $grouped) {
            return $this->buildPurchasesListBuilder($productName, $dateFrom, $dateTo)->countAllResults();
        }

        $db       = db_connect();
        $subSql   = $this->compiledPurchasesIdSubquery($productName, $dateFrom, $dateTo);
        $countRow = $db->query("SELECT COUNT(*) AS aggregate FROM ({$subSql}) purchase_ids")->getRow();

        return (int) ($countRow->aggregate ?? 0);
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
                "GROUP_CONCAT(DISTINCT product_variants.style ORDER BY product_variants.style SEPARATOR ', ') AS style, " .
                "purchase_items.unit_cost, " .
                "GROUP_CONCAT(DISTINCT product_variants.size ORDER BY product_variants.size SEPARATOR ', ') AS size_value, " .
                "MAX(purchase_items.qty) AS sets_count, " .
                "SUM(purchase_items.qty) AS units_count, " .
                "SUM(purchase_items.line_total) AS total_price"
            )
            ->join('product_variants', 'product_variants.id = purchase_items.product_variant_id')
            ->join('products', 'products.id = product_variants.product_id')
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
        $transferFee   = max(0, (float) ($payload['transfer_fee'] ?? 0));
        $headerDiscount = (float) ($payload['discount_total'] ?? 0);
        $paidTotal      = (float) ($payload['paid_total'] ?? 0);
        $items         = $payload['items'] ?? [];

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
            $style          = $item['style'] ?? '';
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
                        trim((string) $style),
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

        $discountTotal = $headerDiscount;
        $grandTotal    = $subTotal - $discountTotal + $transferFee;
        $db->transBegin();

        try {
            $purchaseId = $purchaseModel->createOne([
                'purchase_no'    => $this->generatePurchaseNo(),
                'purchase_date'  => $purchaseDate,
                'supplier_id'    => $supplierId,
                'status'         => $status,
                'sub_total'      => $subTotal,
                'discount_total' => $discountTotal,
                'transfer_fee'   => $transferFee,
                'grand_total'    => $grandTotal,
                'paid_total'     => $paidTotal,
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
                "product_variants.selling_price, product_variants.size AS size_value, product_variants.style, " .
                "products.name AS product_name"
            )
            ->join('products', 'products.id = product_variants.product_id')
            ->where('product_variants.is_active', 1);

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
                "product_variants.size AS size_value, product_variants.style, " .
                "products.name AS product_name, products.serial_number AS product_number, products.brand, " .
                "warehouses.name AS warehouse_name, warehouses.location AS warehouse_location"
            )
            ->join('product_variants', 'product_variants.id = inventory.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('warehouses', 'warehouses.id = inventory.warehouse_id')
            ->where('product_variants.is_active', 1)
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
        return 'PO-' .  date('Ymd-His');
    }

    private function findOrCreateVariantBySize(
        BaseConnection $db,
        ProductVariantModel $productVariantModel,
        array $product,
        string $sizeValue,
        string $styleValue,
        float $unitCost
    ): int {
        $existing = $db->table('product_variants')
            ->select('id')
            ->where('product_id', (int) $product['id'])
            ->where('is_active', 1)
            ->where('size', $sizeValue)
            ->where('style', $styleValue)
            ->get()
            ->getFirstRow('array');

        if (is_array($existing) && isset($existing['id'])) {
            return (int) $existing['id'];
        }

        $sku = $this->generateVariantSku($db, $product, $sizeValue);

        return (int) $productVariantModel->createOne([
            'product_id'    => (int) $product['id'],
            'sku'           => $sku,
            'barcode'       => null,
            'size'          => $sizeValue,
            'style'         => $styleValue,
            'cost_price'    => $unitCost,
            'selling_price' => 0,
            'stock_qty'     => 0,
            'is_active'     => 1,
        ]);
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
