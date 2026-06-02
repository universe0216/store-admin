<?php

namespace App\Database\Migrations;

use App\Models\AccountModel;
use CodeIgniter\Database\Migration;

class AddTagToAccounts extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('accounts') || $this->db->fieldExists('tag', 'accounts')) {
            return;
        }

        $this->forge->addColumn('accounts', [
            'tag' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => AccountModel::DEFAULT_ACCOUNT_TAG,
                'null'       => false,
                'after'      => 'account_type',
            ],
        ]);

        $this->backfillTags();
    }

    public function down(): void
    {
        if ($this->db->tableExists('accounts') && $this->db->fieldExists('tag', 'accounts')) {
            $this->forge->dropColumn('accounts', 'tag');
        }
    }

    private function backfillTags(): void
    {
        $map = [
            '1000' => 'Capital',
            '1010' => 'Capital',
            '1200' => 'Inventory',
            '4000' => 'Business',
            '5000' => 'Business',
        ];

        foreach ($map as $code => $tag) {
            $this->db->table('accounts')
                ->where('code', $code)
                ->update(['tag' => $tag]);
        }
    }
}
