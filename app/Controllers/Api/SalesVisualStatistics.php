<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Enums\Department;
use App\Models\SalesVisualStatisticsModel;
use CodeIgniter\HTTP\ResponseInterface;

class SalesVisualStatistics extends BaseController
{
    public function index(): ResponseInterface
    {
        $years = $this->parseYearsParam();

        $data = (new SalesVisualStatisticsModel())->getMonthlyMetrics($years, $this->parseFilters());

        return $this->response->setJSON(['data' => $data]);
    }

    /**
     * @return array{warehouse_id: int, department: string}
     */
    private function parseFilters(): array
    {
        $warehouseId = max(0, (int) ($this->request->getGet('warehouse_id') ?? 0));
        $department  = strtolower(trim((string) ($this->request->getGet('department') ?? '')));

        if ($department !== '' && ! Department::isValid($department)) {
            $department = '';
        }

        return [
            'warehouse_id' => $warehouseId,
            'department'   => $department,
        ];
    }

    /**
     * @return list<int>
     */
    private function parseYearsParam(): array
    {
        $param = $this->request->getGet('years');

        if (is_array($param)) {
            return array_values(array_filter(array_map(static fn ($y): int => (int) $y, $param)));
        }

        if (is_string($param) && trim($param) !== '') {
            return array_values(array_filter(array_map(
                static fn (string $part): int => (int) trim($part),
                explode(',', $param)
            )));
        }

        return [(int) date('Y')];
    }
}
