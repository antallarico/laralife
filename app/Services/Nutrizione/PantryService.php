<?php

namespace App\Services\Nutrizione;

use App\Enums\BaseNutrizionale;
use App\Enums\Unita;
use App\Models\Alimento;
use App\Models\AlimentoDispensa;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Carbon\Carbon;


class PantryService
{
    public static function calcolaNutrienti(Alimento $alimento, float $quantita, Unita $unita): array
    {
        $base = $alimento->base_nutrizionale; // enum BaseNutrizionale
        $mult = match ($base) {
            BaseNutrizionale::G100  => $unita === Unita::G  ? ($quantita / 100.0) : throw new InvalidArgumentException('Unità attesa: grammi'),
            BaseNutrizionale::ML100 => $unita === Unita::ML ? ($quantita / 100.0) : throw new InvalidArgumentException('Unità attesa: millilitri'),
            BaseNutrizionale::UNIT  => $unita === Unita::U  ? ($quantita) : throw new InvalidArgumentException('Unità attesa: unità'),
        };

        return [
            'kcal'     => (int) round($alimento->kcal_ref    * $mult, 0),
            'carbo_g'  => (int) round($alimento->carbo_ref_g * $mult, 0),
            'prot_g'   => (int) round($alimento->prot_ref_g  * $mult, 0),
            'grassi_g' => (int) round($alimento->grassi_ref_g* $mult, 0),
        ];
    }

    /**
     * Scarica dalla dispensa con clamp a disponibilità (mai sotto zero).
     * Ritorna la quantità effettivamente scaricata.
     */
    public static function scaricaDispensa(int $alimentoId, int $quantitaRichiesta, Unita $unita): int
    {
        return DB::transaction(function () use ($alimentoId, $quantitaRichiesta, $unita) {
            /** @var AlimentoDispensa|null $stock */
            $stock = AlimentoDispensa::where('alimento_id', $alimentoId)->lockForUpdate()->first();
            if (!$stock) return 0.0;

            if ($stock->unita !== $unita) {
                throw new InvalidArgumentException("Unità dispensa ({$stock->unita->value}) diversa dal consumo ({$unita->value}).");
            }

            $scaricabile = max(0.0, (float)$stock->quantita_disponibile);
            $daScaricare = min($scaricabile, $quantitaRichiesta);
            $stock->quantita_disponibile = round($scaricabile - $daScaricare, 2);
            $stock->save();

            return $daScaricare;
        });
    }

    /** Aggiunge o crea stock in dispensa (somma). */
    public static function aggiungiStock(
		int $alimentoId,
		int $quantita,
		Unita $unita,
		?int $nPezzi = null,
		?string $posizione = null,   // NEW
		?string $scadenza = null,    // NEW (YYYY-MM-DD da input date)
		?string $note = null         // NEW
	): void
    {
        DB::transaction(function () use ($alimentoId, $quantita, $unita, $nPezzi, $posizione, $scadenza, $note) {
            $row = AlimentoDispensa::lockForUpdate()->firstOrCreate(
                ['alimento_id' => $alimentoId],
                ['quantita_disponibile' => 0, 'unita' => $unita]
            );

            if ($row->unita !== $unita) {
                throw new InvalidArgumentException("Unità dispensa ({$row->unita->value}) diversa da quella richiesta ({$unita->value}).");
            }
			
			// update quantità
			$row->quantita_disponibile = (int)$row->quantita_disponibile + max(0, $quantita);
            // opzionali
			if ($nPezzi !== null)  $row->n_pezzi = $nPezzi;
			if ($posizione !== null && $posizione !== '') $row->posizione = $posizione;
			if ($note !== null && $note !== '')           $row->note      = $note;
			if ($scadenza) {
				// normalizza a Y-m-d (input type="date" lo fornisce già così)
				$row->scadenza = Carbon::parse($scadenza)->toDateString();
			}

			$row->save();
        });
    }
}
