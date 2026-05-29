<?php

namespace App\Models;

class TaggingModel extends BaseModel
{
    protected $table            = 'taggings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tag_id',
        'entity_type',
        'entity_id',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * @return list<array<string, mixed>>
     */
    public function getTagsForEntity(string $entityType, int $entityId): array
    {
        return $this->select('tags.*')
            ->join('tags', 'tags.id = taggings.tag_id')
            ->where('taggings.entity_type', $entityType)
            ->where('taggings.entity_id', $entityId)
            ->orderBy('tags.name', 'ASC')
            ->findAll();
    }

    /**
     * @param list<int> $tagIds
     */
    public function syncEntityTags(string $entityType, int $entityId, array $tagIds): void
    {
        $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();

        $uniqueTagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), static fn (int $id): bool => $id > 0)));

        foreach ($uniqueTagIds as $tagId) {
            $this->createOne([
                'tag_id'      => $tagId,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
            ]);
        }
    }
}
