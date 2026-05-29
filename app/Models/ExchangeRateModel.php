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

    public function getLatestRate(string $baseCurrency, string $quoteCurrency): ?array
    {
        $row = $this->where('base_currency', strtoupper($baseCurrency))
            ->where('quote_currency', strtoupper($quoteCurrency))
            ->orderBy('effective_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        return is_array($row) ? $row : null;
    }

    public function saveRate(string $baseCurrency, string $quoteCurrency, float $rate, ?string $source = null): int
    {
        return $this->createOne([
            'base_currency'  => strtoupper($baseCurrency),
            'quote_currency' => strtoupper($quoteCurrency),
            'rate'           => $rate,
            'effective_at'   => date('Y-m-d H:i:s'),
            'source'         => $source ?? 'manual',
        ]);
    }
}
