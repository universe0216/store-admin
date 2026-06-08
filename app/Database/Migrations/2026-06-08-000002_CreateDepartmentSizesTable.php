<?php

namespace App\Database\Migrations;

use App\Enums\Department;
use CodeIgniter\Database\Migration;

class CreateDepartmentSizesTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('department_sizes')) {
            $this->seedDefaults();

            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'value' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addKey('department');
        $this->forge->addUniqueKey(['department', 'value']);
        $this->forge->createTable('department_sizes');

        $this->seedDefaults();
    }

    public function down(): void
    {
        if ($this->db->tableExists('department_sizes')) {
            $this->forge->dropTable('department_sizes', true);
        }
    }

    private function seedDefaults(): void
    {
        $defaults = [
            Department::Footwear->value => ['220', '225', '230', '235', '240', '245', '250'],
            Department::Apparel->value  => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL'],
            Department::Other->value    => ['One Size'],
        ];

        $table = $this->db->table('department_sizes');
        $now   = date('Y-m-d H:i:s');

        foreach ($defaults as $department => $values) {
            $sortOrder = 0;
            foreach ($values as $value) {
                $sortOrder += 10;
                if ($table->where('department', $department)->where('value', $value)->countAllResults() > 0) {
                    continue;
                }

                $table->insert([
                    'department' => $department,
                    'value'      => $value,
                    'sort_order' => $sortOrder,
                    'is_active'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
