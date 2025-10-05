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
        Schema::table('alimenti_dispensa', function (Blueprint $table) {
            // Aggiungi campo per gestire pezzi aperti/parziali
            $table->unsignedInteger('quantita_parziale')
                ->default(0)
                ->after('quantita_disponibile')
                ->comment('Quantità nel pezzo aperto (es. vasetto/pacco iniziato)');
        });

        // Nota: quantita_disponibile diventa "quantità nei pezzi CHIUSI"
        // Totale effettivo = quantita_parziale + quantita_disponibile
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alimenti_dispensa', function (Blueprint $table) {
            $table->dropColumn('quantita_parziale');
        });
    }
};