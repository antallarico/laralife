<?php

namespace App\Models;

use App\Enums\Unita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlimentoPasto extends Model
{
    protected $table = 'alimenti_pasti';

    protected $fillable = [
        'planning_id','alimento_id','quantita','unita','scarica_dispensa',
        'kcal','carbo_g','prot_g','grassi_g',
		
    ];

    protected $casts = [
        'quantita'         => 'integer',
        'unita'            => Unita::class,
        'scarica_dispensa' => 'boolean',
        'kcal'             => 'integer',
        'carbo_g'          => 'integer',
        'prot_g'           => 'integer',
        'grassi_g'         => 'integer',
    ];

    public function planning(): BelongsTo
    {
        return $this->belongsTo(Planning::class);
    }

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }
}

/*
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlimentoPasto extends Model
{
    use HasFactory;

    protected $table = 'alimenti_pasti';

    protected $fillable = [
        'planning_id',
        'alimento_id',
        'tipo_pasto',
        'quantita',
        'unita_misura',
        'orario',
        'note',
        'provenienza',
    ];

    // Relazione con Planning
    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    // Relazione diretta con Alimento (se inserito senza passare dalla dispensa)
    public function alimento()
    {
        return $this->belongsTo(Alimento::class);
    }

    // Relazione con AlimentoDispensa (se serve distinguere la provenienza)
    public function alimentoDispensa()
    {
        return $this->belongsTo(AlimentoDispensa::class, 'alimento_id');
    }

    // Accessor: ritorna direttamente l’alimento, sia da alimento() che da alimentoDispensa()
    public function getAlimentoAttribute()
    {
        return $this->alimentoDispensa?->alimento ?? $this->alimento;
    }
}
*/