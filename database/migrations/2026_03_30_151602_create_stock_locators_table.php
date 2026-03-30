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
        Schema::create('stock_locators', function (Blueprint $table) {
            $table->id();
            $table->string('stock_locator', 40)->unique();
            $table->string('subinventory', 20);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['subinventory', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_locators');
    }
};
