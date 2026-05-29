<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CurrencyModel;
use CodeIgniter\HTTP\ResponseInterface;

class Currencies extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new CurrencyModel())->listAll();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(string $code): ResponseInterface
    {
        $row = (new CurrencyModel())->findByCode($code);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Currency not found.']);
        }

        return $this->response->setJSON(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $validated = $this->validatePayload($payload, true);
        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $model = new CurrencyModel();
        if ($model->findByCode($validated['code']) !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Currency code already exists.']);
        }

        $model->insert($validated);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Currency created successfully.',
            'data'    => ['code' => $validated['code']],
        ]);
    }

    public function update(string $code): ResponseInterface
    {
        $model = new CurrencyModel();
        if ($model->findByCode($code) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Currency not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $validated = $this->validatePayload($payload, false);
        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $model->updateOne(strtoupper($code), $validated);

        return $this->response->setJSON(['message' => 'Currency updated successfully.']);
    }

    public function delete(string $code): ResponseInterface
    {
        $model = new CurrencyModel();
        $normalized = strtoupper($code);

        if ($model->findByCode($normalized) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Currency not found.']);
        }

        if ($model->isReferenced($normalized)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Currency cannot be deleted because it is used in exchange rates.',
            ]);
        }

        $model->deleteOne($normalized);

        return $this->response->setJSON(['message' => 'Currency deleted successfully.']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{code: string, name: string, symbol: string, decimals: int}|ResponseInterface
     */
    private function validatePayload(array $payload, bool $isCreate): array|ResponseInterface
    {
        if ($isCreate) {
            $code = strtoupper(trim((string) ($payload['code'] ?? '')));
            if (! preg_match('/^[A-Z]{3}$/', $code)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Currency code must be exactly 3 letters (e.g. USD).',
                ]);
            }
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Currency name is required.']);
        }

        $symbol = trim((string) ($payload['symbol'] ?? ''));
        if ($symbol === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Currency symbol is required.']);
        }

        $decimals = (int) ($payload['decimals'] ?? 2);
        if ($decimals < 0 || $decimals > 8) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Decimals must be between 0 and 8.',
            ]);
        }

        $data = [
            'name'     => $name,
            'symbol'   => $symbol,
            'decimals' => $decimals,
        ];

        if ($isCreate) {
            $data['code'] = $code;
        }

        return $data;
    }
}
