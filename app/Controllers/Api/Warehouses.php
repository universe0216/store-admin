<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\WarehouseModel;
use CodeIgniter\HTTP\ResponseInterface;

class Warehouses extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new WarehouseModel())->listActive();

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new WarehouseModel())->findActive($id);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Warehouse not found.']);
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
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Warehouse name is required.']);
        }

        $id = (new WarehouseModel())->createOne([
            'name'       => $name,
            'location'   => $payload['location'] ?? null,
            'can_store'  => $this->parseBool($payload['can_store'] ?? true),
            'can_sell'   => $this->parseBool($payload['can_sell'] ?? true),
            'is_deleted' => 0,
        ]);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Warehouse created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new WarehouseModel();
        if ($model->findActive($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Warehouse not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Warehouse name is required.']);
        }

        $model->updateOne($id, [
            'name'      => $name,
            'location'  => $payload['location'] ?? null,
            'can_store' => $this->parseBool($payload['can_store'] ?? false),
            'can_sell'  => $this->parseBool($payload['can_sell'] ?? false),
        ]);

        return $this->response->setJSON(['message' => 'Warehouse updated successfully.']);
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new WarehouseModel();
        if ($model->findActive($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Warehouse not found.']);
        }

        $model->softDeleteOne($id);

        return $this->response->setJSON(['message' => 'Warehouse deleted successfully.']);
    }
}
