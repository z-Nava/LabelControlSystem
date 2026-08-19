<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_request_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')
                ->constrained('label_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('part_number', 80);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['label_request_id', 'part_number'], 'uq_label_request_serial_part');
            $table->index(['label_request_id', 'position'], 'idx_label_request_serial_position');
            $table->index('part_number', 'idx_label_request_serials_part_number');
        });

        DB::table('label_request_serials')->insertUsing(
            ['label_request_id', 'part_number', 'position', 'created_at', 'updated_at'],
            DB::table('label_requests')
                ->select([
                    'id',
                    'serial_part_number',
                    DB::raw('1'),
                    'created_at',
                    'updated_at',
                ])
                ->whereNotNull('serial_part_number')
                ->where('serial_part_number', '<>', ''),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('label_request_serials');
    }
};
