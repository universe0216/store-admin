<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTagsAndTaggingsTables extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tags')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'slug' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'color' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 7,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('name');
            $this->forge->addUniqueKey('slug');
            $this->forge->createTable('tags');
        }

        if (! $this->db->tableExists('taggings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'tag_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'entity_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'entity_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['entity_type', 'entity_id']);
            $this->forge->addUniqueKey(['tag_id', 'entity_type', 'entity_id']);
            $this->forge->addForeignKey('tag_id', 'tags', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('taggings');
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('taggings')) {
            $this->forge->dropTable('taggings', true);
        }

        if ($this->db->tableExists('tags')) {
            $this->forge->dropTable('tags', true);
        }
    }
}
