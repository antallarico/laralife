<?php

namespace App\Models;

use App\Enums\Unita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlimentoDispensa extends Model
{
    protected $table = 'alimenti_dispensa';

    protected $fillable = [
        'alimento_id','quantita_disponibile','unita','n_pezzi','scadenza','posizione','note',
    ];

    protected $casts = [
        'quantita_disponibile' => 'integer',
        'unita'                 => Unita::class,
        'n_pezzi'               => 'integer',
		'scadenza'             => 'date',
    ];

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }
	
}
