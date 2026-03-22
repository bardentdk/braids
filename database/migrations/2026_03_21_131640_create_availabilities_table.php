<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();

            // Récurrence hebdomadaire (null si date spécifique)
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Dim, 1=Lun ... 6=Sam

            // Date spécifique (override la récurrence)
            $table->date('specific_date')->nullable();

            $table->time('start_time');
            $table->time('end_time');

            // Blocages (congés, indisponibilités)
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason')->nullable();

            // Nombre max de RDV sur ce créneau
            $table->integer('max_appointments')->default(1);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['day_of_week', 'is_active', 'is_blocked']);
            $table->index(['specific_date', 'is_blocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};