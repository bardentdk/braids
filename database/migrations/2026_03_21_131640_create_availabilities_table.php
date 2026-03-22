<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes nécessaires à la table availabilities
     * pour gérer les blocages de dates en plus des créneaux récurrents.
     *
     * À placer dans : database/migrations/
     * Nom suggéré  : xxxx_add_blocking_fields_to_availabilities_table.php
     */
    public function up(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            // Colonne pour distinguer créneau récurrent / blocage
            if (! Schema::hasColumn('availabilities', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_active');
            }

            // Date spécifique (pour les blocages)
            if (! Schema::hasColumn('availabilities', 'date')) {
                $table->date('date')->nullable()->after('is_blocked');
            }

            // Journée entière bloquée ?
            if (! Schema::hasColumn('availabilities', 'full_day')) {
                $table->boolean('full_day')->default(true)->after('date');
            }

            // Motif du blocage
            if (! Schema::hasColumn('availabilities', 'reason')) {
                $table->string('reason')->nullable()->after('full_day');
            }

            // Durée des créneaux (si pas déjà présente)
            if (! Schema::hasColumn('availabilities', 'slot_duration')) {
                $table->unsignedSmallInteger('slot_duration')->default(60)->after('end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('availabilities', 'is_blocked') ? 'is_blocked' : null,
                Schema::hasColumn('availabilities', 'date')       ? 'date'       : null,
                Schema::hasColumn('availabilities', 'full_day')   ? 'full_day'   : null,
                Schema::hasColumn('availabilities', 'reason')     ? 'reason'     : null,
                Schema::hasColumn('availabilities', 'slot_duration') ? 'slot_duration' : null,
            ]));
        });
    }
};