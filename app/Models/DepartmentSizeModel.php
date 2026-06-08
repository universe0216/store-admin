<?php

namespace App\Models;

use App\Enums\Department;
use CodeIgniter\Database\BaseConnection;

class DepartmentSizeModel extends BaseModel
{
    protected $table            = 'department_sizes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['department', 'value', 'sort_order', 'is_active'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(?string $department = null): array
    {
        $builder = $this->builder()
            ->orderBy('department', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('value', 'ASC');

        $department = strtolower(trim((string) $department));
        if ($department !== '' && Department::isValid($department)) {
            $builder->where('department', $department);
        }

        return $builder->get()->getResultArray();
    }

    public function findDuplicate(string $department, string $value, ?int $excludeId = null): ?array
    {
        $builder = $this->builder()
            ->where('department', $department)
            ->where('value', $value);

        if ($excludeId !== null && $excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        $row = $builder->get()->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function nextSortOrder(string $department): int
    {
        $row = $this->builder()
            ->selectMax('sort_order', 'max_sort')
            ->where('department', $department)
            ->get()
            ->getRowArray();

        return ((int) ($row['max_sort'] ?? 0)) + 10;
    }

    public function isReferenced(array $row, ?BaseConnection $db = null): bool
    {
        $db = $db ?? $this->db;

        if (! $db->tableExists('product_variants') || ! $db->fieldExists('size', 'product_variants')) {
            return false;
        }

        $department = strtolower(trim((string) ($row['department'] ?? '')));
        $value      = trim((string) ($row['value'] ?? ''));

        if ($department === '' || $value === '') {
            return false;
        }

        $builder = $db->table('product_variants pv')
            ->join('products p', 'p.id = pv.product_id', 'inner')
            ->where('p.department', $department)
            ->where('pv.size', $value);

        return (int) $builder->countAllResults() > 0;
    }
}
