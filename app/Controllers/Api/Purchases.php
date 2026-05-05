<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\PurchaseItemModel;
use App\Models\PurchaseModel;
use App\Models\StockMovementModel;
use App\Models\SupplierModel;
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
            ->select('purchase_items.*, product_variants.sku, products.name AS product_name')
            ->join('product_variants', 'product_variants.id = purchase_items.product_variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->where('purchase_items.purchase_id', $id)
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

        $purchaseModel      = new PurchaseModel();
        $purchaseItemModel  = new PurchaseItemModel();
        $productVariantModel = new ProductVariantModel();
        $stockMovementModel = new StockMovementModel();

        $subTotal      = 0.0;
        $discountTotal = 0.0;
        $normalized    = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $variantId      = (int) ($item['product_variant_id'] ?? 0);
            $qty            = (int) ($item['qty'] ?? 0);
            $unitCost       = (float) ($item['unit_cost'] ?? 0);
            $discountAmount = 0.0;

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
                'unit_cost'          => $unitCost,
                'discount_amount'    => $discountAmount,
                'line_total'         => $lineTotal,
            ];
        }

        if ($normalized === []) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No valid purchase items.']);
        }

        $grandTotal = $subTotal - $discountTotal;
        $db         = db_connect();
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

    private function generatePurchaseNo(): string
    {
        return 'PO-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
