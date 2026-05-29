<?php

namespace App\Libraries;

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
        ?string $description = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $creditAccount = $this->paymentAccountCode($paymentMethod);
        $this->postPair(
            $db,
            $referenceNo,
            $transactionDate,
            $this->config->inventoryAccount,
            $creditAccount,
            $amount,
            $description ?? "Purchase {$referenceNo}"
        );
    }

    public function recordSale(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        float $amount,
        string $paymentMethod,
        ?string $description = null
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
            $description ?? "Sale {$referenceNo}"
        );
    }

    public function deleteByReference(BaseConnection $db, string $referenceNo): void
    {
        if ($referenceNo === '') {
            return;
        }

        $db->table('transactions')->where('reference_no', $referenceNo)->delete();
    }

    public static function toTransactionDate(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }

    private function paymentAccountCode(string $paymentMethod): string
    {
        $method = strtolower(trim($paymentMethod));

        if (in_array($method, $this->config->bankPaymentMethods, true)) {
            return $this->config->bankAccount;
        }

        return $this->config->cashAccount;
    }

    private function postPair(
        BaseConnection $db,
        string $referenceNo,
        string $transactionDate,
        string $debitAccount,
        string $creditAccount,
        float $amount,
        string $description
    ): void {
        $this->assertAccountExists($db, $debitAccount);
        $this->assertAccountExists($db, $creditAccount);

        $date = self::toTransactionDate($transactionDate);
        $now  = date('Y-m-d H:i:s');

        $db->table('transactions')->insertBatch([
            [
                'transaction_date' => $date,
                'account_code'     => $debitAccount,
                'reference_no'     => $referenceNo,
                'description'      => $description,
                'debit'            => round($amount, 2),
                'credit'           => 0.00,
                'created_at'       => $now,
            ],
            [
                'transaction_date' => $date,
                'account_code'     => $creditAccount,
                'reference_no'     => $referenceNo,
                'description'      => $description,
                'debit'            => 0.00,
                'credit'           => round($amount, 2),
                'created_at'       => $now,
            ],
        ]);
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
