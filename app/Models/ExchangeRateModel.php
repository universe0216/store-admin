<?php

namespace App\Models;

class ExchangeRateModel extends BaseModel
{
    protected $table            = 'exchange_rates';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'base_currency',
        'quote_currency',
        'rate',
        'effective_at',
        'source',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
