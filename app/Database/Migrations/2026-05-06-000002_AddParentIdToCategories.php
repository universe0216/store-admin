<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddParentIdToCategories extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('categories', [
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'name',
            ],
        ]);

        $this->forge->addKey('parent_id');
        $this->forge->addForeignKey('parent_id', 'categories', 'id', 'SET NULL', 'CASCADE');
        $this->forge->processIndexes('categories');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('categories', 'categories_parent_id_foreign');
        $this->forge->dropColumn('categories', 'parent_id');
    }
}
