<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PriceAnalyzerModel;
use CodeIgniter\HTTP\ResponseInterface;

class PriceAnalyzer extends BaseController
{
    public function index(): ResponseInterface
    {
        $warehouseId = max(0, (int) ($this->request->getGet('warehouse_id') ?? 0));

        return $this->response->setJSON([
            'data' => (new PriceAnalyzerModel())->getDepartmentBucketUnits([
                'warehouse_id' => $warehouseId,
            ]),
        ]);
    }
}
