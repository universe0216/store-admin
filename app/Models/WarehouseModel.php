<?php

namespace App\Models;

class WarehouseModel extends BaseModel
{
    protected $table            = 'warehouses';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'location', 'can_store', 'can_sell', 'is_deleted'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function listActive(int $limit = 1000): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->where('is_deleted', 0)
            ->orderBy('name', 'ASC')
            ->findAll($limit);

        return $rows;
    }

    public function findActive(int $id): ?array
    {
        $row = $this->where('is_deleted', 0)->find($id);

        return is_array($row) ? $row : null;
    }

    public function softDeleteOne(int|string $id): bool
    {
        return $this->updateOne((int) $id, ['is_deleted' => 1]);
    }
}
