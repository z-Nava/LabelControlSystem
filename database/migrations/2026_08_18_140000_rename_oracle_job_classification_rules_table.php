<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('oracle_job_classification_rules')
            && ! Schema::hasTable('master_assembly_classification_rules')
        ) {
            Schema::rename('oracle_job_classification_rules', 'master_assembly_classification_rules');
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('master_assembly_classification_rules')
            && ! Schema::hasTable('oracle_job_classification_rules')
        ) {
            Schema::rename('master_assembly_classification_rules', 'oracle_job_classification_rules');
        }
    }
};
