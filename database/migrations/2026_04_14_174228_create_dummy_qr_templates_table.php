<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dummy_qr_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->enum('dummy_type', ['rmt', 'rw']);

            $table->unsignedSmallInteger('dpi')->default(203);
            $table->decimal('width_mm', 6, 2)->nullable();
            $table->decimal('height_mm', 6, 2)->nullable();

            $table->unsignedSmallInteger('qr_x')->default(30);
            $table->unsignedSmallInteger('qr_y')->default(65);
            $table->unsignedTinyInteger('qr_magnification')->default(4);
            $table->char('qr_orientation', 1)->default('N');

            $table->unsignedSmallInteger('fg_x')->default(360);
            $table->unsignedSmallInteger('fg_y')->default(70);
            $table->unsignedTinyInteger('fg_font_size')->default(40);

            $table->unsignedSmallInteger('job_x')->default(360);
            $table->unsignedSmallInteger('job_y')->default(130);
            $table->unsignedTinyInteger('job_font_size')->default(34);

            $table->unsignedSmallInteger('consecutive_x')->default(380);
            $table->unsignedSmallInteger('consecutive_y')->default(250);
            $table->unsignedTinyInteger('consecutive_font_size')->default(58);

            $table->unsignedSmallInteger('title_x')->default(20);
            $table->unsignedSmallInteger('title_y')->default(20);
            $table->unsignedTinyInteger('title_font_size')->default(44);

            $table->enum('connection_type', ['usb', 'network'])->default('usb');
            $table->string('default_printer_name', 120)->nullable();
            $table->string('default_printer_ip', 45)->nullable();

            $table->longText('zpl');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->timestamps();

            $table->index(['dummy_type', 'is_active']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dummy_qr_templates');
    }
};
