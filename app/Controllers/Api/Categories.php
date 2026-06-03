<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Enums\Department;
use App\Models\CategoryModel;
use CodeIgniter\HTTP\ResponseInterface;

class Categories extends BaseController
{
    public function index(): ResponseInterface
    {
        $rows = (new CategoryModel())
            ->orderBy('id', 'DESC')
            ->findAll(1000);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new CategoryModel())->find($id);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Category not found.']);
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
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Category name is required.']);
        }

        $parentId = $this->normalizeParentId($payload['parent_id'] ?? null);
        if ($parentId !== null && (new CategoryModel())->find($parentId) === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Parent category not found.']);
        }

        $department = trim((string) ($payload['department'] ?? ''));
        if (! Department::isValid($department)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid department.']);
        }

        $id = (new CategoryModel())->createOne([
            'name'       => $name,
            'parent_id'  => $parentId,
            'department' => $department,
        ]);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Category created successfully.',
            'data'    => ['id' => $id],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new CategoryModel();
        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Category not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Category name is required.']);
        }

        $parentId = $this->normalizeParentId($payload['parent_id'] ?? null);
        if ($parentId === $id) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Category cannot be its own parent.']);
        }
        if ($parentId !== null && $model->find($parentId) === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Parent category not found.']);
        }

        $department = trim((string) ($payload['department'] ?? ''));
        if (! Department::isValid($department)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Invalid department.']);
        }

        $model->updateOne($id, [
            'name'       => $name,
            'parent_id'  => $parentId,
            'department' => $department,
        ]);

        return $this->response->setJSON(['message' => 'Category updated successfully.']);
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new CategoryModel();
        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Category not found.']);
        }

        $model->deleteOne($id);

        return $this->response->setJSON(['message' => 'Category deleted successfully.']);
    }

    private function normalizeParentId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if (is_string($value) && str_starts_with($value, 'tmp-')) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $parentId = (int) $value;

        return $parentId > 0 ? $parentId : null;
    }
}
