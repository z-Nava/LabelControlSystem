<?php

namespace Database\Seeders;

use App\Models\ProductionLine;
use Illuminate\Database\Seeder;

class ProductionLineSeeder extends Seeder
{
    /**
     * Seed production lines catalog.
     */
    public function run(): void
    {
        $linesByType = [
            'CONSOLAS' => [
                'MXC001', 'MXC002', 'MXC004', 'MXC005', 'MXC006',
                'MXC006.1', 'MXC007', 'MXC010', 'MXC10.1', 'MXC013',
                'MXC014', 'MXC015', 'MXC016', 'MXC017', 'MXC018',
                'MXC021', 'MXC022', 'MXC026', 'MXC027', 'MXC028',
                'MXC029', 'MXC030', 'MXC032', 'MXC037', 'MXC039',
                'MXC040',
            ],
            'HIDRAULICOS' => [
                'MXH001', 'MXH002', 'MXH003', 'MXH004',
            ],
            'EMPAQUE' => [
                'MXHP001', 'MXP002',
            ],
            'BATERIAS' => [
                'MXB001', 'MXB002', 'MXB003', 'MXB004', 'MXB005', 'MXB006', 'MXB007',
            ],
            'MOTORES STATOR' => [
                'MXMS001', 'MXMS002', 'MXMS003', 'MXMS004',
                'MXMS005', 'MXMS006', 'MXMS007', 'MXMS008',
            ],
            'MOTORES ROTOR' => [
                'MXMR002', 'MXMR003', 'MXMR005', 'MXMR006.1',
                'MXMR006.2', 'MXMR007', 'MXMR008',
            ],
        ];

        foreach ($linesByType as $lineType => $codes) {
            foreach ($codes as $code) {
                ProductionLine::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $code,
                        'line_type' => $lineType,
                        'active' => true,
                    ]
                );
            }
        }
    }
}
