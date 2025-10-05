<?php

namespace App\Models;

use App\Enums\Unita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlimentoDispensa extends Model
{
    protected $table = 'alimenti_dispensa';

    protected $fillable = [
        'alimento_id',
        'quantita_per_pezzo',    // Quantità per SINGOLO pezzo (es. 600g per barattolo)
        'quantita_parziale',     // Quantità nel pezzo aperto
        'unita',
        'n_pezzi',              // Numero di pezzi CHIUSI
        'scadenza',
        'posizione',
        'note',
    ];

    protected $casts = [
        'quantita_per_pezzo'   => 'integer',
        'quantita_parziale'    => 'integer',
        'unita'                => Unita::class,
        'n_pezzi'              => 'integer',
        'scadenza'             => 'date',
    ];

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }

    /**
     * Accessor: Totale nei pezzi chiusi.
     * Totale chiusi = quantita_per_pezzo × n_pezzi
     */
    public function getTotaleChiusiAttribute(): int
    {
        $qPerPezzo = max(0, (int)$this->quantita_per_pezzo);
        $nPezzi = max(0, (int)$this->n_pezzi);
        
        return $qPerPezzo * $nPezzi;
    }

    /**
     * Accessor: Totale effettivo disponibile (aperto + chiusi).
     * Totale = quantita_parziale + (quantita_per_pezzo × n_pezzi)
     */
    public function getTotaleDisponibileAttribute(): int
    {
        $parziale = max(0, (int)$this->quantita_parziale);
        return $parziale + $this->totale_chiusi;
    }

    /**
     * Accessor: Ha un pezzo aperto?
     */
    public function getHaParzialeApertoAttribute(): bool
    {
        return $this->quantita_parziale > 0;
    }

    /**
     * Accessor: Descrizione leggibile dello stato.
     * Es: "570g aperto + 600g×1pz (1170g tot)"
     */
    public function getDescrizioneDisponibileAttribute(): string
    {
        $u = $this->unita instanceof \BackedEnum ? $this->unita->value : $this->unita;
        $parts = [];

        if ($this->quantita_parziale > 0) {
            $parts[] = "{$this->quantita_parziale}{$u} aperto";
        }

        if ($this->n_pezzi > 0) {
            if ($u === 'u') {
                // Per unità discrete: mostra solo totale pezzi
                $parts[] = "{$this->n_pezzi} pz chiusi";
            } else {
                // Per g/ml: mostra quantità per pezzo × numero
                $parts[] = "{$this->quantita_per_pezzo}{$u}×{$this->n_pezzi}pz";
            }
        }

        if (empty($parts)) {
            return "0{$u} (esaurito)";
        }

        $descrizione = implode(' + ', $parts);
        $totale = $this->totale_disponibile;
        
        return "{$descrizione} ({$totale}{$u} tot)";
    }
}