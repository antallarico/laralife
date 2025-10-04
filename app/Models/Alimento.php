<?php

namespace App\Models;

use App\Enums\BaseNutrizionale;
use App\Enums\Unita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Alimento extends Model
{
	protected $table = 'alimenti';
	
    protected $fillable = [
        'nome','marca','base_nutrizionale','kcal_ref','carbo_ref_g','prot_ref_g','grassi_ref_g','unita_preferita',
		'distributore','prezzo_medio','categoria','note',
    ];
	
    protected $casts = [
        'base_nutrizionale' => BaseNutrizionale::class,
        'unita_preferita'   => Unita::class,
        'kcal_ref'          => 'integer',
        'carbo_ref_g'       => 'integer',
        'prot_ref_g'        => 'integer',
        'grassi_ref_g'      => 'integer',
    ];

    public function dispensa(): HasOne
    {
        return $this->hasOne(AlimentoDispensa::class);
    }
	
	public function getDisplayNameAttribute(): string
	{
		$nome  = (string)($this->nome ?? '');
		$marca = trim((string)($this->marca ?? ''));
		return $marca !== '' ? "{$nome} ({$marca})" : $nome;
	}

}
