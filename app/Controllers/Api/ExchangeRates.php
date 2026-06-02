<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CurrencyModel;
use App\Models\ExchangeRateModel;
use CodeIgniter\HTTP\ResponseInterface;

class ExchangeRates extends BaseController
{
    public function index(): ResponseInterface
    {
        $base          = 'USD';
        $quoteFilter   = strtoupper(trim((string) ($this->request->getGet('quote_currency') ?? '')));
        $historyLimit  = max(1, min(100, (int) ($this->request->getGet('limit') ?? 50)));
        $model         = new ExchangeRateModel();

        if ($quoteFilter !== '') {
            if (! preg_match('/^[A-Z]{3}$/', $quoteFilter)) {
                return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid currency code.']);
            }

            $rows = $model->where('base_currency', $base)
                ->where('quote_currency', $quoteFilter)
                ->orderBy('effective_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll($historyLimit);

            return $this->response->setJSON([
                'data' => $rows,
                'meta' => [
                    'base_currency'  => $base,
                    'quote_currency' => $quoteFilter,
                ],
            ]);
        }

        $currencies = (new CurrencyModel())->listAll();
        $latest     = [];

        foreach ($currencies as $currency) {
            $code = strtoupper((string) ($currency['code'] ?? ''));
            if ($code === '' || $code === $base) {
                continue;
            }

            $row = $model->getLatestRate($base, $code);
            $latest[] = [
                'quote_currency' => $code,
                'currency_name'  => (string) ($currency['name'] ?? ''),
                'rate'           => $row !== null ? (float) ($row['rate'] ?? 0) : null,
                'effective_at'   => $row['effective_at'] ?? null,
                'source'         => $row['source'] ?? null,
                'id'             => isset($row['id']) ? (int) $row['id'] : null,
            ];
        }

        return $this->response->setJSON([
            'data' => $latest,
            'meta' => ['base_currency' => $base],
        ]);
    }

    public function latest(string $quoteCurrency): ResponseInterface
    {
        $quote = strtoupper(trim($quoteCurrency));
        if (! preg_match('/^[A-Z]{3}$/', $quote)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid currency code.']);
        }

        if ($quote === 'USD') {
            return $this->response->setJSON([
                'data' => [
                    'base_currency'  => 'USD',
                    'quote_currency' => 'USD',
                    'rate'           => 1,
                ],
            ]);
        }

        $row = (new ExchangeRateModel())->getLatestRate('USD', $quote);

        return $this->response->setJSON(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $quote = strtoupper(trim((string) ($payload['quote_currency'] ?? '')));
        $rate  = (float) ($payload['rate'] ?? 0);

        if (! preg_match('/^[A-Z]{3}$/', $quote)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'quote_currency must be a 3-letter code.']);
        }

        if ($quote === 'USD') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Cannot set exchange rate for USD.']);
        }

        if ($rate <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Rate must be greater than 0.']);
        }

        $currencyModel = new CurrencyModel();
        if ($currencyModel->findByCode('USD') === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'USD currency must exist before saving rates.']);
        }

        if ($currencyModel->findByCode($quote) === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Currency not found.']);
        }

        $id = (new ExchangeRateModel())->saveRate('USD', $quote, $rate);
        $row = (new ExchangeRateModel())->find($id);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Exchange rate saved successfully.',
            'data'    => $row,
        ]);
    }
}
