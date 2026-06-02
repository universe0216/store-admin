<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\CurrencyModel;
use CodeIgniter\HTTP\ResponseInterface;

class Accounts extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new AccountModel())->listAll();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new AccountModel())->find($id);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Account not found.']);
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

        $model = new AccountModel();
        if ($model->findByCode($validated['code']) !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Account code already exists.']);
        }

        $validated['created_at'] = date('Y-m-d H:i:s');
        $id = $model->createOne($validated);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Account created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new AccountModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Account not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $validated = $this->validatePayload($payload, false);
        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $model->updateOne($id, $validated);

        return $this->response->setJSON(['message' => 'Account updated successfully.']);
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new AccountModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Account not found.']);
        }

        $code = (string) ($existing['code'] ?? '');
        if ($code !== '' && $model->isReferenced($code)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Account cannot be deleted because it has transactions.',
            ]);
        }

        $model->deleteOne($id);

        return $this->response->setJSON(['message' => 'Account deleted successfully.']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{code?: string, name: string, account_type: string, currency_code: string, is_active: int}|ResponseInterface
     */
    private function validatePayload(array $payload, bool $isCreate): array|ResponseInterface
    {
        $data = [];

        if ($isCreate) {
            $code = trim((string) ($payload['code'] ?? ''));
            if ($code === '') {
                return $this->response->setStatusCode(422)->setJSON(['message' => 'Account code is required.']);
            }
            if (! preg_match('/^[A-Za-z0-9_-]{1,20}$/', $code)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Account code must be 1-20 characters (letters, numbers, underscore, hyphen).',
                ]);
            }
            $data['code'] = $code;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Account name is required.']);
        }
        if (strlen($name) > 100) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Account name must be 100 characters or less.']);
        }
        $data['name'] = $name;

        $accountType = strtoupper(trim((string) ($payload['account_type'] ?? '')));
        if (! in_array($accountType, AccountModel::ACCOUNT_TYPES, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Account type must be one of: ' . implode(', ', AccountModel::ACCOUNT_TYPES) . '.',
            ]);
        }
        $data['account_type'] = $accountType;

        $currencyCode = strtoupper(trim((string) ($payload['currency_code'] ?? '')));
        if (! preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Currency code must be exactly 3 letters (e.g. USD).',
            ]);
        }
        if ((new CurrencyModel())->findByCode($currencyCode) === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Currency not found.']);
        }
        $data['currency_code'] = $currencyCode;

        $data['is_active'] = ! empty($payload['is_active']) ? 1 : 0;

        return $data;
    }
}
