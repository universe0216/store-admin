<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SaleItemModel;
use App\Models\SaleModel;
use App\Models\StockMovementModel;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Sells extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new SaleModel())
            ->orderBy('id', 'DESC')
            ->findAll(500);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function productsByWarehouse(): ResponseInterface
    {
        $warehouseId = (int) ($this->request->getGet('warehouse_id') ?? 0);
        if ($warehouseId < 1) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'warehouse_id is required.']);
        }

        $rows = db_connect()->table('inventory')
            ->select(
                "inventory.id AS inventory_id, inventory.variant_id, inventory.warehouse_id, inventory.quantity, " .
                "product_variants.sku, product_variants.cost_price, product_variants.selling_price, " .
                "products.name AS product_name, products.serial_number AS product_number, products.brand, " .
                "MAX(CASE WHEN variant_attributes.name = 'Size' THEN variant_attribute_values.value END) AS size_value"
            )
            ->join('product_variants', 'product_variants.id = inventory.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('product_variant_values', 'product_variant_values.product_variant_id = product_variants.id', 'left')
            ->join('variant_attributes', 'variant_attributes.id = product_variant_values.attribute_id', 'left')
            ->join('variant_attribute_values', 'variant_attribute_values.id = product_variant_values.attribute_value_id', 'left')
            ->where('inventory.warehouse_id', $warehouseId)
            ->where('inventory.quantity >', 0)
            ->groupBy(
                'inventory.id, inventory.variant_id, inventory.warehouse_id, inventory.quantity, product_variants.sku, ' .
                'product_variants.cost_price, product_variants.selling_price, products.name, products.serial_number, products.brand'
            )
            ->orderBy('products.name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $warehouseId  = (int) ($payload['warehouse_id'] ?? 0);
        $saleDate     = (string) ($payload['sale_date'] ?? '');
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        $items        = $payload['items'] ?? [];

        if ($warehouseId < 1 || $saleDate === '' || ! is_array($items) || $items === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'warehouse_id, sale_date and at least one item are required.',
            ]);
        }

        $saleModel         = new SaleModel();
        $saleItemModel     = new SaleItemModel();
        $stockMovementModel = new StockMovementModel();
        $db                = db_connect();

        $normalized = [];
        $subTotal   = 0.0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $variantId  = (int) ($item['variant_id'] ?? 0);
            $qty        = (int) ($item['qty'] ?? 0);
            $unitPrice  = (float) ($item['unit_price'] ?? 0);

            if ($variantId < 1 || $qty < 1 || $unitPrice < 0) {
                continue;
            }

            $inventory = $db->table('inventory')
                ->select('id, quantity')
                ->where('warehouse_id', $warehouseId)
                ->where('variant_id', $variantId)
                ->get()
                ->getFirstRow('array');

            if (! is_array($inventory) || (int) ($inventory['quantity'] ?? 0) < $qty) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Insufficient inventory stock for one or more items.',
                ]);
            }

            $lineTotal  = (float) ($qty * $unitPrice);
            $subTotal  += $lineTotal;
            $normalized[] = [
                'variant_id'  => $variantId,
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'line_total'  => $lineTotal,
            ];
        }

        if ($normalized === []) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No valid sale items.']);
        }

        $db->transBegin();

        try {
            $saleId = $saleModel->createOne([
                'sale_no'         => $this->generateSaleNo(),
                'sale_date'       => $saleDate,
                'customer_name'   => $customerName !== '' ? $customerName : null,
                'sub_total'       => $subTotal,
                'discount_total'  => 0,
                'grand_total'     => $subTotal,
                'payment_method'  => 'cash',
            ]);

            foreach ($normalized as $item) {
                $saleItemModel->createOne([
                    'sale_id'            => $saleId,
                    'product_variant_id' => $item['variant_id'],
                    'qty'                => $item['qty'],
                    'unit_price'         => $item['unit_price'],
                    'discount_amount'    => 0,
                    'line_total'         => $item['line_total'],
                ]);

                $db->table('inventory')
                    ->set('quantity', 'quantity - ' . (int) $item['qty'], false)
                    ->where('warehouse_id', $warehouseId)
                    ->where('variant_id', $item['variant_id'])
                    ->update();

                $db->table('product_variants')
                    ->set('stock_qty', 'stock_qty - ' . (int) $item['qty'], false)
                    ->where('id', $item['variant_id'])
                    ->update();

                $stockMovementModel->createOne([
                    'product_variant_id' => $item['variant_id'],
                    'movement_type'      => 'sale',
                    'qty_change'         => -1 * (int) $item['qty'],
                    'reference_type'     => 'sale',
                    'reference_id'       => $saleId,
                    'notes'              => 'Stock deducted from sale.',
                ]);
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to save sale transaction.');
            }

            $db->transCommit();
            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Sale created successfully.',
                'data'    => ['sale_id' => $saleId],
            ]);
        } catch (Throwable $e) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Failed to create sale.',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function generateSaleNo(): string
    {
        return 'SO-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
