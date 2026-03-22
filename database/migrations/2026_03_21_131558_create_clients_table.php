<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country', 100)->default('France');
            $table->date('birth_date')->nullable();
            $table->enum('hair_type', ['1a','1b','1c','2a','2b','2c','3a','3b','3c','4a','4b','4c'])->nullable();
            $table->text('allergies')->nullable();
            $table->text('notes')->nullable();           // Notes privées (admin)
            $table->integer('loyalty_points')->default(0);
            $table->string('source')->nullable();        // Comment a-t-il connu Patricia ?
            $table->boolean('newsletter')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'is_vip']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};