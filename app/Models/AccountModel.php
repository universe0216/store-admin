<?php

namespace App\Models;

class AccountModel extends BaseModel
{
    protected $table            = 'accounts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['code', 'name', 'account_type', 'currency_code', 'is_active', 'created_at'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = false;

    /** @var list<string> */
    public const ACCOUNT_TYPES = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(int $limit = 1000): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->orderBy('code', 'ASC')->findAll($limit);

        return $rows;
    }

    public function findByCode(string $code): ?array
    {
        $row = $this->where('code', $code)->first();

        return is_array($row) ? $row : null;
    }

    public function isReferenced(string $code): bool
    {
        return $this->db->table('transactions')
            ->where('account_code', $code)
            ->countAllResults() > 0;
    }
}
