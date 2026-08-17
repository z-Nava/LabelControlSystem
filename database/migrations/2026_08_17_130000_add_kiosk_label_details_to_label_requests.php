<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('serial_part_number', 80)->nullable()->after('label_part_number');
            $table->unsignedInteger('shipping_quantity')->nullable()->after('quantity_requested');
            $table->boolean('include_inner')->default(false)->after('include_rating');

            $table->index('serial_part_number', 'idx_label_requests_serial_part_number');
        });

        Schema::create('label_request_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')
                ->constrained('label_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('part_number', 80);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['label_request_id', 'part_number'], 'uq_label_request_rating_part');
            $table->index(['label_request_id', 'position'], 'idx_label_request_rating_position');
            $table->index('part_number', 'idx_label_request_ratings_part_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_request_ratings');

        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropIndex('idx_label_requests_serial_part_number');
            $table->dropColumn([
                'include_inner',
                'shipping_quantity',
                'serial_part_number',
            ]);
        });
    }
};
