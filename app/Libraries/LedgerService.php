<?php

namespace App\Libraries;

use App\Models\ExchangeRateModel;
use App\Models\PaymentMethodModel;
use CodeIgniter\Database\BaseConnection;
use Config\Accounting;
use RuntimeException;

class LedgerService
{
    private Accounting $config;

    public function __construct(?Accounting $config = null)
    {
        $this->config = $config ?? config('Accounting');
    }

    public function recordPurchase(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        float $amount,
        string $paymentMethod,
        ?string $description = null,
        ?string $calculationCurrency = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $payment = $this->resolvePaymentMethod($db, $paymentMethod);
        $creditAccount   = $payment['account_code'];
        $paymentCurrency = $payment['currency_code'];
        $calcCurrency    = strtoupper(trim((string) ($calculationCurrency ?? '')));
        if ($calcCurrency === '') {
            $calcCurrency = $paymentCurrency;
        }

        $paymentAmount = $this->convertCurrency($amount, $calcCurrency, $paymentCurrency);

        $this->postPair(
            $db,
            $referenceNo,
            $transactionDate,
            $this->config->inventoryAccount,
            $creditAccount,
            $paymentAmount,
            $description ?? "Purchase {$referenceNo}",
            $paymentCurrency,
            $creditAccount
        );
    }

    public function recordPurchaseTransferFee(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        float $amount,
        string $paymentMethod,
        ?string $description = null,
        ?string $calculationCurrency = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $payment = $this->resolvePaymentMethod($db, $paymentMethod);
        $creditAccount   = $payment['account_code'];
        $paymentCurrency = $payment['currency_code'];
        $calcCurrency    = strtoupper(trim((string) ($calculationCurrency ?? '')));
        if ($calcCurrency === '') {
            $calcCurrency = $paymentCurrency;
        }

        $paymentAmount = $this->convertCurrency($amount, $calcCurrency, $paymentCurrency);

        $this->postPair(
            $db,
            $referenceNo,
            $transactionDate,
            $this->config->transferFeeAccount,
            $creditAccount,
            $paymentAmount,
            $description ?? "Purchase transfer fee {$referenceNo}",
            $paymentCurrency,
            $creditAccount
        );
    }

    public function recordSale(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        float $amount,
        string $paymentMethod,
        ?string $description = null,
        ?string $currencyCode = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $debitAccount = $this->paymentAccountCode($paymentMethod);
        $this->postPair(
            $db,
            $referenceNo,
            $transactionDate,
            $debitAccount,
            $this->config->salesRevenueAccount,
            $amount,
            $description ?? "Sale {$referenceNo}",
            $currencyCode,
            $debitAccount
        );
    }

    public function recordSaleCogs(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        float $amount,
        ?string $description = null,
        ?string $currencyCode = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $this->postPair(
            $db,
            $referenceNo,
            $transactionDate,
            $this->config->cogsAccount,
            $this->config->inventoryAccount,
            $amount,
            $description ?? "COGS {$referenceNo}",
            $currencyCode,
            $this->config->inventoryAccount
        );
    }

    /**
     * Manual expense or revenue entry against a payment method account.
     *
     * @param 'expense'|'revenue' $entryType
     */
    public function recordManualEntry(
        BaseConnection $db,
        string $entryType,
        string $accountCode,
        string $paymentMethod,
        float $amount,
        string $transactionDate,
        string $description,
        ?string $referenceNo = null,
        ?float $exchangeRateOverride = null
    ): string {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than 0.');
        }

        $entryType = strtolower(trim($entryType));
        if (! in_array($entryType, ['expense', 'revenue'], true)) {
            throw new RuntimeException('Entry type must be expense or revenue.');
        }

        $accountCode = trim($accountCode);
        $payment     = $this->resolvePaymentMethod($db, $paymentMethod);
        $paymentAccount  = $payment['account_code'];
        $paymentCurrency = $payment['currency_code'];
        $referenceNo = $referenceNo !== null && trim($referenceNo) !== ''
            ? trim($referenceNo)
            : $this->generateManualReference($db);

        $desc = trim($description) !== '' ? trim($description) : ucfirst($entryType) . ' ' . $referenceNo;

        if ($entryType === 'expense') {
            $this->postPair(
                $db,
                $referenceNo,
                $transactionDate,
                $accountCode,
                $paymentAccount,
                $amount,
                $desc,
                $paymentCurrency,
                $paymentAccount,
                $exchangeRateOverride
            );
        } else {
            $this->postPair(
                $db,
                $referenceNo,
                $transactionDate,
                $paymentAccount,
                $accountCode,
                $amount,
                $desc,
                $paymentCurrency,
                $paymentAccount,
                $exchangeRateOverride
            );
        }

        return $referenceNo;
    }

    /**
     * Transfer between two payment-method cash accounts (optional cross-currency).
     */
    public function recordSwap(
        BaseConnection $db,
        string $fromPaymentMethod,
        string $toPaymentMethod,
        float $fromAmount,
        float $toAmount,
        string $transactionDate,
        string $description,
        ?string $referenceNo = null,
        ?float $fromExchangeRateOverride = null,
        ?float $toExchangeRateOverride = null
    ): string {
        if ($fromAmount <= 0 || $toAmount <= 0) {
            throw new RuntimeException('Amounts must be greater than 0.');
        }

        $from = $this->resolvePaymentMethod($db, $fromPaymentMethod);
        $to   = $this->resolvePaymentMethod($db, $toPaymentMethod);

        if ($from['account_code'] === $to['account_code']) {
            throw new RuntimeException('From and to accounts must be different.');
        }

        $referenceNo = $referenceNo !== null && trim($referenceNo) !== ''
            ? trim($referenceNo)
            : $this->generateSwapReference($db);

        $desc = trim($description) !== ''
            ? trim($description)
            : 'Swap ' . $referenceNo;

        $this->postCashTransfer(
            $db,
            $referenceNo,
            $transactionDate,
            $desc,
            $from['account_code'],
            $fromAmount,
            $from['currency_code'],
            $fromExchangeRateOverride,
            $to['account_code'],
            $toAmount,
            $to['currency_code'],
            $toExchangeRateOverride
        );

        return $referenceNo;
    }

    public function deleteByReference(BaseConnection $db, string $referenceNo): void
    {
        if ($referenceNo === '') {
            return;
        }

        $db->table('transactions')->where('reference_no', $referenceNo)->delete();
    }

    private function generateManualReference(BaseConnection $db): string
    {
        $prefix = 'JE-' . date('Ymd') . '-';
        $last   = $db->table('transactions')
            ->select('reference_no')
            ->like('reference_no', $prefix, 'after')
            ->orderBy('reference_no', 'DESC')
            ->limit(1)
            ->get()
            ->getFirstRow('array');

        $seq = 1;
        if (is_array($last) && isset($last['reference_no'])) {
            $tail = substr((string) $last['reference_no'], strlen($prefix));
            $seq  = max(1, (int) $tail) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function generateSwapReference(BaseConnection $db): string
    {
        $prefix = 'SW-' . date('Ymd') . '-';
        $last   = $db->table('transactions')
            ->select('reference_no')
            ->like('reference_no', $prefix, 'after')
            ->orderBy('reference_no', 'DESC')
            ->limit(1)
            ->get()
            ->getFirstRow('array');

        $seq = 1;
        if (is_array($last) && isset($last['reference_no'])) {
            $tail = substr((string) $last['reference_no'], strlen($prefix));
            $seq  = max(1, (int) $tail) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function postCashTransfer(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        string $description,
        string $fromAccount,
        float $fromAmount,
        string $fromCurrency,
        ?float $fromRateOverride,
        string $toAccount,
        float $toAmount,
        string $toCurrency,
        ?float $toRateOverride
    ): void {
        $this->assertAccountExists($db, $fromAccount);
        $this->assertAccountExists($db, $toAccount);

        $fromMonetary = $this->resolveMonetary($db, $fromAmount, $fromCurrency, $fromAccount, $fromRateOverride);
        $toMonetary   = $this->resolveMonetary($db, $toAmount, $toCurrency, $toAccount, $toRateOverride);

        $date = self::toTransactionDate($transactionDate);
        $now  = date('Y-m-d H:i:s');

        $db->table('transactions')->insertBatch([
            $this->transactionRow(
                $date,
                $toAccount,
                $referenceNo,
                $description,
                $toMonetary['usd_amount'],
                0.00,
                $toMonetary,
                $now
            ),
            $this->transactionRow(
                $date,
                $fromAccount,
                $referenceNo,
                $description,
                0.00,
                $fromMonetary['usd_amount'],
                $fromMonetary,
                $now
            ),
        ]);
    }

    public static function toTransactionDate(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }

    /**
     * @return array{account_code: string, currency_code: string}
     */
    private function resolvePaymentMethod(BaseConnection $db, string $paymentMethod): array
    {
        $code = strtolower(trim($paymentMethod));
        $base = strtoupper($this->config->baseCurrency);

        if ($code !== '' && $db->tableExists('payment_methods')) {
            $resolved = (new PaymentMethodModel())->resolveLedgerAccount($code);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $accountCode = $this->legacyPaymentAccountCode($paymentMethod);

        return [
            'account_code'  => $accountCode,
            'currency_code' => $this->accountCurrency($db, $accountCode) ?? $base,
        ];
    }

    private function legacyPaymentAccountCode(string $paymentMethod): string
    {
        $method = strtolower(trim($paymentMethod));

        if (in_array($method, $this->config->bankPaymentMethods, true)) {
            return $this->config->bankAccount;
        }

        return $this->config->cashAccount;
    }

    private function paymentAccountCode(string $paymentMethod): string
    {
        return $this->resolvePaymentMethod(db_connect(), $paymentMethod)['account_code'];
    }

    private function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $base = strtoupper($this->config->baseCurrency);
        $from = strtoupper(trim($fromCurrency));
        $to   = strtoupper(trim($toCurrency));

        if ($from === '' || $to === '' || $from === $to) {
            return round($amount, 2);
        }

        $usd = $from === $base
            ? $amount
            : $amount / $this->exchangeRateFor($from);

        if ($to === $base) {
            return round($usd, 2);
        }

        return round($usd * $this->exchangeRateFor($to), 2);
    }

    private function postPair(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        string $debitAccount,
        string $creditAccount,
        float $originalAmount,
        string $description,
        ?string $currencyCode,
        ?string $currencyFallbackAccount,
        ?float $exchangeRateOverride = null
    ): void {
        $this->assertAccountExists($db, $debitAccount);
        $this->assertAccountExists($db, $creditAccount);

        $monetary = $this->resolveMonetary(
            $db,
            $originalAmount,
            $currencyCode,
            $currencyFallbackAccount,
            $exchangeRateOverride
        );
        $usdAmount = $monetary['usd_amount'];

        $date = self::toTransactionDate($transactionDate);
        $now  = date('Y-m-d H:i:s');

        $db->table('transactions')->insertBatch([
            $this->transactionRow(
                $date,
                $debitAccount,
                $referenceNo,
                $description,
                $usdAmount,
                0.00,
                $monetary,
                $now
            ),
            $this->transactionRow(
                $date,
                $creditAccount,
                $referenceNo,
                $description,
                0.00,
                $usdAmount,
                $monetary,
                $now
            ),
        ]);
    }

    /**
     * @param array{original_amount: float, currency: string, exchange_rate: float, usd_amount: float} $monetary
     *
     * @return array<string, mixed>
     */
    private function transactionRow(
        string $date,
        string $accountCode,
        string $referenceNo,
        string $description,
        float $debit,
        float $credit,
        array $monetary,
        string $createdAt
    ): array {
        return [
            'transaction_date' => $date,
            'account_code'     => $accountCode,
            'reference_no'     => $referenceNo,
            'description'      => $description,
            'debit'            => $debit,
            'credit'           => $credit,
            'original_amount'  => $monetary['original_amount'],
            'currency'         => $monetary['currency'],
            'exchange_rate'    => $monetary['exchange_rate'],
            'created_at'       => $createdAt,
        ];
    }

    /**
     * @return array{original_amount: float, currency: string, exchange_rate: float, usd_amount: float}
     */
    private function resolveMonetary(
        BaseConnection $db,
        float $originalAmount,
        ?string $currencyCode,
        ?string $fallbackAccountCode,
        ?float $exchangeRateOverride = null
    ): array {
        $base = strtoupper($this->config->baseCurrency);
        $currency = strtoupper(trim((string) ($currencyCode ?? '')));

        if ($currency === '') {
            $fromAccount = $fallbackAccountCode !== null && $fallbackAccountCode !== ''
                ? $this->accountCurrency($db, $fallbackAccountCode)
                : null;
            $currency = $fromAccount !== null && $fromAccount !== '' ? $fromAccount : $base;
        }

        $rate = $exchangeRateOverride !== null && $exchangeRateOverride > 0
            ? $exchangeRateOverride
            : $this->exchangeRateFor($currency);
        $usdAmount = $currency === $base
            ? round($originalAmount, 2)
            : round($originalAmount / $rate, 2);

        return [
            'original_amount' => round($originalAmount, 2),
            'currency'        => $currency,
            'exchange_rate'   => $rate,
            'usd_amount'      => $usdAmount,
        ];
    }

    private function accountCurrency(BaseConnection $db, string $accountCode): ?string
    {
        if (! $db->fieldExists('currency_code', 'accounts')) {
            return null;
        }

        $row = $db->table('accounts')
            ->select('currency_code')
            ->where('code', $accountCode)
            ->get()
            ->getFirstRow('array');

        if (! is_array($row)) {
            return null;
        }

        $code = strtoupper(trim((string) ($row['currency_code'] ?? '')));

        return $code !== '' ? $code : null;
    }

    private function exchangeRateFor(string $currency): float
    {
        $base = strtoupper($this->config->baseCurrency);
        $currency = strtoupper($currency);

        if ($currency === $base) {
            return 1.0;
        }

        $row = (new ExchangeRateModel())->getLatestRate($base, $currency);
        $rate  = is_array($row) ? (float) ($row['rate'] ?? 0) : 0.0;

        if ($rate <= 0) {
            throw new RuntimeException(
                "Exchange rate for {$currency} is not configured (base {$base})."
            );
        }

        return $rate;
    }

    private function assertAccountExists(BaseConnection $db, string $accountCode): void
    {
        $row = $db->table('accounts')
            ->select('id')
            ->where('code', $accountCode)
            ->where('is_active', 1)
            ->get()
            ->getFirstRow('array');

        if (! is_array($row)) {
            throw new RuntimeException("Account code \"{$accountCode}\" is not configured or inactive.");
        }
    }
}
