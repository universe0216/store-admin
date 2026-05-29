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
        $tagIds      = $this->parseTagIds($this->request->getGet('tag_ids'));

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
            ->where('inventory.quantity >', 0)
            ->orderBy('products.name', 'ASC');

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

        $rows = $builder->get(5000)->getResultArray();

        return $this->response->setJSON(['data' => $rows]);
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
