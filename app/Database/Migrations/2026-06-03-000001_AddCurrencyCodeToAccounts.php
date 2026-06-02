<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrencyCodeToAccounts extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('accounts') || $this->db->fieldExists('currency_code', 'accounts')) {
            return;
        }

        $this->forge->addColumn('accounts', [
            'currency_code' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'account_type',
            ],
        ]);

        $defaultCode = $this->resolveDefaultCurrencyCode();
        if ($defaultCode !== null) {
            $this->db->table('accounts')
                ->where('currency_code', null)
                ->update(['currency_code' => $defaultCode]);
        }

        if ($this->db->tableExists('currencies')) {
            $this->forge->addForeignKey('currency_code', 'currencies', 'code', 'RESTRICT', 'CASCADE');
            $this->forge->processIndexes('accounts');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('accounts') || ! $this->db->fieldExists('currency_code', 'accounts')) {
            return;
        }

        if ($this->db->tableExists('currencies')) {
            $this->forge->dropForeignKey('accounts', 'accounts_currency_code_foreign');
        }

        $this->forge->dropColumn('accounts', 'currency_code');
    }

    private function resolveDefaultCurrencyCode(): ?string
    {
        if (! $this->db->tableExists('currencies')) {
            return null;
        }

        $usd = $this->db->table('currencies')->where('code', 'USD')->countAllResults();
        if ($usd > 0) {
            return 'USD';
        }

        $row = $this->db->table('currencies')->select('code')->limit(1)->get()->getRowArray();

        return is_array($row) ? (string) ($row['code'] ?? '') : null;
    }
}
