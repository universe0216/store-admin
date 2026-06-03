<?php

namespace App\Models;

class ProductModel extends BaseModel
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'category_id',
        'brand',
        'serial_number',
        'description',
        'department',
        'gender',
        'season',
        'is_active',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
