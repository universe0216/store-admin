<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedShippingFeeAccount extends Migration
{
    public function up(): void
    {
        $code = '5110';
        if ($this->db->table('accounts')->where('code', $code)->countAllResults() > 0) {
            return;
        }

        $row = [
            'code'         => $code,
            'name'         => 'Shipping Fee',
            'account_type' => 'EXPENSE',
            'is_active'    => 1,
        ];

        if ($this->db->fieldExists('currency_code', 'accounts')) {
            $row['currency_code'] = 'USD';
        }

        $this->db->table('accounts')->insert($row);
    }

    public function down(): void
    {
        $this->db->table('accounts')->where('code', '5110')->delete();
    }
}
