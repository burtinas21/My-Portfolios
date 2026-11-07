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
        Schema::create('assistant_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('greeting', 255)->default('Hi! I\'m an AI assistant. Ask me anything about my background, skills, or projects.');
            $table->text('extra_knowledge')->nullable()->comment('Additional context: rates, availability, languages, FAQs (max 3000 chars)');
            $table->json('suggested_questions')->nullable()->comment('Array of up to 6 suggested question strings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_settings');
    }
};
