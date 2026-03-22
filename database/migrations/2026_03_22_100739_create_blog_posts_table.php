<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();  // Auteur (admin)

            // Contenu
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();                // Résumé court (max 300 chars)
            $table->longText('content');                        // HTML enrichi
            $table->string('cover_image')->nullable();
            $table->string('cover_image_alt')->nullable();

            // Lecture
            $table->unsignedSmallInteger('reading_time')->default(1);  // Minutes estimées

            // Statut
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);     // Article mis en avant (grande carte Bento)
            $table->boolean('is_pinned')->default(false);       // Épinglé en haut
            $table->timestamp('published_at')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_image')->nullable();             // Image Open Graph

            // IA
            $table->boolean('ai_generated')->default(false);   // Généré par IA ?
            $table->string('ai_model')->nullable();             // 'groq/llama-3.3-70b', etc.

            // Stats
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);

            // Tags (JSON simple)
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['blog_category_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};