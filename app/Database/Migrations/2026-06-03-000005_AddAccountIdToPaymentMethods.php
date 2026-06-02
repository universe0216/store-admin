<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccountIdToPaymentMethods extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('payment_methods')
            || $this->db->fieldExists('account_id', 'payment_methods')) {
            return;
        }

        $this->forge->addColumn('payment_methods', [
            'account_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'description',
            ],
        ]);

        if ($this->db->tableExists('accounts')) {
            $this->forge->addForeignKey('account_id', 'accounts', 'id', 'SET NULL', 'CASCADE');
            $this->forge->processIndexes('payment_methods');
            $this->linkDefaultAccounts();
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('payment_methods')
            || ! $this->db->fieldExists('account_id', 'payment_methods')) {
            return;
        }

        if ($this->db->tableExists('accounts')) {
            $this->forge->dropForeignKey('payment_methods', 'payment_methods_account_id_foreign');
        }

        $this->forge->dropColumn('payment_methods', 'account_id');
    }

    private function linkDefaultAccounts(): void
    {
        $map = [
            'cash'          => '1000',
            'bank_transfer' => '1010',
            'card'          => '1010',
            'check'         => '1010',
        ];

        foreach ($map as $methodCode => $accountCode) {
            $account = $this->db->table('accounts')
                ->select('id')
                ->where('code', $accountCode)
                ->get()
                ->getRowArray();

            if (! is_array($account) || ! isset($account['id'])) {
                continue;
            }

            $this->db->table('payment_methods')
                ->where('code', $methodCode)
                ->where('account_id', null)
                ->update(['account_id' => (int) $account['id']]);
        }
    }
}
