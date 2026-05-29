<?php

namespace App\Commands;

use App\Libraries\LedgerService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LedgerBackfill extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'ledger:backfill';
    protected $description = 'Create ledger transactions for purchases/sales missing entries.';
    protected $usage       = 'ledger:backfill';

    public function run(array $params): void
    {
        require_once APPPATH . 'Database/Migrations/2026-05-30-000005_SeedDefaultAccounts.php';
        (new \App\Database\Migrations\SeedDefaultAccounts())->up();

        $db     = db_connect();
        $ledger = new LedgerService();
        $posted = 0;
        $skipped = 0;

        $purchases = $db->table('purchases')
            ->select('purchase_no, purchase_date, grand_total, payment_method')
            ->get()
            ->getResultArray();

        foreach ($purchases as $purchase) {
            $ref = (string) ($purchase['purchase_no'] ?? '');
            if ($ref === '' || $this->hasReference($db, $ref)) {
                $skipped++;

                continue;
            }

            $amount = (float) ($purchase['grand_total'] ?? 0);
            if ($amount <= 0) {
                $skipped++;

                continue;
            }

            $ledger->recordPurchase(
                $db,
                $ref,
                (string) ($purchase['purchase_date'] ?? ''),
                $amount,
                (string) ($purchase['payment_method'] ?? 'cash'),
                'Purchase ' . $ref
            );
            $posted += 2;
            CLI::write("Purchase {$ref}", 'green');
        }

        $sales = $db->table('sales')
            ->select('sale_no, sale_date, grand_total, payment_method')
            ->get()
            ->getResultArray();

        foreach ($sales as $sale) {
            $ref = (string) ($sale['sale_no'] ?? '');
            if ($ref === '' || $this->hasReference($db, $ref)) {
                $skipped++;

                continue;
            }

            $amount = (float) ($sale['grand_total'] ?? 0);
            if ($amount <= 0) {
                $skipped++;

                continue;
            }

            $ledger->recordSale(
                $db,
                $ref,
                (string) ($sale['sale_date'] ?? ''),
                $amount,
                (string) ($sale['payment_method'] ?? 'cash'),
                'Sale ' . $ref
            );
            $posted += 2;
            CLI::write("Sale {$ref}", 'green');
        }

        CLI::write("Done. Posted {$posted} ledger line(s), skipped {$skipped} document(s).", 'yellow');
    }

    private function hasReference($db, string $referenceNo): bool
    {
        return $db->table('transactions')
            ->where('reference_no', $referenceNo)
            ->countAllResults() > 0;
    }
}
