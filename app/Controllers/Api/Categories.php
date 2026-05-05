<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
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

        $parentId = (int) ($payload['parent_id'] ?? 0);
        if ($parentId > 0 && (new CategoryModel())->find($parentId) === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Parent category not found.']);
        }

        $id = (new CategoryModel())->createOne([
            'name'      => $name,
            'parent_id' => $parentId > 0 ? $parentId : null,
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

        $parentId = (int) ($payload['parent_id'] ?? 0);
        if ($parentId === $id) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Category cannot be its own parent.']);
        }
        if ($parentId > 0 && $model->find($parentId) === null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Parent category not found.']);
        }

        $model->updateOne($id, [
            'name'      => $name,
            'parent_id' => $parentId > 0 ? $parentId : null,
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
}
