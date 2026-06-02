<?php

namespace App\Models;

class PaymentMethodModel extends BaseModel
{
    protected $table            = 'payment_methods';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['code', 'name', 'description', 'account_id', 'is_active'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(int $limit = 1000): array
    {
        if (! $this->db->fieldExists('account_id', $this->table)) {
            /** @var list<array<string, mixed>> $rows */
            $rows = $this->orderBy('name', 'ASC')->findAll($limit);

            return $rows;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->db->table($this->table . ' pm')
            ->select('pm.*, a.code AS account_code, a.name AS account_name, a.currency_code AS account_currency')
            ->join('accounts a', 'a.id = pm.account_id', 'left')
            ->orderBy('pm.name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return $rows;
    }

    public function findByCode(string $code): ?array
    {
        $row = $this->where('code', strtolower(trim($code)))->first();

        return is_array($row) ? $row : null;
    }

    /**
     * Ledger account and currency for a payment method code.
     *
     * @return array{account_code: string, currency_code: string}|null
     */
    public function resolveLedgerAccount(string $code): ?array
    {
        $method = $this->findByCode($code);
        if ($method === null || empty($method['account_id'])) {
            return null;
        }

        $account = $this->db->table('accounts')
            ->select('code, currency_code')
            ->where('id', (int) $method['account_id'])
            ->where('is_active', 1)
            ->get()
            ->getFirstRow('array');

        if (! is_array($account)) {
            return null;
        }

        $accountCode = trim((string) ($account['code'] ?? ''));
        if ($accountCode === '') {
            return null;
        }

        $base     = 'USD';
        $currency = strtoupper(trim((string) ($account['currency_code'] ?? '')));

        return [
            'account_code'   => $accountCode,
            'currency_code'  => $currency !== '' ? $currency : $base,
        ];
    }

    public function isReferenced(string $code): bool
    {
        $code = strtolower(trim($code));

        if ($this->db->tableExists('purchases')
            && $this->db->fieldExists('payment_method', 'purchases')
            && $this->db->table('purchases')->where('payment_method', $code)->countAllResults() > 0) {
            return true;
        }

        if ($this->db->tableExists('sales')
            && $this->db->fieldExists('payment_method', 'sales')
            && $this->db->table('sales')->where('payment_method', $code)->countAllResults() > 0) {
            return true;
        }

        return false;
    }
}
