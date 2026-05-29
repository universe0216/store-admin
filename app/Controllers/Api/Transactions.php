<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Accounting;

class Transactions extends BaseController
{
    public function index(): ResponseInterface
    {
        $accounting  = config(Accounting::class);
        $dateFrom    = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo      = trim((string) ($this->request->getGet('date_to') ?? ''));
        $referenceNo = trim((string) ($this->request->getGet('reference_no') ?? ''));
        $accountCodes = $this->resolveMoneyAccountCodes(
            $this->normalizeAccountCodes($this->request->getGet('account_code')),
            $accounting
        );
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 50)));
        $offset = ($page - 1) * $perPage;

        $db = db_connect();

        $countBuilder = $db->table('transactions t');
        $this->applyTransactionFilters($countBuilder, $dateFrom, $dateTo, $referenceNo, $accountCodes);
        $total = (int) $countBuilder->countAllResults();

        $listBuilder = $db->table('transactions t')
            ->select(
                't.id, t.transaction_date, t.account_code, t.reference_no, t.description, ' .
                't.debit, t.credit, t.created_at, a.name AS account_name, a.account_type'
            )
            ->join('accounts a', 'a.code = t.account_code', 'left');
        $this->applyTransactionFilters($listBuilder, $dateFrom, $dateTo, $referenceNo, $accountCodes);

        $rows = $listBuilder
            ->orderBy('t.transaction_date', 'DESC')
            ->orderBy('t.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $summaryBuilder = $db->table('transactions t');
        $this->applyTransactionFilters($summaryBuilder, $dateFrom, $dateTo, $referenceNo, $accountCodes);
        $totals = $summaryBuilder
            ->select('COALESCE(SUM(t.debit), 0) AS total_debit, COALESCE(SUM(t.credit), 0) AS total_credit', false)
            ->get()
            ->getRowArray();

        $balances     = $this->moneyAccountBalances($db, $accountCodes);
        $totalBalance = 0.0;
        foreach ($balances as $balance) {
            $totalBalance += (float) ($balance['balance'] ?? 0);
        }

        return $this->response->setJSON([
            'data'  => $rows,
            'meta'  => [
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
            ],
            'summary' => [
                'total_debit'   => (float) ($totals['total_debit'] ?? 0),
                'total_credit'  => (float) ($totals['total_credit'] ?? 0),
                'total_balance' => round($totalBalance, 2),
                'accounts'      => $balances,
            ],
        ]);
    }

    public function accounts(): ResponseInterface
    {
        $codes = config(Accounting::class)->moneyAccountCodes();

        $rows = db_connect()->table('accounts')
            ->select('code, name, account_type')
            ->where('is_active', 1)
            ->whereIn('code', $codes)
            ->orderBy('code', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['data' => $rows]);
    }

    /**
     * @param list<string> $selected
     *
     * @return list<string>
     */
    private function resolveMoneyAccountCodes(array $selected, Accounting $accounting): array
    {
        $moneyCodes = $accounting->moneyAccountCodes();

        if ($selected === []) {
            return $moneyCodes;
        }

        return array_values(array_intersect($selected, $moneyCodes));
    }

    /**
     * @param list<string> $accountCodes
     *
     * @return list<array{code: string, name: string, balance: float}>
     */
    private function moneyAccountBalances($db, array $accountCodes): array
    {
        if ($accountCodes === []) {
            return [];
        }

        $rows = $db->table('accounts a')
            ->select(
                'a.code, a.name, ' .
                '(COALESCE(SUM(t.debit), 0) - COALESCE(SUM(t.credit), 0)) AS balance',
                false
            )
            ->join('transactions t', 't.account_code = a.code', 'left')
            ->whereIn('a.code', $accountCodes)
            ->groupBy('a.code, a.name')
            ->orderBy('a.code', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static function (array $row): array {
            return [
                'code'    => (string) $row['code'],
                'name'    => (string) ($row['name'] ?? ''),
                'balance' => round((float) ($row['balance'] ?? 0), 2),
            ];
        }, $rows);
    }

    /**
     * @return list<string>
     */
    private function normalizeAccountCodes(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $codes = [];
        foreach ($raw as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * @param list<string> $accountCodes
     */
    private function applyTransactionFilters(
        BaseBuilder $builder,
        string $dateFrom,
        string $dateTo,
        string $referenceNo,
        array $accountCodes
    ): void {
        $builder->whereIn('t.account_code', $accountCodes);

        if ($dateFrom !== '') {
            $builder->where('t.transaction_date >=', $dateFrom);
        }
        if ($dateTo !== '') {
            $builder->where('t.transaction_date <=', $dateTo);
        }
        if ($referenceNo !== '') {
            $builder->like('t.reference_no', $referenceNo);
        }
    }
}
