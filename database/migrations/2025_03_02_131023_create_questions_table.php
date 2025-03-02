<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('question_type')->index();
            $table->string('difficulty')->nullable();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->json('correct_answer');
            $table->json('options')->nullable();  // Stores options as JSON (e.g., {"A": "Option 1", "B": "Option 2", ...})
            $table->text('content_hash')->unique()->index();
            $table->timestamps();

            // Additional index for category lookups
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
