<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedCogsAccount extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('accounts')) {
            return;
        }

        $exists = $this->db->table('accounts')->where('code', '5000')->countAllResults() > 0;
        if ($exists) {
            return;
        }

        $row = [
            'code'         => '5000',
            'name'         => 'Cost of Goods Sold',
            'account_type' => 'EXPENSE',
            'is_active'    => 1,
        ];

        if ($this->db->fieldExists('currency_code', 'accounts')) {
            $row['currency_code'] = $this->defaultCurrencyCode();
        }

        $this->db->table('accounts')->insert($row);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('accounts')) {
            return;
        }

        $this->db->table('accounts')->where('code', '5000')->delete();
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
