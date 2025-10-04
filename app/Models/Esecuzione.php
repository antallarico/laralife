<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Esecuzione extends Model
{
    protected $table = 'esecuzioni';
    protected $fillable = ['data','modulo','riferimento_id','minuti_effettivi','stato','note'];
	
	public function planning()
	{
		return $this->belongsTo(\App\Models\Planning::class);
	}
	
	public function lezione()
{
    return $this->belongsTo(\App\Models\Lezione::class, 'riferimento_id');
}


}
