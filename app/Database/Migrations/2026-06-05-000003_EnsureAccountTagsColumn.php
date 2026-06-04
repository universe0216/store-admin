<?php

namespace App\Database\Migrations;

use App\Models\AccountModel;
use CodeIgniter\Database\Migration;

/**
 * Idempotent: adds accounts.tags when missing (fixes "Unknown column 'tags'").
 */
class EnsureAccountTagsColumn extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('accounts')) {
            return;
        }

        if ($this->db->fieldExists('tags', 'accounts')) {
            return;
        }

        $this->forge->addColumn('accounts', [
            'tags' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'account_type',
            ],
        ]);

        $rows = $this->db->table('accounts')->get()->getResultArray();
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }

            $single = trim((string) ($row['tag'] ?? ''));
            $tags   = $single !== ''
                ? AccountModel::normalizeTags([$single])
                : [AccountModel::DEFAULT_ACCOUNT_TAG];

            $this->db->table('accounts')->where('id', $id)->update([
                'tags' => json_encode($tags, JSON_THROW_ON_ERROR),
            ]);
        }

        if ($this->db->fieldExists('tag', 'accounts')) {
            $this->forge->dropColumn('accounts', 'tag');
        }
    }

    public function down(): void
    {
        // No-op: do not drop tags once added.
    }
}
