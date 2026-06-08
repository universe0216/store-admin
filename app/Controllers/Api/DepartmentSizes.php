<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Enums\Department;
use App\Models\DepartmentSizeModel;
use CodeIgniter\HTTP\ResponseInterface;

class DepartmentSizes extends BaseController
{
    public function index(): ResponseInterface
    {
        $department = strtolower(trim((string) ($this->request->getGet('department') ?? '')));
        if ($department !== '' && ! Department::isValid($department)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid department.']);
        }

        $rows = (new DepartmentSizeModel())->listAll($department !== '' ? $department : null);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new DepartmentSizeModel())->find($id);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Size not found.']);
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

        $model = new DepartmentSizeModel();
        if ($model->findDuplicate($validated['department'], $validated['value']) !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'This size already exists for the department.']);
        }

        if (! isset($validated['sort_order'])) {
            $validated['sort_order'] = $model->nextSortOrder($validated['department']);
        }

        $id = $model->createOne($validated);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Size created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new DepartmentSizeModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Size not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $validated = $this->validatePayload($payload, false);
        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        if ($model->findDuplicate($validated['department'], $validated['value'], $id) !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'This size already exists for the department.']);
        }

        $model->updateOne($id, $validated);

        return $this->response->setJSON(['message' => 'Size updated successfully.']);
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new DepartmentSizeModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Size not found.']);
        }

        if ($model->isReferenced($existing)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Size cannot be deleted because it is used on product variants.',
            ]);
        }

        $model->deleteOne($id);

        return $this->response->setJSON(['message' => 'Size deleted successfully.']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{department: string, value: string, sort_order: int, is_active: int}|ResponseInterface
     */
    private function validatePayload(array $payload, bool $isCreate): array|ResponseInterface
    {
        $department = strtolower(trim((string) ($payload['department'] ?? '')));
        if ($department === '' || ! Department::isValid($department)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid department.']);
        }

        $value = trim((string) ($payload['value'] ?? ''));
        if ($value === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Size value is required.']);
        }
        if (strlen($value) > 50) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Size value must be 50 characters or less.']);
        }

        $sortOrder = (int) ($payload['sort_order'] ?? 0);

        $data = [
            'department' => $department,
            'value'      => $value,
            'is_active'  => ! empty($payload['is_active']) ? 1 : 0,
        ];

        if (! $isCreate || $sortOrder > 0) {
            $data['sort_order'] = max(0, $sortOrder);
        }

        return $data;
    }
}
