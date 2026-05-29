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
        ];

        $table = $this->db->table('accounts');

        foreach ($accounts as $account) {
            $exists = $table->where('code', $account['code'])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $table->insert([
                'code'         => $account['code'],
                'name'         => $account['name'],
                'account_type' => $account['account_type'],
                'is_active'    => 1,
            ]);
        }
    }

    public function down(): void
    {
        $this->db->table('accounts')
            ->whereIn('code', ['1000', '1010', '1200', '4000'])
            ->delete();
    }
}
