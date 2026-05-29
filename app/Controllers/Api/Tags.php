<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TagModel;
use CodeIgniter\HTTP\ResponseInterface;

class Tags extends BaseController
{
    public function index(): ResponseInterface
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));

        $model = new TagModel();

        if ($q !== '') {
            $model->like('name', $q);
        }

        $rows = $model->orderBy('name', 'ASC')->findAll(500);

        return $this->response->setJSON(['data' => $rows]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Invalid JSON payload.']);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Tag name is required.']);
        }

        $model = new TagModel();
        $existing = $model->where('name', $name)->first();

        if (is_array($existing)) {
            return $this->response->setJSON([
                'message' => 'Tag already exists.',
                'data'    => $existing,
            ]);
        }

        $color = trim((string) ($payload['color'] ?? ''));
        $id    = $model->createOne([
            'name'  => $name,
            'slug'  => $this->makeSlug($name),
            'color' => $color !== '' ? $color : null,
        ]);

        $tag = $model->find($id);

        return $this->response->setStatusCode(201)->setJSON([
            'message' => 'Tag created successfully.',
            'data'    => $tag,
        ]);
    }

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'tag';
    }
}
