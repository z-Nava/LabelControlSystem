<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            $table->foreignId('label_template_id')
                ->nullable()
                ->after('label_type')
                ->constrained('label_templates')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('media_tracking', 40)
                ->nullable()
                ->after('media_type');

        });
    }

    public function down(): void
    {
        Schema::table('label_print_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('label_template_id');
            $table->dropColumn('media_tracking');
        });
    }
};
