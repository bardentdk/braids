<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->enum('category', ['braids','twists','locs','natural','extensions','kids','other'])->default('braids');
            $table->integer('duration')->default(60);           // En minutes
            $table->integer('buffer_time')->default(15);        // Temps entre RDV
            $table->decimal('price', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0); // Acompte requis
            $table->boolean('deposit_required')->default(false);
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('includes')->nullable();                // Ce qui est inclus
            $table->json('requirements')->nullable();            // Prérequis client
            $table->integer('max_clients_per_slot')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('requires_consultation')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};