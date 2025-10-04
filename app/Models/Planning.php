<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planning extends Model
{
    protected $table = 'planning';
	public $timestamps = true;

    protected $fillable = [
		'data',
		'tipo_pasto',
		'ordine',
		'note',
		'riferibile_type',
		'riferibile_id',
		'quantita',
		'note',
		//'scadenza','modulo','riferimento_id','quantita',
	];

    protected $casts = [
        'data' => 'date',
		'quantita' => 'integer',
    ];

    public function riferibile(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'riferibile_type', 'riferibile_id');
    }
	
	/** Scope rapido per Alimentazione (riferito a AlimentoDispensa) */
    public function scopeAlimentazione($q)
    {
        return $q->where('riferibile_type', \App\Models\AlimentoDispensa::class);
    }

    /** Scope rapido per Allenamento (riferito a Lezione) */
    public function scopeAllenamento($q)
    {
        return $q->where('riferibile_type', \App\Models\Lezione::class);
    }

    // Solo per Alimentazione (quando tipo_pasto non è null)
    public function alimentiPasti(): HasMany
    {
        return $this->hasMany(AlimentoPasto::class);
    }

    /* Scopes utili */
    public function scopeDelGiorno($q, \Carbon\Carbon|string $data)
    {
        $d = $data instanceof \Carbon\Carbon ? $data->toDateString() : $data;
        return $q->whereDate('data', $d);
    }
	
	// app/Models/Planning.php
	public function getLezioneAttribute() {
    return $this->riferibile_type === \App\Models\Lezione::class ? $this->riferibile : null;
	}


    //public function scopeAlimentazione($q)
    //{
    //  return $q->whereNotNull('tipo_pasto');
    //}
}

/*
<?php

class Planning extends Model
{
    protected $table = 'planning';
	
	protected $fillable = [
        'data',
        'scadenza',
        'modulo',
        'riferimento_id',
        'ordine',
        'note',
		'pasto',
		'quantita',		
    ];


// === RELAZIONI ===

    // Per il modulo Allenamento
    public function lezione()
    {
        return $this->belongsTo(\App\Models\Lezione::class, 'riferimento_id');
    }

    // Per il modulo Alimentazione
    public function alimentoDispensa()
    {
        return $this->belongsTo(\App\Models\AlimentoDispensa::class, 'riferimento_id');
    }

    // Registrazioni reali dei pasti
    public function pasti()
    {
        return $this->hasMany(\App\Models\AlimentoPasto::class);
    }

    // === ACCESSOR ===

    
    // Accesso all'alimento tramite la relazione alla dispensa
    public function getAlimentoAttribute()
    {
        return $this->alimentoDispensa?->alimento;
    }
}
*/