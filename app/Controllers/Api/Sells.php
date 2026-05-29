<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SaleItemModel;
use App\Models\SaleModel;
use App\Models\StockMovementModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Sells extends BaseController
{
    public function index(): ResponseInterface
    {
        $productName = trim((string) ($this->request->getGet('product_name') ?? $this->request->getGet('q') ?? ''));
        $dateFrom    = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo      = trim((string) ($this->request->getGet('date_to') ?? ''));
        $page        = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage     = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 20)));
        $offset      = ($page - 1) * $perPage;

        $grouped = $productName !== '';
        $builder = $this->buildSalesListBuilder($productName, $dateFrom, $dateTo);
        $total   = $this->countSalesList($productName, $dateFrom, $dateTo, $grouped);

        $rows = $builder
            ->orderBy('sales.id', 'DESC')
            ->findAll($perPage, $offset);

        $subSql  = $this->compiledSalesIdSubquery($productName, $dateFrom, $dateTo);
        $metrics = $this->getSaleMetricsBySaleIdsSubquery($subSql);

        foreach ($rows as &$row) {
            $saleId = (int) ($row['id'] ?? 0);
            $row['total_cost']   = (float) ($metrics['cost_by_sale'][$saleId] ?? 0);
            $row['total_profit'] = (float) ($row['grand_total'] ?? 0) - $row['total_cost'];
        }
        unset($row);

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $summary    = $this->getSalesListSummary($productName, $dateFrom, $dateTo);

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

    public function show(int $id): ResponseInterface
    {
        $sale = $this->buildSalesListBuilder('', '', '')
            ->where('sales.id', $id)
            ->first();

        if ($sale === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Sale not found.']);
        }

        $items   = $this->getSaleItemsForSale($id);
        $metrics = $this->getSaleMetricsForSale($id, (float) ($sale['grand_total'] ?? 0));

        return $this->response->setJSON([
            'data' => [
                'sale'    => $sale,
                'items'   => $items,
                'metrics' => $metrics,
            ],
        ]);
    }

    public function productsByWarehouse(): ResponseInterface
    {
        $warehouseId = (int) ($this->request->getGet('warehouse_id') ?? 0);

        $builder = db_connect()->table('inventory')
            ->select(
                'inventory.id AS inventory_id, inventory.variant_id, inventory.warehouse_id, inventory.quantity, ' .
                'product_variants.sku, product_variants.style, product_variants.cost_price, product_variants.selling_price, ' .
                'product_variants.size AS size_value, products.name AS product_name, ' .
                'products.serial_number AS product_number, products.brand, warehouses.name AS warehouse_name'
            )
            ->join('product_variants', 'product_variants.id = inventory.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('warehouses', 'warehouses.id = inventory.warehouse_id')
            ->where('inventory.quantity >', 0)
            ->orderBy('products.name', 'ASC');

        if ($warehouseId > 0) {
            $builder->where('inventory.warehouse_id', $warehouseId);
        }

        $rows = $builder->get()->getResultArray();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function delete(int $id): ResponseInterface
    {
        $saleModel = new SaleModel();
        $sale      = $saleModel->find($id);

        if ($sale === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Sale not found.']);
        }

        $items       = (new SaleItemModel())->where('sale_id', $id)->findAll();
        $warehouseId = (int) ($sale['warehouse_id'] ?? 0);
        $db          = db_connect();
        $db->transBegin();

        try {
            foreach ($items as $item) {
                $this->restoreSaleStock(
                    $db,
                    $warehouseId,
                    (int) ($item['product_variant_id'] ?? 0),
                    (int) ($item['qty'] ?? 0)
                );
            }

            $db->table('stock_movements')
                ->where('reference_type', 'sale')
                ->where('reference_id', $id)
                ->delete();

            $saleModel->delete($id);

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to delete sale.');
            }

            $db->transCommit();

            return $this->response->setJSON(['message' => 'Sale deleted successfully.']);
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Failed to delete sale.',
                'error'   => $e->getMessage(),
            ]);
        }
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
        $paymentMethod = strtolower(trim((string) ($payload['payment_method'] ?? 'cash')));
        $items        = $payload['items'] ?? [];

        if ($warehouseId < 1 || $saleDate === '' || ! is_array($items) || $items === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'warehouse_id, sale_date and at least one item are required.',
            ]);
        }

        $allowedPaymentMethods = ['cash', 'bank_transfer', 'card', 'check', 'other'];
        if (! in_array($paymentMethod, $allowedPaymentMethods, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Invalid payment method.',
            ]);
        }

        $saleModel          = new SaleModel();
        $saleItemModel      = new SaleItemModel();
        $stockMovementModel = new StockMovementModel();
        $db                 = db_connect();

        $normalized = [];
        $subTotal   = 0.0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $variantId = (int) ($item['variant_id'] ?? 0);
            $qty       = (int) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

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
                'variant_id' => $variantId,
                'qty'        => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        if ($normalized === []) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No valid sale items.']);
        }

        $discountTotal = max(0, (float) ($payload['discount_total'] ?? 0));
        $paidTotal     = (float) ($payload['paid_total'] ?? ($subTotal - $discountTotal));
        if ($paidTotal < 0) {
            $paidTotal = 0;
        }
        if ($discountTotal > $subTotal) {
            $discountTotal = $subTotal;
            $paidTotal     = 0;
        }

        $db->transBegin();

        try {
            $saleId = $saleModel->createOne([
                'sale_no'        => $this->generateSaleNo(),
                'sale_date'      => $saleDate,
                'customer_name'  => $customerName !== '' ? $customerName : null,
                'warehouse_id'   => $warehouseId,
                'sub_total'      => $subTotal,
                'discount_total' => $discountTotal,
                'grand_total'    => $paidTotal,
                'payment_method' => $paymentMethod,
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

    /**
     * @return array{
     *     total_sales: int,
     *     total_sale_items: int,
     *     total_amount: float,
     *     total_cost: float,
     *     total_profit: float
     * }
     */
    private function getSalesListSummary(string $productName, string $dateFrom, string $dateTo): array
    {
        $empty = [
            'total_sales'      => 0,
            'total_sale_items' => 0,
            'total_amount'     => 0.0,
            'total_cost'       => 0.0,
            'total_profit'     => 0.0,
        ];

        $subSql = $this->compiledSalesIdSubquery($productName, $dateFrom, $dateTo);
        $db     = db_connect();

        $saleAgg = $db->query(
            'SELECT COUNT(*) AS total_sales, COALESCE(SUM(grand_total), 0) AS total_amount ' .
            "FROM sales WHERE id IN ({$subSql})"
        )->getRowArray();

        if (! is_array($saleAgg) || (int) ($saleAgg['total_sales'] ?? 0) === 0) {
            return $empty;
        }

        $itemAgg = $db->query(
            'SELECT COUNT(*) AS total_sale_items, ' .
            'COALESCE(SUM(sale_items.qty * product_variants.cost_price), 0) AS total_cost ' .
            'FROM sale_items ' .
            'INNER JOIN product_variants ON product_variants.id = sale_items.product_variant_id ' .
            "WHERE sale_items.sale_id IN ({$subSql})"
        )->getRowArray();

        $totalAmount = (float) ($saleAgg['total_amount'] ?? 0);
        $totalCost   = (float) ($itemAgg['total_cost'] ?? 0);

        return [
            'total_sales'      => (int) ($saleAgg['total_sales'] ?? 0),
            'total_sale_items' => (int) ($itemAgg['total_sale_items'] ?? 0),
            'total_amount'     => $totalAmount,
            'total_cost'       => $totalCost,
            'total_profit'     => $totalAmount - $totalCost,
        ];
    }

    /**
     * @return array{total_amount: float, total_cost: float, total_profit: float}
     */
    private function getSaleMetricsForSale(int $saleId, float $grandTotal): array
    {
        $db  = db_connect();
        $row = $db->query(
            'SELECT COALESCE(SUM(sale_items.qty * product_variants.cost_price), 0) AS total_cost ' .
            'FROM sale_items ' .
            'INNER JOIN product_variants ON product_variants.id = sale_items.product_variant_id ' .
            'WHERE sale_items.sale_id = ?',
            [$saleId]
        )->getRowArray();

        $totalCost = (float) ($row['total_cost'] ?? 0);

        return [
            'total_amount' => $grandTotal,
            'total_cost'   => $totalCost,
            'total_profit' => $grandTotal - $totalCost,
        ];
    }

    /**
     * @return array{cost_by_sale: array<int, float>}
     */
    private function getSaleMetricsBySaleIdsSubquery(string $subSql): array
    {
        $db   = db_connect();
        $rows = $db->query(
            'SELECT sale_items.sale_id, COALESCE(SUM(sale_items.qty * product_variants.cost_price), 0) AS total_cost ' .
            'FROM sale_items ' .
            'INNER JOIN product_variants ON product_variants.id = sale_items.product_variant_id ' .
            "WHERE sale_items.sale_id IN ({$subSql}) " .
            'GROUP BY sale_items.sale_id'
        )->getResultArray();

        $costBySale = [];
        foreach ($rows as $row) {
            $costBySale[(int) ($row['sale_id'] ?? 0)] = (float) ($row['total_cost'] ?? 0);
        }

        return ['cost_by_sale' => $costBySale];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getSaleItemsForSale(int $saleId): array
    {
        return (new SaleItemModel())
            ->select(
                'products.name AS product_name, products.serial_number AS product_number, products.brand, ' .
                'product_variants.sku, product_variants.size AS size_value, ' .
                'product_variants.cost_price AS unit_cost, sale_items.qty, sale_items.unit_price, sale_items.line_total'
            )
            ->join('product_variants', 'product_variants.id = sale_items.product_variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->where('sale_items.sale_id', $saleId)
            ->findAll();
    }

    private function buildSalesListBuilder(string $productName, string $dateFrom, string $dateTo): SaleModel
    {
        return $this->applySalesListFilters(
            (new SaleModel())->select('sales.*, warehouses.name AS warehouse_name'),
            $productName,
            $dateFrom,
            $dateTo
        );
    }

    private function applySalesListFilters(
        SaleModel $model,
        string $productName,
        string $dateFrom,
        string $dateTo
    ): SaleModel {
        $model->join('warehouses', 'warehouses.id = sales.warehouse_id', 'left');

        if ($dateFrom !== '') {
            $model->where('DATE(sales.sale_date) >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $model->where('DATE(sales.sale_date) <=', $dateTo);
        }

        if ($productName !== '') {
            $model
                ->join('sale_items', 'sale_items.sale_id = sales.id')
                ->join('product_variants', 'product_variants.id = sale_items.product_variant_id')
                ->join('products', 'products.id = product_variants.product_id')
                ->groupStart()
                    ->like('products.name', $productName)
                    ->orLike('products.serial_number', $productName)
                ->groupEnd()
                ->groupBy('sales.id');
        }

        return $model;
    }

    private function compiledSalesIdSubquery(string $productName, string $dateFrom, string $dateTo): string
    {
        $model = $this->applySalesListFilters(new SaleModel(), $productName, $dateFrom, $dateTo);

        return $model->builder()->select('sales.id')->getCompiledSelect(false);
    }

    private function countSalesList(
        string $productName,
        string $dateFrom,
        string $dateTo,
        bool $grouped
    ): int {
        if (! $grouped) {
            return $this->buildSalesListBuilder($productName, $dateFrom, $dateTo)->countAllResults();
        }

        $db       = db_connect();
        $subSql   = $this->compiledSalesIdSubquery($productName, $dateFrom, $dateTo);
        $countRow = $db->query("SELECT COUNT(*) AS aggregate FROM ({$subSql}) sale_ids")->getRow();

        return (int) ($countRow->aggregate ?? 0);
    }

    private function restoreSaleStock(BaseConnection $db, int $warehouseId, int $variantId, int $qty): void
    {
        if ($variantId < 1 || $qty < 1) {
            return;
        }

        if ($warehouseId > 0) {
            $inventory = $db->table('inventory')
                ->select('id')
                ->where('warehouse_id', $warehouseId)
                ->where('variant_id', $variantId)
                ->get()
                ->getFirstRow('array');

            if (is_array($inventory) && isset($inventory['id'])) {
                $db->table('inventory')
                    ->set('quantity', 'quantity + ' . $qty, false)
                    ->set('updated_at', date('Y-m-d H:i:s'))
                    ->where('id', (int) $inventory['id'])
                    ->update();
            } else {
                $db->table('inventory')->insert([
                    'variant_id'        => $variantId,
                    'warehouse_id'      => $warehouseId,
                    'quantity'          => $qty,
                    'reserved_quantity' => 0,
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            }
        } else {
            $inventory = $db->table('inventory')
                ->select('id')
                ->where('variant_id', $variantId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getFirstRow('array');

            if (is_array($inventory) && isset($inventory['id'])) {
                $db->table('inventory')
                    ->set('quantity', 'quantity + ' . $qty, false)
                    ->set('updated_at', date('Y-m-d H:i:s'))
                    ->where('id', (int) $inventory['id'])
                    ->update();
            }
        }

        $db->table('product_variants')
            ->set('stock_qty', 'stock_qty + ' . $qty, false)
            ->where('id', $variantId)
            ->update();
    }

    private function generateSaleNo(): string
    {
        return 'SO-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
