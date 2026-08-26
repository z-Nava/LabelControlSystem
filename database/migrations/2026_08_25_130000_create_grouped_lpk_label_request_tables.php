<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_request_lpk_label_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')->constrained()->cascadeOnDelete();
            $table->string('label_type', 20);
            $table->string('part_number', 80);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->unique(
                ['label_request_id', 'label_type', 'part_number'],
                'uq_lpk_label_group_request_type_part',
            );
            $table->index(
                ['label_request_id', 'position'],
                'idx_lpk_label_group_request_position',
            );
            $table->index('part_number', 'idx_lpk_label_group_part_number');
        });

        Schema::create('label_request_lpk_label_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_lpk_label_group_id');
            $table->foreign('label_request_lpk_label_group_id', 'fk_lpk_label_item_group')
                ->references('id')
                ->on('label_request_lpk_label_groups')
                ->cascadeOnDelete();
            $table->string('job_number', 40);
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->index('job_number', 'idx_lpk_label_item_job_number');
            $table->index(
                ['label_request_lpk_label_group_id', 'position'],
                'idx_lpk_label_item_group_position',
            );
        });

        Schema::create('label_request_lpk_shipping_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')->constrained()->cascadeOnDelete();
            $table->string('part_number', 120);
            $table->unsignedInteger('quantity');
            $table->string('po_number', 80)->nullable();
            $table->string('destination', 120)->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->index(
                ['label_request_id', 'position'],
                'idx_lpk_shipping_group_request_position',
            );
            $table->index('part_number', 'idx_lpk_shipping_group_part_number');
        });

        Schema::create('label_request_lpk_shipping_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_lpk_shipping_group_id');
            $table->foreign('label_request_lpk_shipping_group_id', 'fk_lpk_shipping_item_group')
                ->references('id')
                ->on('label_request_lpk_shipping_groups')
                ->cascadeOnDelete();
            $table->string('job_number', 40);
            $table->string('model', 80)->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->index('job_number', 'idx_lpk_shipping_item_job_number');
            $table->index(
                ['label_request_lpk_shipping_group_id', 'position'],
                'idx_lpk_shipping_item_group_position',
            );
        });

        $this->backfillExistingLpkRequests();
    }

    public function down(): void
    {
        Schema::dropIfExists('label_request_lpk_shipping_items');
        Schema::dropIfExists('label_request_lpk_shipping_groups');
        Schema::dropIfExists('label_request_lpk_label_items');
        Schema::dropIfExists('label_request_lpk_label_groups');
    }

    private function backfillExistingLpkRequests(): void
    {
        DB::table('label_requests')
            ->where('request_kind', 'lpk')
            ->orderBy('id')
            ->chunkById(200, function ($requests): void {
                foreach ($requests as $request) {
                    $this->backfillProductionLabelGroups($request);
                    $this->backfillShippingGroups($request);
                }
            });
    }

    private function backfillProductionLabelGroups(object $request): void
    {
        $sources = [
            'serial' => DB::table('label_request_serials')
                ->where('label_request_id', $request->id)
                ->orderBy('position')
                ->get(['part_number', 'model']),
            'rating' => DB::table('label_request_ratings')
                ->where('label_request_id', $request->id)
                ->orderBy('position')
                ->get(['part_number', 'model']),
        ];

        if ($request->include_inner && filled($request->inner_part_number)) {
            $sources['inner'] = collect([(object) [
                'part_number' => $request->inner_part_number,
                'model' => $request->inner_model,
            ]]);
        }

        $position = 1;
        $now = now();

        foreach ($sources as $labelType => $items) {
            foreach ($items as $item) {
                if (! filled($item->part_number)) {
                    continue;
                }

                $groupId = DB::table('label_request_lpk_label_groups')->insertGetId([
                    'label_request_id' => $request->id,
                    'label_type' => $labelType,
                    'part_number' => strtoupper(trim((string) $item->part_number)),
                    'position' => $position++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('label_request_lpk_label_items')->insert([
                    'label_request_lpk_label_group_id' => $groupId,
                    'job_number' => strtoupper(trim((string) $request->job_number)),
                    'model' => $this->nullableUppercase($item->model),
                    'quantity' => max(1, (int) $request->quantity_requested),
                    'position' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function backfillShippingGroups(object $request): void
    {
        if (! $request->include_shipping) {
            return;
        }

        $shippingItems = DB::table('label_request_shipping_items')
            ->where('label_request_id', $request->id)
            ->orderBy('position')
            ->get(['item_reference', 'model']);

        if ($shippingItems->isEmpty() && filled($request->shipping_part_number)) {
            $shippingItems = collect([(object) [
                'item_reference' => $request->shipping_part_number,
                'model' => $request->shipping_model,
            ]]);
        }

        $now = now();

        foreach ($shippingItems as $index => $item) {
            if (! filled($item->item_reference)) {
                continue;
            }

            $groupId = DB::table('label_request_lpk_shipping_groups')->insertGetId([
                'label_request_id' => $request->id,
                'part_number' => strtoupper(trim((string) $item->item_reference)),
                'quantity' => max(1, (int) ($request->shipping_quantity ?: $request->quantity_requested)),
                'po_number' => $this->nullableUppercase($request->po_number),
                'destination' => $this->nullableUppercase($request->destination),
                'position' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('label_request_lpk_shipping_items')->insert([
                'label_request_lpk_shipping_group_id' => $groupId,
                'job_number' => strtoupper(trim((string) $request->job_number)),
                'model' => $this->nullableUppercase($item->model),
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function nullableUppercase(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
};
