<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lezione extends Model
{
    protected $table = 'lezioni';
	
	protected $fillable = [
    'titolo',
    'descrizione',
    'link',
    'durata',
    'tipo',
    'piattaforma',
	];

}
