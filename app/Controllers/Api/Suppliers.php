<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SupplierModel;
use CodeIgniter\HTTP\ResponseInterface;

class Suppliers extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new SupplierModel())
            ->orderBy('id', 'DESC')
            ->findAll(1000);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new SupplierModel())->find($id);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Supplier not found.']);
        }

        return $this->response->setJSON(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Supplier name is required.']);
        }

        $data = [
            'name'    => $name,
            'phone'   => $payload['phone'] ?? null,
            'email'   => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
        ];

        if (db_connect()->fieldExists('default_currency', 'suppliers')) {
            $defaultCurrency = strtoupper(trim((string) ($payload['default_currency'] ?? 'USD')));
            $data['default_currency'] = $defaultCurrency !== '' ? $defaultCurrency : 'USD';
        }

        $id = (new SupplierModel())->createOne($data);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Supplier created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new SupplierModel();
        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Supplier not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Supplier name is required.']);
        }

        $data = [
            'name'    => $name,
            'phone'   => $payload['phone'] ?? null,
            'email'   => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
        ];

        if (db_connect()->fieldExists('default_currency', 'suppliers')) {
            $defaultCurrency = strtoupper(trim((string) ($payload['default_currency'] ?? 'USD')));
            $data['default_currency'] = $defaultCurrency !== '' ? $defaultCurrency : 'USD';
        }

        $model->updateOne($id, $data);

        return $this->response->setJSON(['message' => 'Supplier updated successfully.']);
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new SupplierModel();
        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Supplier not found.']);
        }

        $model->deleteOne($id);

        return $this->response->setJSON(['message' => 'Supplier deleted successfully.']);
    }
}
