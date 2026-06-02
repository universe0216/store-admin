<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedDefaultAccounts extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash', 'account_type' => 'ASSET'],
            ['code' => '1010', 'name' => 'Bank', 'account_type' => 'ASSET'],
            ['code' => '1200', 'name' => 'Inventory', 'account_type' => 'ASSET'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'account_type' => 'REVENUE'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => 'EXPENSE'],
        ];

        $table = $this->db->table('accounts');

        foreach ($accounts as $account) {
            $exists = $table->where('code', $account['code'])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $row = [
                'code'         => $account['code'],
                'name'         => $account['name'],
                'account_type' => $account['account_type'],
                'is_active'    => 1,
            ];
            if ($this->db->fieldExists('currency_code', 'accounts')) {
                $row['currency_code'] = $this->defaultCurrencyCode();
            }
            $table->insert($row);
        }
    }

    public function down(): void
    {
        $this->db->table('accounts')
            ->whereIn('code', ['1000', '1010', '1200', '4000', '5000'])
            ->delete();
    }

    private function defaultCurrencyCode(): string
    {
        if ($this->db->tableExists('currencies')
            && $this->db->table('currencies')->where('code', 'USD')->countAllResults() > 0) {
            return 'USD';
        }

        if ($this->db->tableExists('currencies')) {
            $row = $this->db->table('currencies')->select('code')->limit(1)->get()->getRowArray();
            if (is_array($row) && isset($row['code'])) {
                return (string) $row['code'];
            }
        }

        return 'USD';
    }
}
