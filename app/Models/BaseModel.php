<?php

namespace App\Models;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;

abstract class BaseModel extends Model
{
    /**
     * Create one record and return inserted ID.
     *
     * @param array<string, mixed> $data
     */
    public function createOne(array $data): int
    {
        $this->insert($data);

        return (int) $this->getInsertID();
    }

    /**
     * Update one record by its primary key.
     *
     * @param array<string, mixed> $data
     */
    public function updateOne(int|string $id, array $data): bool
    {
        return (bool) $this->update($id, $data);
    }

    public function deleteOne(int|string $id, bool $purge = false): bool
    {
        return (bool) $this->delete($id, $purge);
    }

    public function findOne(int|string $id): array|object|null
    {
        return $this->find($id);
    }

    public function findOneOrFail(int|string $id): array|object
    {
        $record = $this->find($id);

        if ($record === null) {
            throw PageNotFoundException::forPageNotFound('Record not found.');
        }

        return $record;
    }

    /**
     * @return list<array<string, mixed>|object>
     */
    public function allActive(?int $limit = null): array
    {
        if (! in_array('is_active', $this->allowedFields, true)) {
            return $limit === null ? $this->findAll() : $this->findAll($limit);
        }

        $builder = $this->where('is_active', 1);

        if ($limit !== null) {
            $builder = $builder->limit($limit);
        }

        /** @var list<array<string, mixed>|object> $results */
        $results = $builder->findAll();

        return $results;
    }

    /**
     * @return list<array<string, mixed>|object>
     */
    public function listBy(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this;

        foreach ($filters as $field => $value) {
            if (! is_string($field)) {
                continue;
            }

            $builder = $builder->where($field, $value);
        }

        /** @var list<array<string, mixed>|object> $results */
        $results = $builder->orderBy($this->primaryKey, 'DESC')->findAll($limit, $offset);

        return $results;
    }
}
