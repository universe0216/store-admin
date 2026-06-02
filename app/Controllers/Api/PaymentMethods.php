<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\PaymentMethodModel;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentMethods extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new PaymentMethodModel())->listAll();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new PaymentMethodModel())->find($id);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Payment method not found.']);
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

        $model = new PaymentMethodModel();
        if ($model->findByCode($validated['code']) !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Payment method code already exists.']);
        }

        $id = $model->createOne($validated);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Payment method created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new PaymentMethodModel();
        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Payment method not found.']);
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

        return $this->response->setJSON(['message' => 'Payment method updated successfully.']);
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new PaymentMethodModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Payment method not found.']);
        }

        $code = (string) ($existing['code'] ?? '');
        if ($code !== '' && $model->isReferenced($code)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Payment method cannot be deleted because it is used in purchases or sales.',
            ]);
        }

        $model->deleteOne($id);

        return $this->response->setJSON(['message' => 'Payment method deleted successfully.']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{code?: string, name: string, description: ?string, account_id: ?int, is_active: int}|ResponseInterface
     */
    private function validatePayload(array $payload, bool $isCreate): array|ResponseInterface
    {
        $data = [];

        if ($isCreate) {
            $code = strtolower(trim((string) ($payload['code'] ?? '')));
            if ($code === '') {
                return $this->response->setStatusCode(422)->setJSON(['message' => 'Payment method code is required.']);
            }
            if (! preg_match('/^[a-z][a-z0-9_]{0,49}$/', $code)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Code must start with a letter and use lowercase letters, numbers, or underscores.',
                ]);
            }
            $data['code'] = $code;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Payment method name is required.']);
        }
        if (strlen($name) > 100) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Name must be 100 characters or less.']);
        }
        $data['name'] = $name;

        $description = trim((string) ($payload['description'] ?? ''));
        $data['description'] = $description !== '' ? $description : null;

        $accountId = (int) ($payload['account_id'] ?? 0);
        if ($accountId > 0) {
            $account = (new AccountModel())->find($accountId);
            if ($account === null || (int) ($account['is_active'] ?? 0) !== 1) {
                return $this->response->setStatusCode(422)->setJSON(['message' => 'Ledger account not found or inactive.']);
            }
            $data['account_id'] = $accountId;
        } else {
            $data['account_id'] = null;
        }

        $data['is_active'] = ! empty($payload['is_active']) ? 1 : 0;

        return $data;
    }
}
