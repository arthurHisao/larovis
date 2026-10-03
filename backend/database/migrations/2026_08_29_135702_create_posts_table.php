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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Se null, o post é global (visível para todos). Se preenchido, é restrito ao setor.
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('content');

            // Enum para categorizar a intenção da publicação
            $table->enum('type', ['official', 'casual'])->default('casual');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
