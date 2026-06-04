<?php

namespace App\Models;

class AccountModel extends BaseModel
{
    protected $table            = 'accounts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['code', 'name', 'account_type', 'tags', 'tag', 'currency_code', 'is_active', 'created_at'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = false;

    /** @var array<string, string> */
    protected array $casts = [
        'tags' => '?json',
    ];

    /** @var list<string> */
    public const ACCOUNT_TYPES = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];

    /** Default when no tag is set. */
    public const DEFAULT_ACCOUNT_TAG = 'Other';

    /**
     * Account grouping tags (edit this list to add or rename tags).
     *
     * @var list<string>
     */
    public const ACCOUNT_TAGS = ['Capital', 'Inventory', 'Business', 'Life', 'Other'];

    public static function isValidTag(string $tag): bool
    {
        return in_array($tag, self::ACCOUNT_TAGS, true);
    }

    public static function normalizeTag(?string $tag): string
    {
        $tags = self::normalizeTags($tag !== null && $tag !== '' ? [$tag] : []);

        return $tags[0];
    }

    /**
     * @param mixed $raw
     *
     * @return list<string>
     */
    public static function normalizeTags(mixed $raw): array
    {
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed !== '' && $trimmed[0] === '[') {
                $decoded = json_decode($trimmed, true);
                $raw     = is_array($decoded) ? $decoded : [$trimmed];
            } else {
                $raw = $trimmed !== '' ? preg_split('/\s*,\s*/', $trimmed) : [];
            }
        }

        if (! is_array($raw)) {
            return [self::DEFAULT_ACCOUNT_TAG];
        }

        $tags = [];
        foreach ($raw as $item) {
            $value = trim((string) $item);
            if ($value !== '' && self::isValidTag($value) && ! in_array($value, $tags, true)) {
                $tags[] = $value;
            }
        }

        if ($tags === []) {
            return [self::DEFAULT_ACCOUNT_TAG];
        }

        sort($tags);

        return $tags;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(int $limit = 1000): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->orderBy('code', 'ASC')->findAll($limit);

        foreach ($rows as &$row) {
            $row['tags'] = self::normalizeTags($row['tags'] ?? $row['tag'] ?? null);
            unset($row['tag']);
        }
        unset($row);

        return $rows;
    }

    public function findByCode(string $code): ?array
    {
        $row = $this->where('code', $code)->first();

        return is_array($row) ? $row : null;
    }

    public function isReferenced(string $code): bool
    {
        return $this->db->table('transactions')
            ->where('account_code', $code)
            ->countAllResults() > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOne(array $data): int
    {
        $this->insert($this->mapTagsForDatabase($data));

        return (int) $this->getInsertID();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateOne(int|string $id, array $data): bool
    {
        return (bool) $this->update($id, $this->mapTagsForDatabase($data));
    }

    /**
     * Map tags payload to the column that exists on this database (tags JSON or legacy tag).
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function mapTagsForDatabase(array $data): array
    {
        if (! array_key_exists('tags', $data)) {
            return $data;
        }

        $tags = self::normalizeTags($data['tags']);
        unset($data['tags'], $data['tag']);

        if ($this->db->fieldExists('tags', $this->table)) {
            $data['tags'] = $tags;

            return $data;
        }

        if ($this->db->fieldExists('tag', $this->table)) {
            $data['tag'] = $tags[0];

            return $data;
        }

        return $data;
    }
}
