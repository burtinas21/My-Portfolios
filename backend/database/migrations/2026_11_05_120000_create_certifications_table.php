<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('issuer', 120);
            $table->string('issuer_icon', 80)->nullable();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('credential_id', 100)->nullable();
            $table->string('credential_url', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
