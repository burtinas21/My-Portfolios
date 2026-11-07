<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table): void {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('employment_type', 30)->nullable();
            $table->string('work_mode', 20)->nullable();
            $table->string('location', 100)->nullable();
            $table->json('highlights')->nullable();
            $table->json('technologies')->nullable();
        });

        Schema::table('experiences', function (Blueprint $table): void {
            $table->string('period')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('experiences')->whereNull('period')->exists()) {
            throw new RuntimeException(
                'Cannot roll back experience details while records have a null legacy period. '
                .'Add a period to those records before retrying the rollback.'
            );
        }

        Schema::table('experiences', function (Blueprint $table): void {
            $table->string('period')->nullable(false)->change();
        });

        Schema::table('experiences', function (Blueprint $table): void {
            $table->dropColumn([
                'start_date',
                'end_date',
                'is_current',
                'employment_type',
                'work_mode',
                'location',
                'highlights',
                'technologies',
            ]);
        });
    }
};
