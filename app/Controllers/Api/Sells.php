<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Enums\SaleStatus;
use App\Libraries\LedgerService;
use App\Models\PaymentMethodModel;
use App\Models\SaleItemModel;
use App\Models\SaleModel;
use App\Models\SalePaymentModel;
use App\Models\StockMovementModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Sells extends BaseController
{
    public function index(): ResponseInterface
    {
        $productName    = trim((string) ($this->request->getGet('product_name') ?? $this->request->getGet('q') ?? ''));
        $dateFrom       = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo         = trim((string) ($this->request->getGet('date_to') ?? ''));
        $statusFilter   = $this->normalizeSaleStatusFilter((string) ($this->request->getGet('status') ?? ''));
        $page           = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage        = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 20)));
        $offset         = ($page - 1) * $perPage;

        $grouped = $productName !== '';
        $builder = $this->buildSalesListBuilder($productName, $dateFrom, $dateTo, $statusFilter);
        $total   = $this->countSalesList($productName, $dateFrom, $dateTo, $grouped, $statusFilter);

        $rows = $builder
            ->orderBy('sales.id', 'DESC')
            ->findAll($perPage, $offset);

        $subSql  = $this->compiledSalesIdSubquery($productName, $dateFrom, $dateTo, $statusFilter);
        $metrics = $this->getSaleMetricsBySaleIdsSubquery($subSql);

        foreach ($rows as &$row) {
            $saleId = (int) ($row['id'] ?? 0);
            $row['total_cost']   = (float) ($metrics['cost_by_sale'][$saleId] ?? 0);
            $row['total_profit'] = (float) ($row['grand_total'] ?? 0) - $row['total_cost'];
        }
        unset($row);

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $summary    = $this->getSalesListSummary($productName, $dateFrom, $dateTo, $statusFilter);

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

        $items    = $this->getSaleItemsForSale($id);
        $metrics  = $this->getSaleMetricsForSale($id, (float) ($sale['grand_total'] ?? 0));
        $payments = $this->getSalePaymentsForSale($id, $sale);

        return $this->response->setJSON([
            'data' => [
                'sale'     => $sale,
                'items'    => $items,
                'metrics'  => $metrics,
                'payments' => $payments,
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
                'products.serial_number AS product_number, products.brand, ' .
                'products.department, products.gender, products.season, warehouses.name AS warehouse_name'
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

            (new LedgerService())->deleteByReference($db, (string) ($sale['sale_no'] ?? ''));

            if ($db->tableExists('sale_payments')) {
                $db->table('sale_payments')->where('sale_id', $id)->delete();
            }

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
        $paymentsInput = $payload['payments'] ?? [];
        $currencyCode  = strtoupper(trim((string) ($payload['currency_code'] ?? '')));
        $items        = $payload['items'] ?? [];

        if ($warehouseId < 1 || $saleDate === '' || ! is_array($items) || $items === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'warehouse_id, sale_date and at least one item are required.',
            ]);
        }

        $saleModel          = new SaleModel();
        $saleItemModel      = new SaleItemModel();
        $stockMovementModel = new StockMovementModel();
        $db                 = db_connect();

        $normalized = [];
        $subTotal   = 0.0;
        $cogsTotal  = 0.0;

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
                ->where('variant_id', $variantId)
                ->where('quantity >',0)
                ->get()->getResultArray();

            if (count($inventory) === 0 || (int) ($inventory[0]['quantity'] ?? 0) < $qty) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Insufficient inventory stock for one or more items.',
                ]);
            }

            $variant = $db->table('product_variants')
                ->select('cost_price')
                ->where('id', $variantId)
                ->get()
                ->getFirstRow('array');
            $unitCost = (float) ($variant['cost_price'] ?? 0);

            $lineTotal  = (float) ($qty * $unitPrice);
            $lineCost   = (float) ($qty * $unitCost);
            $subTotal  += $lineTotal;
            $cogsTotal += $lineCost;
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
        if ($discountTotal > $subTotal) {
            $discountTotal = $subTotal;
        }

        $amountDue = round(max(0, $subTotal - $discountTotal), 2);
        $payments  = $this->normalizeSalePayments($paymentsInput, $amountDue);
        if ($payments === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Invalid payment methods or amounts.',
            ]);
        }

        $paidTotal    = round(array_sum(array_column($payments, 'amount')), 2);
        $unpaidTotal  = round(max(0, $amountDue - $paidTotal), 2);
        $saleStatus   = SaleStatus::fromPaymentBalance($amountDue, $paidTotal)->value;
        $primaryPaymentMethod = (string) ($payments[0]['payment_method'] ?? $paymentMethod);
        if ($primaryPaymentMethod === '' || ! $this->isValidPaymentMethod($primaryPaymentMethod)) {
            $primaryPaymentMethod = 'cash';
        }

        $db->transBegin();

        try {
            $saleNo = $this->generateSaleNo();
            $saleId = $saleModel->createOne([
                'sale_no'        => $saleNo,
                'sale_date'      => $saleDate,
                'customer_name'  => $customerName !== '' ? $customerName : null,
                'warehouse_id'   => $warehouseId,
                'sub_total'      => $subTotal,
                'discount_total' => $discountTotal,
                'grand_total'    => $amountDue,
                'paid_total'     => $paidTotal,
                'unpaid_total'   => $unpaidTotal,
                'payment_method' => $primaryPaymentMethod,
                'status'         => $saleStatus,
            ]);

            $salePaymentModel = new SalePaymentModel();
            foreach ($payments as $payment) {
                if ($db->tableExists('sale_payments')) {
                    $salePaymentModel->createOne([
                        'sale_id'        => $saleId,
                        'payment_method' => $payment['payment_method'],
                        'amount'         => $payment['amount'],
                        'created_at'     => $this->normalizePaymentDateTime($saleDate),
                    ]);
                }
            }

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
                    // ->where('warehouse_id', $warehouseId)
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

            $ledger = new LedgerService();
            $ledgerCurrency = $currencyCode !== '' ? $currencyCode : null;
            $ledger->recordSaleWithBalance(
                $db,
                $saleNo,
                $saleDate,
                $amountDue,
                $payments,
                $unpaidTotal,
                'Sale ' . $saleNo,
                $ledgerCurrency
            );
            $ledger->recordSaleCogs(
                $db,
                $saleNo,
                $saleDate,
                $cogsTotal,
                'COGS ' . $saleNo,
                $ledgerCurrency
            );

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to save sale transaction.');
            }

            $db->transCommit();

            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Sale created successfully.',
                'data'    => ['sale_id' => $saleId, 'sale_no' => $saleNo],
            ]);
        } catch (Throwable $e) {
            $db->transRollback();

            $status = $e instanceof RuntimeException ? 422 : 500;

            return $this->response->setStatusCode($status)->setJSON([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Failed to create sale.',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function addPayment(int $id): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $saleModel = new SaleModel();
        $sale      = $saleModel->find($id);

        if ($sale === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Sale not found.']);
        }

        $status = strtolower(trim((string) ($sale['status'] ?? SaleStatus::Completed->value)));
        if ($status === SaleStatus::Completed->value) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Sale is already fully paid.']);
        }

        $amountDue   = round((float) ($sale['grand_total'] ?? 0), 2);
        $unpaidTotal = round((float) ($sale['unpaid_total'] ?? max(0, $amountDue - (float) ($sale['paid_total'] ?? 0))), 2);
        if ($unpaidTotal <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No unpaid balance on this sale.']);
        }

        $paymentsInput = $payload['payments'] ?? [];
        if (! is_array($paymentsInput) || $paymentsInput === []) {
            $method = strtolower(trim((string) ($payload['payment_method'] ?? 'cash')));
            $amount = round((float) ($payload['amount'] ?? 0), 2);
            $paymentsInput = [
                [
                    'payment_method' => $method,
                    'amount'         => $amount,
                ],
            ];
        }

        $payments = $this->normalizeSalePayments($paymentsInput, $unpaidTotal);
        if ($payments === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid payment methods or amounts.']);
        }

        $paymentSum = round(array_sum(array_column($payments, 'amount')), 2);
        if ($paymentSum <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Payment amount must be greater than 0.']);
        }

        $paymentDate = trim((string) ($payload['payment_date'] ?? $sale['sale_date'] ?? date('Y-m-d H:i:s')));
        $db          = db_connect();
        $db->transBegin();

        try {
            $salePaymentModel = new SalePaymentModel();
            foreach ($payments as $payment) {
                if ($db->tableExists('sale_payments')) {
                    $salePaymentModel->createOne([
                        'sale_id'        => $id,
                        'payment_method' => $payment['payment_method'],
                        'amount'         => $payment['amount'],
                        'created_at'     => $this->normalizePaymentDateTime($paymentDate),
                    ]);
                }
            }

            $paidTotal   = round((float) ($sale['paid_total'] ?? 0) + $paymentSum, 2);
            $newUnpaid   = round(max(0, $amountDue - $paidTotal), 2);
            $saleStatus  = SaleStatus::fromPaymentBalance($amountDue, $paidTotal)->value;
            $primaryMethod = (string) ($payments[0]['payment_method'] ?? $sale['payment_method'] ?? 'cash');

            $saleModel->update($id, [
                'paid_total'     => $paidTotal,
                'unpaid_total'   => $newUnpaid,
                'status'         => $saleStatus,
                'payment_method' => $primaryMethod,
            ]);

            $ledger = new LedgerService();
            $saleNo = (string) ($sale['sale_no'] ?? '');
            foreach ($payments as $payment) {
                $ledger->recordSaleReceivablePayment(
                    $db,
                    $saleNo,
                    $paymentDate,
                    $payment['amount'],
                    $payment['payment_method'],
                    'Sale payment ' . $saleNo
                );
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to record sale payment.');
            }

            $db->transCommit();

            $updatedSale = $this->buildSalesListBuilder('', '', '')
                ->where('sales.id', $id)
                ->first();

            return $this->response->setJSON([
                'message' => 'Payment recorded successfully.',
                'data'    => [
                    'sale'     => $updatedSale,
                    'payments' => $this->getSalePaymentsForSale($id, is_array($updatedSale) ? $updatedSale : $sale),
                ],
            ]);
        } catch (Throwable $e) {
            $db->transRollback();

            $status = $e instanceof RuntimeException ? 422 : 500;

            return $this->response->setStatusCode($status)->setJSON([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Failed to record payment.',
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
    private function getSalesListSummary(string $productName, string $dateFrom, string $dateTo, string $statusFilter = ''): array
    {
        $empty = [
            'total_sales'      => 0,
            'total_sale_items' => 0,
            'total_amount'     => 0.0,
            'total_cost'       => 0.0,
            'total_profit'     => 0.0,
        ];

        $subSql = $this->compiledSalesIdSubquery($productName, $dateFrom, $dateTo, $statusFilter);
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

    private function buildSalesListBuilder(string $productName, string $dateFrom, string $dateTo, string $statusFilter = ''): SaleModel
    {
        return $this->applySalesListFilters(
            (new SaleModel())->select('sales.*, warehouses.name AS warehouse_name'),
            $productName,
            $dateFrom,
            $dateTo,
            $statusFilter
        );
    }

    private function applySalesListFilters(
        SaleModel $model,
        string $productName,
        string $dateFrom,
        string $dateTo,
        string $statusFilter = ''
    ): SaleModel {
        $model->join('warehouses', 'warehouses.id = sales.warehouse_id', 'left');

        if ($statusFilter !== '' && db_connect()->fieldExists('status', 'sales')) {
            $model->where('sales.status', $statusFilter);
        }

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

    private function compiledSalesIdSubquery(string $productName, string $dateFrom, string $dateTo, string $statusFilter = ''): string
    {
        $model = $this->applySalesListFilters(new SaleModel(), $productName, $dateFrom, $dateTo, $statusFilter);

        return $model->builder()->select('sales.id')->getCompiledSelect(false);
    }

    private function countSalesList(
        string $productName,
        string $dateFrom,
        string $dateTo,
        bool $grouped,
        string $statusFilter = ''
    ): int {
        if (! $grouped) {
            return $this->buildSalesListBuilder($productName, $dateFrom, $dateTo, $statusFilter)->countAllResults();
        }

        $db       = db_connect();
        $subSql   = $this->compiledSalesIdSubquery($productName, $dateFrom, $dateTo, $statusFilter);
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
        return 'SO-' . date('Ymd-His');
    }

    /**
     * @param array<string, mixed> $sale
     *
     * @return list<array{payment_method: string, payment_method_name: string, amount: float}>
     */
    private function getSalePaymentsForSale(int $saleId, array $sale): array
    {
        $db = db_connect();

        if ($db->tableExists('sale_payments')) {
            $rows = $db->table('sale_payments')
                ->where('sale_id', $saleId)
                ->orderBy('created_at', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if ($rows !== []) {
                return $this->formatSalePayments($rows);
            }
        }

        $method = strtolower(trim((string) ($sale['payment_method'] ?? 'cash')));
        $amount = round((float) ($sale['paid_total'] ?? $sale['grand_total'] ?? 0), 2);

        if ($method === '' || $amount <= 0) {
            return [];
        }

        return $this->formatSalePayments([
            [
                'payment_method' => $method,
                'amount'         => $amount,
                'created_at'     => $sale['sale_date'] ?? null,
            ],
        ]);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{payment_method: string, payment_method_name: string, amount: float, payment_date: string}>
     */
    private function formatSalePayments(array $rows): array
    {
        $nameMap = $this->paymentMethodNameMap();
        $result  = [];

        foreach ($rows as $row) {
            $code = strtolower(trim((string) ($row['payment_method'] ?? '')));
            if ($code === '') {
                continue;
            }

            $result[] = [
                'payment_method'      => $code,
                'payment_method_name' => $nameMap[$code] ?? ucfirst(str_replace('_', ' ', $code)),
                'amount'              => round((float) ($row['amount'] ?? 0), 2),
                'payment_date'        => $this->formatPaymentDate($row['created_at'] ?? $row['payment_date'] ?? null),
            ];
        }

        return $result;
    }

    private function formatPaymentDate(mixed $raw): string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : substr($value, 0, 10);
    }

    private function normalizePaymentDateTime(string $dateTime): string
    {
        $value = trim($dateTime);
        if ($value === '') {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value . ' 00:00:00';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }

    /**
     * @return array<string, string>
     */
    private function paymentMethodNameMap(): array
    {
        $map = [];
        $db  = db_connect();

        if ($db->tableExists('payment_methods')) {
            foreach ((new PaymentMethodModel())->listAll() as $row) {
                $code = strtolower(trim((string) ($row['code'] ?? '')));
                if ($code !== '') {
                    $map[$code] = (string) ($row['name'] ?? $code);
                }
            }
        }

        return $map;
    }

    private function isValidPaymentMethod(string $code): bool
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            return false;
        }

        $legacy = ['cash', 'bank_transfer', 'card', 'check', 'other'];

        if (db_connect()->tableExists('payment_methods')) {
            return (new PaymentMethodModel())->findByCode($code) !== null
                || in_array($code, $legacy, true);
        }

        return in_array($code, $legacy, true);
    }

    /**
     * @param mixed $paymentsInput
     *
     * @return list<array{payment_method: string, amount: float}>|null
     */
    private function normalizeSalePayments($paymentsInput, float $maxAmount): ?array
    {
        $payments = [];

        if (is_array($paymentsInput) && $paymentsInput !== []) {
            foreach ($paymentsInput as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $method = strtolower(trim((string) ($row['payment_method'] ?? '')));
                $amount = round((float) ($row['amount'] ?? 0), 2);
                if ($method === '' || $amount <= 0) {
                    continue;
                }

                if (! $this->isValidPaymentMethod($method)) {
                    return null;
                }

                $payments[] = [
                    'payment_method' => $method,
                    'amount'         => $amount,
                ];
            }
        }

        $maxAmount = round(max(0, $maxAmount), 2);
        $sum       = round(array_sum(array_column($payments, 'amount')), 2);

        if ($sum > $maxAmount + 0.01) {
            return null;
        }

        return $payments;
    }

    private function normalizeSaleStatusFilter(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, [SaleStatus::Incomplete->value, SaleStatus::Completed->value], true)
            ? $status
            : '';
    }
}
