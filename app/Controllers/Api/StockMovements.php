<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\ResponseInterface;

class StockMovements extends BaseController
{
    public function index(): ResponseInterface
    {
        $search        = trim((string) ($this->request->getGet('search') ?? ''));
        $movementType  = trim((string) ($this->request->getGet('movement_type') ?? ''));
        $referenceType = trim((string) ($this->request->getGet('reference_type') ?? ''));
        $dateFrom      = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo        = trim((string) ($this->request->getGet('date_to') ?? ''));
        $page          = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage       = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 20)));
        $offset        = ($page - 1) * $perPage;

        $filters = [
            'search'         => $search,
            'movement_type'  => $movementType,
            'reference_type' => $referenceType,
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
        ];

        $total = $this->countStockMovements($filters);
        $rows  = $this->fetchStockMovements($filters, $perPage, $offset);

        return $this->response->setJSON([
            'data'       => $rows,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ]);
    }

    /**
     * @param array{search: string, movement_type: string, reference_type: string, date_from: string, date_to: string} $filters
     */
    private function buildStockMovementQuery($db, array $filters): BaseBuilder
    {
        $referenceNoExpr = "
            CASE stock_movements.reference_type
                WHEN 'purchase' THEN (
                    SELECT purchases.purchase_no FROM purchases
                    WHERE purchases.id = stock_movements.reference_id LIMIT 1
                )
                WHEN 'sale' THEN (
                    SELECT sales.sale_no FROM sales
                    WHERE sales.id = stock_movements.reference_id LIMIT 1
                )
                WHEN 'transfer' THEN (
                    SELECT transfers.transfer_no FROM transfers
                    WHERE transfers.id = stock_movements.reference_id LIMIT 1
                )
                ELSE NULL
            END";

        $builder = $db->table('stock_movements')
            ->select(
                'stock_movements.id, stock_movements.created_at, stock_movements.movement_type, ' .
                'stock_movements.qty_change, stock_movements.reference_type, stock_movements.reference_id, ' .
                'stock_movements.notes, products.name AS product_name, products.serial_number AS product_number, ' .
                'product_variants.size AS size_value, product_variants.style, ' .
                "({$referenceNoExpr}) AS reference_no",
                false
            )
            ->join('product_variants', 'product_variants.id = stock_movements.product_variant_id')
            ->join('products', 'products.id = product_variants.product_id');

        if ($filters['search'] !== '') {
            $builder->groupStart()
                ->like('products.name', $filters['search'])
                ->orLike('products.serial_number', $filters['search'])
                ->orLike('product_variants.style', $filters['search'])
                ->groupEnd();
        }

        if ($filters['movement_type'] !== '') {
            $builder->where('stock_movements.movement_type', $filters['movement_type']);
        }

        if ($filters['reference_type'] !== '') {
            $builder->where('stock_movements.reference_type', $filters['reference_type']);
        }

        if ($filters['date_from'] !== '') {
            $builder->where('DATE(stock_movements.created_at) >=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $builder->where('DATE(stock_movements.created_at) <=', $filters['date_to']);
        }

        return $builder;
    }

    /**
     * @param array{search: string, movement_type: string, reference_type: string, date_from: string, date_to: string} $filters
     *
     * @return list<array<string, mixed>>
     */
    private function fetchStockMovements(array $filters, int $limit, int $offset): array
    {
        $db = db_connect();

        if (! $db->tableExists('stock_movements')) {
            return [];
        }

        $rows = $this->buildStockMovementQuery($db, $filters)
            ->orderBy('stock_movements.created_at', 'DESC')
            ->orderBy('stock_movements.id', 'DESC')
            ->get($limit, $offset)
            ->getResultArray();

        return array_map(static function (array $row): array {
            $createdAt = $row['created_at'] ?? null;
            if ($createdAt !== null && $createdAt !== '') {
                $timestamp = strtotime((string) $createdAt);
                $row['movement_date'] = $timestamp !== false
                    ? date('Y-m-d H:i', $timestamp)
                    : (string) $createdAt;
            } else {
                $row['movement_date'] = '';
            }

            $row['qty_change']      = (int) ($row['qty_change'] ?? 0);
            $row['reference_no']    = trim((string) ($row['reference_no'] ?? ''));
            $row['movement_label']  = ucfirst(str_replace('_', ' ', (string) ($row['movement_type'] ?? '')));
            $row['reference_label'] = ucfirst(str_replace('_', ' ', (string) ($row['reference_type'] ?? '')));

            return $row;
        }, $rows);
    }

    /**
     * @param array{search: string, movement_type: string, reference_type: string, date_from: string, date_to: string} $filters
     */
    private function countStockMovements(array $filters): int
    {
        $db = db_connect();

        if (! $db->tableExists('stock_movements')) {
            return 0;
        }

        return (int) $this->buildStockMovementQuery($db, $filters)->countAllResults();
    }
}
