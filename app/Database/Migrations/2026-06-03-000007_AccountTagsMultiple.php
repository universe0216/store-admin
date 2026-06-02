<?php

namespace App\Database\Migrations;

use App\Models\AccountModel;
use CodeIgniter\Database\Migration;

class AccountTagsMultiple extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('accounts')) {
            return;
        }

        if (! $this->db->fieldExists('tags', 'accounts')) {
            $this->forge->addColumn('accounts', [
                'tags' => [
                    'type' => 'JSON',
                    'null' => true,
                    'after' => 'account_type',
                ],
            ]);
        }

        $this->migrateTagColumnToTags();

        if ($this->db->fieldExists('tag', 'accounts')) {
            $this->forge->dropColumn('accounts', 'tag');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('accounts')) {
            return;
        }

        if (! $this->db->fieldExists('tag', 'accounts')) {
            $this->forge->addColumn('accounts', [
                'tag' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => AccountModel::DEFAULT_ACCOUNT_TAG,
                    'null'       => false,
                    'after'      => 'account_type',
                ],
            ]);
        }

        if ($this->db->fieldExists('tags', 'accounts')) {
            $rows = $this->db->table('accounts')->get()->getResultArray();
            foreach ($rows as $row) {
                $tags = AccountModel::normalizeTags($row['tags'] ?? null);
                $this->db->table('accounts')
                    ->where('id', $row['id'])
                    ->update(['tag' => $tags[0] ?? AccountModel::DEFAULT_ACCOUNT_TAG]);
            }

            $this->forge->dropColumn('accounts', 'tags');
        }
    }

    private function migrateTagColumnToTags(): void
    {
        $rows = $this->db->table('accounts')->get()->getResultArray();

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }

            $existing = $row['tags'] ?? null;
            if ($existing !== null && $existing !== '') {
                $normalized = AccountModel::normalizeTags($existing);
                $this->db->table('accounts')->where('id', $id)->update([
                    'tags' => json_encode($normalized, JSON_THROW_ON_ERROR),
                ]);
                continue;
            }

            $single = trim((string) ($row['tag'] ?? ''));
            $tags   = $single !== '' ? AccountModel::normalizeTags([$single]) : [AccountModel::DEFAULT_ACCOUNT_TAG];

            $this->db->table('accounts')->where('id', $id)->update([
                'tags' => json_encode($tags, JSON_THROW_ON_ERROR),
            ]);
        }
    }
}
