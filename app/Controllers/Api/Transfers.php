<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\StockMovementModel;
use App\Models\TransferItemModel;
use App\Models\TransferModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Transfers extends BaseController
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
        $builder = $this->buildTransfersListBuilder($productName, $dateFrom, $dateTo);
        $total   = $this->countTransfersList($productName, $dateFrom, $dateTo, $grouped);

        $rows = $builder
            ->orderBy('transfers.id', 'DESC')
            ->findAll($perPage, $offset);

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $summary    = $this->getTransfersListSummary($productName, $dateFrom, $dateTo);

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
        $transfer = $this->buildTransfersListBuilder('', '', '')
            ->where('transfers.id', $id)
            ->first();

        if ($transfer === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Transfer not found.']);
        }

        $items = $this->getTransferItemsForTransfer($id);

        return $this->response->setJSON([
            'data' => [
                'transfer' => $transfer,
                'items'    => $items,
            ],
        ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $transferModel = new TransferModel();
        $transfer      = $transferModel->find($id);

        if ($transfer === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Transfer not found.']);
        }

        $items            = (new TransferItemModel())->where('transfer_id', $id)->findAll();
        $fromWarehouseId  = (int) ($transfer['from_warehouse_id'] ?? 0);
        $toWarehouseId    = (int) ($transfer['to_warehouse_id'] ?? 0);
        $db               = db_connect();
        $db->transBegin();

        try {
            foreach ($items as $item) {
                $variantId = (int) ($item['product_variant_id'] ?? 0);
                $qty       = (int) ($item['qty'] ?? 0);

                $this->adjustWarehouseInventory($db, $fromWarehouseId, $variantId, $qty);
                $this->adjustWarehouseInventory($db, $toWarehouseId, $variantId, -$qty);
            }

            $db->table('stock_movements')
                ->where('reference_type', 'transfer')
                ->where('reference_id', $id)
                ->delete();

            $transferModel->delete($id);

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to delete transfer.');
            }

            $db->transCommit();

            return $this->response->setJSON(['message' => 'Transfer deleted successfully.']);
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Failed to delete transfer.',
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

        $fromWarehouseId = (int) ($payload['from_warehouse_id'] ?? 0);
        $toWarehouseId   = (int) ($payload['to_warehouse_id'] ?? 0);
        $transferDate    = (string) ($payload['transfer_date'] ?? '');
        $notes           = trim((string) ($payload['notes'] ?? ''));
        $items           = $payload['items'] ?? [];

        if ($fromWarehouseId < 1 || $toWarehouseId < 1 || $fromWarehouseId === $toWarehouseId) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'from_warehouse_id and to_warehouse_id are required and must be different.',
            ]);
        }

        if ($transferDate === '' || ! is_array($items) || $items === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'transfer_date and at least one item are required.',
            ]);
        }

        $transferModel      = new TransferModel();
        $transferItemModel  = new TransferItemModel();
        $stockMovementModel = new StockMovementModel();
        $db                 = db_connect();

        $normalized = [];
        $totalQty   = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $variantId = (int) ($item['variant_id'] ?? 0);
            $qty       = (int) ($item['qty'] ?? 0);

            if ($variantId < 1 || $qty < 1) {
                continue;
            }

            $inventory = $db->table('inventory')
                ->select('id, quantity')
                ->where('warehouse_id', $fromWarehouseId)
                ->where('variant_id', $variantId)
                ->get()
                ->getFirstRow('array');

            if (! is_array($inventory) || (int) ($inventory['quantity'] ?? 0) < $qty) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Insufficient inventory stock for one or more items.',
                ]);
            }

            $totalQty    += $qty;
            $normalized[] = [
                'variant_id' => $variantId,
                'qty'        => $qty,
            ];
        }

        if ($normalized === []) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No valid transfer items.']);
        }

        $db->transBegin();

        try {
            $transferId = $transferModel->createOne([
                'transfer_no'         => $this->generateTransferNo(),
                'transfer_date'       => $transferDate,
                'from_warehouse_id'   => $fromWarehouseId,
                'to_warehouse_id'     => $toWarehouseId,
                'total_qty'           => $totalQty,
                'notes'               => $notes !== '' ? $notes : null,
            ]);

            foreach ($normalized as $item) {
                $transferItemModel->createOne([
                    'transfer_id'        => $transferId,
                    'product_variant_id' => $item['variant_id'],
                    'qty'                => $item['qty'],
                ]);

                $db->table('inventory')
                    ->set('quantity', 'quantity - ' . (int) $item['qty'], false)
                    ->set('updated_at', date('Y-m-d H:i:s'))
                    ->where('warehouse_id', $fromWarehouseId)
                    ->where('variant_id', $item['variant_id'])
                    ->update();

                $this->adjustWarehouseInventory($db, $toWarehouseId, $item['variant_id'], $item['qty']);

                $stockMovementModel->createOne([
                    'product_variant_id' => $item['variant_id'],
                    'movement_type'      => 'transfer_out',
                    'qty_change'         => -1 * (int) $item['qty'],
                    'reference_type'     => 'transfer',
                    'reference_id'       => $transferId,
                    'notes'              => 'Stock transferred out from warehouse.',
                ]);

                $stockMovementModel->createOne([
                    'product_variant_id' => $item['variant_id'],
                    'movement_type'      => 'transfer_in',
                    'qty_change'         => (int) $item['qty'],
                    'reference_type'     => 'transfer',
                    'reference_id'       => $transferId,
                    'notes'              => 'Stock transferred in to warehouse.',
                ]);
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to save transfer transaction.');
            }

            $db->transCommit();

            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Transfer created successfully.',
                'data'    => ['transfer_id' => $transferId],
            ]);
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Failed to create transfer.',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{total_transfers: int, total_transfer_items: int, total_qty: int}
     */
    private function getTransfersListSummary(string $productName, string $dateFrom, string $dateTo): array
    {
        $empty = [
            'total_transfers'      => 0,
            'total_transfer_items' => 0,
            'total_qty'            => 0,
        ];

        $subSql = $this->compiledTransferIdSubquery($productName, $dateFrom, $dateTo);
        $db     = db_connect();

        $transferAgg = $db->query(
            'SELECT COUNT(*) AS total_transfers, COALESCE(SUM(total_qty), 0) AS total_qty ' .
            "FROM transfers WHERE id IN ({$subSql})"
        )->getRowArray();

        if (! is_array($transferAgg) || (int) ($transferAgg['total_transfers'] ?? 0) === 0) {
            return $empty;
        }

        $itemAgg = $db->query(
            'SELECT COUNT(*) AS total_transfer_items ' .
            'FROM transfer_items ' .
            "WHERE transfer_id IN ({$subSql})"
        )->getRowArray();

        return [
            'total_transfers'      => (int) ($transferAgg['total_transfers'] ?? 0),
            'total_transfer_items' => (int) ($itemAgg['total_transfer_items'] ?? 0),
            'total_qty'            => (int) ($transferAgg['total_qty'] ?? 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getTransferItemsForTransfer(int $transferId): array
    {
        return (new TransferItemModel())
            ->select(
                'products.name AS product_name, products.serial_number AS product_number, products.brand, ' .
                'product_variants.sku, product_variants.size AS size_value, transfer_items.qty'
            )
            ->join('product_variants', 'product_variants.id = transfer_items.product_variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->where('transfer_items.transfer_id', $transferId)
            ->findAll();
    }

    private function buildTransfersListBuilder(string $productName, string $dateFrom, string $dateTo): TransferModel
    {
        return $this->applyTransfersListFilters(
            (new TransferModel())->select(
                'transfers.*, ' .
                'from_wh.name AS from_warehouse_name, to_wh.name AS to_warehouse_name'
            ),
            $productName,
            $dateFrom,
            $dateTo
        );
    }

    private function applyTransfersListFilters(
        TransferModel $model,
        string $productName,
        string $dateFrom,
        string $dateTo
    ): TransferModel {
        $model
            ->join('warehouses from_wh', 'from_wh.id = transfers.from_warehouse_id', 'left')
            ->join('warehouses to_wh', 'to_wh.id = transfers.to_warehouse_id', 'left');

        if ($dateFrom !== '') {
            $model->where('DATE(transfers.transfer_date) >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $model->where('DATE(transfers.transfer_date) <=', $dateTo);
        }

        if ($productName !== '') {
            $model
                ->join('transfer_items', 'transfer_items.transfer_id = transfers.id')
                ->join('product_variants', 'product_variants.id = transfer_items.product_variant_id')
                ->join('products', 'products.id = product_variants.product_id')
                ->groupStart()
                    ->like('products.name', $productName)
                    ->orLike('products.serial_number', $productName)
                ->groupEnd()
                ->groupBy('transfers.id');
        }

        return $model;
    }

    private function compiledTransferIdSubquery(string $productName, string $dateFrom, string $dateTo): string
    {
        $model = $this->applyTransfersListFilters(new TransferModel(), $productName, $dateFrom, $dateTo);

        return $model->builder()->select('transfers.id')->getCompiledSelect(false);
    }

    private function countTransfersList(
        string $productName,
        string $dateFrom,
        string $dateTo,
        bool $grouped
    ): int {
        if (! $grouped) {
            return $this->buildTransfersListBuilder($productName, $dateFrom, $dateTo)->countAllResults();
        }

        $db       = db_connect();
        $subSql   = $this->compiledTransferIdSubquery($productName, $dateFrom, $dateTo);
        $countRow = $db->query("SELECT COUNT(*) AS aggregate FROM ({$subSql}) transfer_ids")->getRow();

        return (int) ($countRow->aggregate ?? 0);
    }

    private function adjustWarehouseInventory(BaseConnection $db, int $warehouseId, int $variantId, int $delta): void
    {
        if ($warehouseId < 1 || $variantId < 1 || $delta === 0) {
            return;
        }

        $inventory = $db->table('inventory')
            ->select('id, quantity')
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->get()
            ->getFirstRow('array');

        if (is_array($inventory) && isset($inventory['id'])) {
            if ($delta < 0 && (int) ($inventory['quantity'] ?? 0) + $delta < 0) {
                throw new RuntimeException('Insufficient stock in destination warehouse to reverse transfer.');
            }

            $db->table('inventory')
                ->set('quantity', 'quantity + ' . $delta, false)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('id', (int) $inventory['id'])
                ->update();

            return;
        }

        if ($delta < 0) {
            throw new RuntimeException('Insufficient stock in destination warehouse to reverse transfer.');
        }

        $db->table('inventory')->insert([
            'variant_id'        => $variantId,
            'warehouse_id'      => $warehouseId,
            'quantity'          => $delta,
            'reserved_quantity' => 0,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    private function generateTransferNo(): string
    {
        return 'TR-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
