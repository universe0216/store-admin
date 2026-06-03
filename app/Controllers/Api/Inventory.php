<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Enums\Department;
use App\Enums\Gender;
use App\Enums\Season;
use App\Models\ProductVariantModel;
use CodeIgniter\HTTP\ResponseInterface;

class Inventory extends BaseController
{
    public function index(): ResponseInterface
    {
        $search      = trim((string) (
            $this->request->getGet('q')
            ?? $this->request->getGet('product_name')
            ?? $this->request->getGet('product_number')
            ?? ''
        ));
        $warehouseId = (int) ($this->request->getGet('warehouse_id') ?? 0);
        $department  = trim((string) ($this->request->getGet('department') ?? ''));
        $gender      = trim((string) ($this->request->getGet('gender') ?? ''));
        $season      = trim((string) ($this->request->getGet('season') ?? ''));
        $tagIds      = $this->parseTagIds($this->request->getGet('tag_ids'));

        $builder = db_connect()->table('inventory')
            ->select(
                'products.id AS product_id, inventory.warehouse_id, ' .
                'products.name AS product_name, products.serial_number AS product_number, products.brand, ' .
                'products.department, products.gender, products.season, ' .
                'product_variants.id AS variant_id, product_variants.size AS size_value, ' .
                'product_variants.style, product_variants.cost_price, product_variants.selling_price, ' .
                'inventory.quantity, warehouses.name AS warehouse_name'
            )
            ->join('product_variants', 'product_variants.id = inventory.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('warehouses', 'warehouses.id = inventory.warehouse_id')
            ->where('product_variants.is_active', 1)
            ->where('inventory.quantity >', 0);

        if ($warehouseId > 0) {
            $builder->where('inventory.warehouse_id', $warehouseId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('products.name', $search)
                ->orLike('products.serial_number', $search)
                ->orLike('product_variants.style', $search)
                ->groupEnd();
        }

        if ($department !== '' && Department::isValid($department)) {
            $builder->where('products.department', $department);
        }

        if ($gender !== '' && Gender::isValid($gender)) {
            $builder->where('products.gender', $gender);
        }

        if ($season !== '' && Season::isValid($season)) {
            $builder->where('products.season', $season);
        }

        if ($tagIds !== []) {
            $productIds = db_connect()->table('taggings')
                ->select('entity_id')
                ->where('entity_type', 'products')
                ->whereIn('tag_id', $tagIds)
                ->get()
                ->getResultArray();
            $productIds = array_values(array_unique(array_map('intval', array_column($productIds, 'entity_id'))));

            if ($productIds === []) {
                return $this->response->setJSON(['data' => []]);
            }

            $builder->whereIn('products.id', $productIds);
        }

        $variantRows = $builder
            ->orderBy('products.name', 'ASC')
            ->orderBy('product_variants.size', 'ASC')
            ->get(5000)
            ->getResultArray();

        return $this->response->setJSON(['data' => $this->aggregateProductRows($variantRows)]);
    }

    public function updateSellingPrice(int $variantId): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $sellingPrice = (float) ($payload['selling_price'] ?? -1);
        if ($variantId < 1 || $sellingPrice < 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'variant_id and a non-negative selling_price are required.',
            ]);
        }

        $variantModel = new ProductVariantModel();
        $variant      = $variantModel->find($variantId);
        if ($variant === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Product variant not found.']);
        }

        $variantModel->updateOne($variantId, ['selling_price' => $sellingPrice]);

        return $this->response->setJSON([
            'message' => 'Selling price updated successfully.',
            'data'    => [
                'variant_id'    => $variantId,
                'selling_price' => $sellingPrice,
            ],
        ]);
    }

    public function updateProductSellingPrice(int $productId): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $sellingPrice = (float) ($payload['selling_price'] ?? -1);
        $warehouseId  = (int) ($payload['warehouse_id'] ?? 0);

        if ($productId < 1 || $sellingPrice < 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'product_id and a non-negative selling_price are required.',
            ]);
        }

        $db = db_connect();
        $builder = $db->table('product_variants')
            ->select('product_variants.id')
            ->join('inventory', 'inventory.variant_id = product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->where('product_variants.is_active', 1)
            ->where('inventory.quantity >', 0);

        if ($warehouseId > 0) {
            $builder->where('inventory.warehouse_id', $warehouseId);
        }

        $variantIds = array_values(array_unique(array_map(
            'intval',
            array_column($builder->get()->getResultArray(), 'id')
        )));

        if ($variantIds === []) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'No in-stock variants found for this product.']);
        }

        $variantModel = new ProductVariantModel();
        foreach ($variantIds as $variantId) {
            $variantModel->updateOne($variantId, ['selling_price' => $sellingPrice]);
        }

        return $this->response->setJSON([
            'message' => 'Selling price updated successfully.',
            'data'    => [
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId > 0 ? $warehouseId : null,
                'variant_count'  => count($variantIds),
                'selling_price'  => $sellingPrice,
            ],
        ]);
    }

    /**
     * @param list<array<string, mixed>> $variantRows
     *
     * @return list<array<string, mixed>>
     */
    private function aggregateProductRows(array $variantRows): array
    {
        $groups = [];

        foreach ($variantRows as $row) {
            $productId   = (int) ($row['product_id'] ?? 0);
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);
            if ($productId < 1 || $warehouseId < 1) {
                continue;
            }

            $key = $productId . ':' . $warehouseId;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'product_id'     => $productId,
                    'warehouse_id'   => $warehouseId,
                    'product_name'   => (string) ($row['product_name'] ?? ''),
                    'product_number' => (string) ($row['product_number'] ?? ''),
                    'brand'          => (string) ($row['brand'] ?? ''),
                    'style'          => (string) ($row['style'] ?? ''),
                    'department'     => (string) ($row['department'] ?? ''),
                    'gender'         => (string) ($row['gender'] ?? ''),
                    'season'         => (string) ($row['season'] ?? ''),
                    'warehouse_name' => (string) ($row['warehouse_name'] ?? ''),
                    'sizes'          => [],
                    'quantity'       => 0,
                    'cost_prices'    => [],
                    'selling_prices' => [],
                    'variant_ids'    => [],
                ];
            }

            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty < 1) {
                continue;
            }

            $size = trim((string) ($row['size_value'] ?? ''));
            if ($size !== '') {
                $groups[$key]['sizes'][$size] = true;
            }

            $groups[$key]['quantity'] += $qty;
            $groups[$key]['cost_prices'][]    = (float) ($row['cost_price'] ?? 0);
            $groups[$key]['selling_prices'][] = (float) ($row['selling_price'] ?? 0);
            $groups[$key]['variant_ids'][(int) ($row['variant_id'] ?? 0)] = true;
        }

        $aggregated = [];

        foreach ($groups as $group) {
            $sizes = array_keys($group['sizes']);
            usort($sizes, static function (string $a, string $b): int {
                $numA = is_numeric($a) ? (float) $a : null;
                $numB = is_numeric($b) ? (float) $b : null;
                if ($numA !== null && $numB !== null) {
                    return $numA <=> $numB;
                }

                return strnatcasecmp($a, $b);
            });

            $costPrices    = $group['cost_prices'];
            $sellingPrices = $group['selling_prices'];
            $variantIds    = array_values(array_filter(array_map('intval', array_keys($group['variant_ids']))));

            $aggregated[] = [
                'product_id'     => $group['product_id'],
                'warehouse_id'   => $group['warehouse_id'],
                'product_name'   => $group['product_name'],
                'product_number' => $group['product_number'],
                'brand'          => $group['brand'],
                'style'          => $group['style'],
                'department'     => $group['department'],
                'gender'         => $group['gender'],
                'season'         => $group['season'],
                'warehouse_name' => $group['warehouse_name'],
                'sizes'          => implode(', ', $sizes),
                'quantity'       => $group['quantity'],
                'cost_price'     => $costPrices !== []
                    ? round(array_sum($costPrices) / count($costPrices), 2)
                    : 0,
                'selling_price'  => $sellingPrices !== []
                    ? round(array_sum($sellingPrices) / count($sellingPrices), 2)
                    : 0,
                'variant_ids'    => $variantIds,
            ];
        }

        usort($aggregated, static function (array $a, array $b): int {
            $nameCmp = strcasecmp((string) ($a['product_name'] ?? ''), (string) ($b['product_name'] ?? ''));
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            return strcasecmp((string) ($a['warehouse_name'] ?? ''), (string) ($b['warehouse_name'] ?? ''));
        });

        return $aggregated;
    }

    /**
     * @return list<int>
     */
    private function parseTagIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value), static fn (int $id): bool => $id > 0)));
    }
}
