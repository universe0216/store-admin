<?php

namespace App\Models;

class CurrencyModel extends BaseModel
{
    protected $table            = 'currencies';
    protected $primaryKey       = 'code';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['code', 'name', 'symbol', 'decimals'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = false;

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
        $row = $this->find(strtoupper($code));

        return is_array($row) ? $row : null;
    }

    public function isReferenced(string $code): bool
    {
        $code = strtoupper($code);

        return $this->db->table('exchange_rates')
            ->groupStart()
            ->where('base_currency', $code)
            ->orWhere('quote_currency', $code)
            ->groupEnd()
            ->countAllResults() > 0;
    }
}
