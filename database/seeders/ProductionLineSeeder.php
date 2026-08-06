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
                'MXC006.1', 'MXC007', 'MXC013', 'MXC014', 'MXC015',
                'MXC016', 'MXC017', 'MXC018', 'MXC021', 'MXC022',
                'MXC023', 'MXC026', 'MXC027', 'MXC028', 'MXC029',
                'MXC030', 'MXC031', 'MXC032', 'MXC033', 'MXC034',
                'MXC034.1', 'MXC035', 'MXC036', 'MXC037', 'MXC038',
                'MXC039', 'MXC040', 'MXC044', 'MXC047', 'MXC048',
            ],
            'HIDRAULICOS' => [
                'MXH001', 'MXH002', 'MXH003', 'MXH004', 'MXHP01',
                'MXHR01', 'MXHS01',
            ],
            'EMPAQUE' => [
                'MXP001', 'MXP002', 'MXP003', 
            ],
            'BATERIAS' => [
                'MXB001', 'MXB002', 'MXB003', 'MXB004', 'MXB004-Pre Coating', 'MXB004-Coating', 'MXB004-Post Coating', 'MXB005', 'MXB005-Pre Coating', 'MXB005-Coating', 'MXB005-Post Coating', 'MXB006', 'MXB007',
            ],
            'MOTORES' => [
                'MXMLOM', 'MXMORM001', 'MXMORM002', 'MXMORR001',
                'MXMORR002', 'MXMORRA001', 'MXMORS001', 'MXMORS002',
                'MXMR002', 'MXMR003', 'MXMR005', 'MXMR006.1',
                'MXMR006.2', 'MXMR007', 'MXMR008',
                'MXMS001', 'MXMS002', 'MXMS003', 'MXMS005',
                'MXMS006', 'MXMS007', 'MXMS008', 'MXMS009',
                'MEXMI 001', 'MEXMI 002', 'MEXMI 003', 'MEXMI 004',
                'MEXMI 005', 'MEXMI 006', 'MEXMI 007',
            ],
            'MX FUEL' => [
                'MXF001', 'MXF002', 'MXF003', 'MXF004', 'MXF005', 'MXF006', 'MXF007', 'MXF008', 'MXF009',
            ],
        ];

        ProductionLine::query()
            ->whereIn('code', ['MXC010', 'MXC10.1', 'MXHP001', 'MXMS004'])
            ->update(['active' => false]);

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
