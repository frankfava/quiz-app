<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->json('answers'); // Stores question_id => response pairs
            $table->integer('score')->nullable(); // Calculated score
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_submissions');
    }
};
