<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\LedgerService;
use App\Models\AccountModel;
use App\Models\ExchangeRateModel;
use App\Models\PaymentMethodModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Accounting;
use RuntimeException;
use Throwable;

class Transactions extends BaseController
{
    public function index(): ResponseInterface
    {
        $accounting  = config(Accounting::class);
        $dateFrom    = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo      = trim((string) ($this->request->getGet('date_to') ?? ''));
        $referenceNo = trim((string) ($this->request->getGet('reference_no') ?? ''));
        $accountCodes = $this->normalizeAccountCodes($this->request->getGet('account_code'));
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
                't.debit, t.credit, t.original_amount, t.currency, t.exchange_rate, ' .
                't.created_at, a.name AS account_name, a.account_type'
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
        $accountType = strtoupper(trim((string) ($this->request->getGet('account_type') ?? '')));

        $builder = db_connect()->table('accounts')
            ->select('code, name, account_type, currency_code')
            ->where('is_active', 1);

        if ($accountType !== '' && in_array($accountType, AccountModel::ACCOUNT_TYPES, true)) {
            $builder->where('account_type', $accountType);
        }

        $rows = $builder->orderBy('code', 'ASC')->get()->getResultArray();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $entryType       = strtolower(trim((string) ($payload['entry_type'] ?? '')));
        $transactionDate = trim((string) ($payload['transaction_date'] ?? ''));
        $description     = trim((string) ($payload['description'] ?? ''));

        if (! in_array($entryType, ['expense', 'revenue', 'swap'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Entry type must be expense, revenue, or swap.',
            ]);
        }

        if ($transactionDate === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Transaction date is required.']);
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $ledger = new LedgerService();

            if ($entryType === 'swap') {
                $referenceNo = $this->createSwapTransaction($db, $ledger, $payload, $transactionDate, $description);
            } else {
                $referenceNo = $this->createExpenseRevenueTransaction(
                    $db,
                    $ledger,
                    $payload,
                    $entryType,
                    $transactionDate,
                    $description
                );
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Failed to save transaction.');
            }

            $db->transCommit();

            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Transaction created successfully.',
                'data'    => ['reference_no' => $referenceNo],
            ]);
        } catch (Throwable $e) {
            $db->transRollback();

            $status = $e instanceof RuntimeException ? 422 : 500;

            return $this->response->setStatusCode($status)->setJSON([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Failed to create transaction.',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createExpenseRevenueTransaction(
        $db,
        LedgerService $ledger,
        array $payload,
        string $entryType,
        string $transactionDate,
        string $description
    ): string {
        $accountCode   = trim((string) ($payload['account_code'] ?? ''));
        $paymentMethod = strtolower(trim((string) ($payload['payment_method'] ?? '')));
        $amount        = (float) ($payload['amount'] ?? 0);
        $exchangeRate  = (float) ($payload['exchange_rate'] ?? 0);

        if ($accountCode === '') {
            throw new RuntimeException('Account is required.');
        }
        if ($paymentMethod === '') {
            throw new RuntimeException('Payment method is required.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than 0.');
        }

        $expectedType = $entryType === 'expense' ? 'EXPENSE' : 'REVENUE';
        $account      = (new AccountModel())->findByCode($accountCode);
        if ($account === null) {
            throw new RuntimeException('Account not found.');
        }
        if ((int) ($account['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Account is inactive.');
        }
        if (strtoupper((string) ($account['account_type'] ?? '')) !== $expectedType) {
            throw new RuntimeException("Account must be of type {$expectedType} for this entry.");
        }

        $this->assertActivePaymentMethod($paymentMethod);

        return $ledger->recordManualEntry(
            $db,
            $entryType,
            $accountCode,
            $paymentMethod,
            $amount,
            $transactionDate,
            $description,
            null,
            $exchangeRate > 0 ? $exchangeRate : null
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createSwapTransaction(
        $db,
        LedgerService $ledger,
        array $payload,
        string $transactionDate,
        string $description
    ): string {
        $fromMethod = strtolower(trim((string) ($payload['from_payment_method'] ?? '')));
        $toMethod   = strtolower(trim((string) ($payload['to_payment_method'] ?? '')));
        $fromAmount = (float) ($payload['amount'] ?? 0);
        $toAmount   = (float) ($payload['to_amount'] ?? 0);
        $fromRate   = (float) ($payload['from_exchange_rate'] ?? 0);
        $toRate     = (float) ($payload['to_exchange_rate'] ?? 0);

        if ($fromMethod === '' || $toMethod === '') {
            throw new RuntimeException('From and to payment methods are required.');
        }
        if ($fromMethod === $toMethod) {
            throw new RuntimeException('From and to payment methods must be different.');
        }
        if ($fromAmount <= 0) {
            throw new RuntimeException('From amount must be greater than 0.');
        }
        if ($toAmount <= 0) {
            throw new RuntimeException('To amount must be greater than 0.');
        }

        $this->assertActivePaymentMethod($fromMethod);
        $this->assertActivePaymentMethod($toMethod);

        return $ledger->recordSwap(
            $db,
            $fromMethod,
            $toMethod,
            $fromAmount,
            $toAmount,
            $transactionDate,
            $description,
            null,
            $fromRate > 0 ? $fromRate : null,
            $toRate > 0 ? $toRate : null
        );
    }

    private function assertActivePaymentMethod(string $code): void
    {
        if (! db_connect()->tableExists('payment_methods')) {
            return;
        }

        $method = (new PaymentMethodModel())->findByCode($code);
        if ($method === null || (int) ($method['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Payment method not found or inactive.');
        }
        if (empty($method['account_id'])) {
            throw new RuntimeException('Payment method has no ledger account configured.');
        }
    }

    public function balances(): ResponseInterface
    {
        $accounting = config(Accounting::class);
        $base       = strtoupper($accounting->baseCurrency);
        $db         = db_connect();

        $select = 'a.code, a.name, a.currency_code, ' .
            '(COALESCE(SUM(t.debit), 0) - COALESCE(SUM(t.credit), 0)) AS balance_usd';

        $hasOriginal = $db->fieldExists('original_amount', 'transactions');
        if ($hasOriginal) {
            $select .= ', (COALESCE(SUM(CASE WHEN t.debit > 0 THEN t.original_amount ELSE 0 END), 0) ' .
                '- COALESCE(SUM(CASE WHEN t.credit > 0 THEN t.original_amount ELSE 0 END), 0)) AS balance_original';
        }

        $rows = $db->table('accounts a')
            ->select($select, false)
            ->join('transactions t', 't.account_code = a.code', 'left')
            ->where('a.is_active', 1)
            ->where('a.account_type', 'ASSET')
            ->where('a.code !=', $accounting->inventoryAccount)
            ->groupBy('a.code, a.name, a.currency_code')
            ->orderBy('a.code', 'ASC')
            ->get()
            ->getResultArray();

        $paymentMethodsByAccount = $this->paymentMethodsByAccountCode($db);
        $exchangeModel           = new ExchangeRateModel();

        $accounts = [];
        $totalUsd = 0.0;

        foreach ($rows as $row) {
            $code     = (string) ($row['code'] ?? '');
            $currency = strtoupper(trim((string) ($row['currency_code'] ?? '')));
            if ($currency === '') {
                $currency = $base;
            }

            $balanceUsd = round((float) ($row['balance_usd'] ?? 0), 2);
            $totalUsd  += $balanceUsd;

            if ($currency === $base) {
                $balanceOriginal = $balanceUsd;
            } elseif ($hasOriginal) {
                $balanceOriginal = round((float) ($row['balance_original'] ?? 0), 2);
                if (abs($balanceOriginal) < 0.005 && abs($balanceUsd) > 0.005) {
                    $balanceOriginal = $this->convertUsdToCurrency(
                        $balanceUsd,
                        $currency,
                        $base,
                        $exchangeModel
                    );
                }
            } else {
                $balanceOriginal = $this->convertUsdToCurrency(
                    $balanceUsd,
                    $currency,
                    $base,
                    $exchangeModel
                );
            }

            $accounts[] = [
                'code'              => $code,
                'name'              => (string) ($row['name'] ?? ''),
                'currency_code'     => $currency,
                'balance_usd'       => $balanceUsd,
                'balance_original'  => $balanceOriginal,
                'payment_methods'   => $paymentMethodsByAccount[$code] ?? [],
            ];
        }

        return $this->response->setJSON([
            'data'    => $accounts,
            'summary' => [
                'total_balance_usd' => round($totalUsd, 2),
                'account_count'     => count($accounts),
                'base_currency'     => $accounting->baseCurrency,
            ],
        ]);
    }

    private function convertUsdToCurrency(
        float $amountUsd,
        string $currency,
        string $baseCurrency,
        ExchangeRateModel $exchangeModel
    ): float {
        $currency = strtoupper($currency);
        if ($currency === $baseCurrency || abs($amountUsd) < 0.005) {
            return round($amountUsd, 2);
        }

        $row  = $exchangeModel->getLatestRate($baseCurrency, $currency);
        $rate = is_array($row) ? (float) ($row['rate'] ?? 0) : 0.0;
        if ($rate <= 0) {
            return round($amountUsd, 2);
        }

        return round($amountUsd * $rate, 2);
    }

    /**
     * @return array<string, list<string>>
     */
    private function paymentMethodsByAccountCode($db): array
    {
        if (! $db->tableExists('payment_methods') || ! $db->fieldExists('account_id', 'payment_methods')) {
            return [];
        }

        $rows = $db->table('payment_methods pm')
            ->select('pm.name, pm.code, a.code AS account_code')
            ->join('accounts a', 'a.id = pm.account_id', 'inner')
            ->where('pm.is_active', 1)
            ->orderBy('pm.name', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $accountCode = (string) ($row['account_code'] ?? '');
            if ($accountCode === '') {
                continue;
            }
            $map[$accountCode] ??= [];
            $map[$accountCode][] = (string) ($row['name'] ?? $row['code'] ?? '');
        }

        return $map;
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
        if ($accountCodes !== []) {
            $builder->whereIn('t.account_code', $accountCodes);
        }

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
