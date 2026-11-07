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
    if (! Schema::hasColumn('social_links', 'icon')) {
        Schema::table('social_links', function (Blueprint $table) {
            $table->string('icon', 80)->nullable()->after('name');
        });
    }
}

public function down(): void
{
    if (Schema::hasColumn('social_links', 'icon')) {
        Schema::table('social_links', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
}
};
