<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LabelSkusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['sku' => '2552-20',        'label_part_number' => '941050066'],
            ['sku' => '2505',           'label_part_number' => '941050068'],
            ['sku' => '2454',           'label_part_number' => '941050110'],
            ['sku' => '2717',           'label_part_number' => '941050234'],
            ['sku' => '2720',           'label_part_number' => '941050235'],
            ['sku' => '2721',           'label_part_number' => '941050236'],
            ['sku' => '2718',           'label_part_number' => '941050237'],
            ['sku' => '2719',           'label_part_number' => '941050238'],
            ['sku' => '2522',           'label_part_number' => '941050269'],
            ['sku' => '2723',           'label_part_number' => '941050624'],
            ['sku' => '2486',           'label_part_number' => '941050625'],
            ['sku' => '2485',           'label_part_number' => '941050626'],

            ['sku' => '2557-20 ESPECIAL','label_part_number' => '941050643'],
            ['sku' => '2558-20 ESPECIAL','label_part_number' => '941050644'],
            ['sku' => '2560-20 ESPECIAL','label_part_number' => '941050657'],

            ['sku' => '2559',           'label_part_number' => '941050658'],
            ['sku' => '2828',           'label_part_number' => '941050740'],
            ['sku' => '2566',           'label_part_number' => '941051262'],
            ['sku' => '2567',           'label_part_number' => '941051263'],
            ['sku' => '3001',           'label_part_number' => '941051343'],
            ['sku' => '2727-20C',        'label_part_number' => '941051345'],
            ['sku' => '3403',           'label_part_number' => '941051538'],
            ['sku' => '3404',           'label_part_number' => '941051539'],
            ['sku' => '3453',           'label_part_number' => '941051540'],
            ['sku' => '2564',           'label_part_number' => '941051757'],
            ['sku' => '2565',           'label_part_number' => '941051758'],
            ['sku' => '2565P-20',        'label_part_number' => '941051759'],
            ['sku' => '2910',           'label_part_number' => '941051760'],
            ['sku' => '2911',           'label_part_number' => '941051761'],
            ['sku' => '2568',           'label_part_number' => '941051803'],
            ['sku' => '2569',           'label_part_number' => '941051804'],
            ['sku' => '2832',           'label_part_number' => '941051887'],
            ['sku' => '3650',           'label_part_number' => '941052160'],
            ['sku' => '3601',           'label_part_number' => '941052226'],
            ['sku' => '3602',           'label_part_number' => '941052227'],
            ['sku' => '3651',           'label_part_number' => '941052161'],
            ['sku' => '3050',           'label_part_number' => '941052243'],
            ['sku' => '3017',           'label_part_number' => '941052544'],
            ['sku' => '3016',           'label_part_number' => '941052698'],
            ['sku' => '3034-20',         'label_part_number' => '941052908'],
            ['sku' => '3033-20',         'label_part_number' => '941052906'],
            ['sku' => '3006',           'label_part_number' => '941694001'],
        ];

        // Normaliza, agrega defaults y upsert para que sea idempotente
        $payload = array_map(function ($r) use ($now) {
            return [
                'sku'              => trim($r['sku']),
                'label_part_number'=> trim($r['label_part_number']),
                'description'      => null,
                'is_active'        => true,
                'updated_by_user_id' => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }, $rows);

        DB::table('label_skus')->upsert(
            $payload,
            ['sku'], // unique key
            ['label_part_number', 'description', 'is_active', 'updated_by_user_id', 'updated_at']
        );
    }
}