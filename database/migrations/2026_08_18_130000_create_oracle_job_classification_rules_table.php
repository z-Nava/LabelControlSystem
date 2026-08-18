<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oracle_job_classification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('match_field', 20);
            $table->string('prefix', 30);
            $table->string('description', 160)->nullable();
            $table->boolean('allows_assembly')->default(false);
            $table->boolean('allows_packaging')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['match_field', 'prefix']);
            $table->index(['match_field', 'active']);
            $table->index(['allows_assembly', 'active']);
            $table->index(['allows_packaging', 'active']);
        });

        $now = now();
        $rules = [
            ['match_field' => 'assembly', 'prefix' => '001', 'description' => 'Ensamble y empaque', 'allows_assembly' => true, 'allows_packaging' => true],
            ['match_field' => 'assembly', 'prefix' => '018', 'description' => 'Empaque', 'allows_assembly' => false, 'allows_packaging' => true],
            ['match_field' => 'assembly', 'prefix' => '055', 'description' => 'Empaque', 'allows_assembly' => false, 'allows_packaging' => true],
            ['match_field' => 'assembly', 'prefix' => '103', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '130', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '139', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '208', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '270', 'description' => 'Ensamble y empaque', 'allows_assembly' => true, 'allows_packaging' => true],
            ['match_field' => 'assembly', 'prefix' => '290', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '291', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '299', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '399', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'assembly', 'prefix' => '770', 'description' => 'Ensamble / Subensamble', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'line', 'prefix' => 'MEXMI', 'description' => 'Motores - Moldeo', 'allows_assembly' => true, 'allows_packaging' => false],
            ['match_field' => 'line', 'prefix' => 'MXM', 'description' => 'Motores - Moldeo', 'allows_assembly' => true, 'allows_packaging' => false],
        ];

        DB::table('oracle_job_classification_rules')->insert(array_map(
            static fn (array $rule): array => [...$rule, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            $rules,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('oracle_job_classification_rules');
    }
};
