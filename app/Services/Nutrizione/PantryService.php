<?php

namespace App\Services\Nutrizione;

use App\Enums\BaseNutrizionale;
use App\Enums\Unita;
use App\Models\Alimento;
use App\Models\AlimentoDispensa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Carbon\Carbon;

/**
 * PantryService - Gestione dispensa
 * 
 * IMPORTANTE - Significato campi:
 * - quantita_per_pezzo: quantità PER SINGOLO pezzo (es. 600g per barattolo)
 * - n_pezzi: numero di pezzi CHIUSI
 * - quantita_parziale: quantità nel pezzo APERTO
 * 
 * Totale effettivo = (quantita_per_pezzo × n_pezzi) + quantita_parziale
 */
class PantryService
{
    /**
     * Calcola i nutrienti per una quantità di alimento.
     */
    public static function calcolaNutrienti(Alimento $alimento, int $quantita, Unita $unita): array
    {
        $base = $alimento->base_nutrizionale;

        $mult = match ($base) {
            BaseNutrizionale::G100  => $unita === Unita::G  
                ? ($quantita / 100.0) 
                : throw new InvalidArgumentException("Alimento '{$alimento->nome}': base 100g richiede grammi"),
                
            BaseNutrizionale::ML100 => $unita === Unita::ML 
                ? ($quantita / 100.0) 
                : throw new InvalidArgumentException("Alimento '{$alimento->nome}': base 100ml richiede millilitri"),
                
            BaseNutrizionale::UNIT  => $unita === Unita::U  
                ? ($quantita) 
                : throw new InvalidArgumentException("Alimento '{$alimento->nome}': base unit richiede unità"),
        };

        return [
            'kcal'     => (int) round(($alimento->kcal_ref ?? 0) * $mult),
            'carbo_g'  => (int) round(($alimento->carbo_ref_g ?? 0) * $mult),
            'prot_g'   => (int) round(($alimento->prot_ref_g ?? 0) * $mult),
            'grassi_g' => (int) round(($alimento->grassi_ref_g ?? 0) * $mult),
        ];
    }

    /**
     * Scarica dalla dispensa con gestione intelligente parziali.
     * 
     * @param int $alimentoId
     * @param int $quantitaRichiesta Quantità da scaricare
     * @param Unita $unita
     * @param bool $usaParziale Se true, scala prima dal parziale aperto
     * @param bool $apriNuovo Se true, apre un nuovo pezzo (ignora parziale esistente)
     * @return array ['scaricato' => int, 'da_parziale' => int, 'da_chiusi' => int]
     */
    public static function scaricaDispensa(
        int $alimentoId, 
        int $quantitaRichiesta, 
        Unita $unita,
        bool $usaParziale = true,
        bool $apriNuovo = false
    ): array {
        return DB::transaction(function () use ($alimentoId, $quantitaRichiesta, $unita, $usaParziale, $apriNuovo) {
            
            Log::info('PantryService::scaricaDispensa', [
                'alimento_id' => $alimentoId,
                'quantita_richiesta' => $quantitaRichiesta,
                'unita' => $unita->value,
                'usa_parziale' => $usaParziale,
                'apri_nuovo' => $apriNuovo,
            ]);
            
            /** @var AlimentoDispensa|null $stock */
            $stock = AlimentoDispensa::where('alimento_id', $alimentoId)
                ->lockForUpdate()
                ->first();
            
            if (!$stock) {
                return ['scaricato' => 0, 'da_parziale' => 0, 'da_chiusi' => 0];
            }

            // Verifica unità
            if ($stock->unita !== $unita) {
                throw new InvalidArgumentException(
                    "Unità dispensa ({$stock->unita->value}) ≠ consumo ({$unita->value})"
                );
            }

            $parziale = max(0, (int)$stock->quantita_parziale);
            $qPerPezzo = max(0, (int)$stock->quantita_per_pezzo);
            $nPezzi = max(0, (int)$stock->n_pezzi);
            
            Log::info('Stato dispensa PRIMA', [
                'parziale' => $parziale,
                'q_per_pezzo' => $qPerPezzo,
                'n_pezzi' => $nPezzi,
                'totale' => $parziale + ($qPerPezzo * $nPezzi),
            ]);
            
            $rimanente = $quantitaRichiesta;
            $daParziale = 0;
            $daChiusi = 0;

            // === LOGICA DI SCARICO ===
            
            // Per TUTTE le unità (u, g, ml) usiamo la stessa logica di gestione parziali
            // perché anche per unità discrete si possono avere "confezioni aperte"
            // Es: 10 pacchi da 16 cialde → consumo 1 cialda → pacco aperto con 15 cialde
            
            if ($apriNuovo && $nPezzi > 0 && $qPerPezzo > 0) {
                // OPZIONE A: Apri nuovo pezzo (ignora parziale esistente)
                Log::info('Opzione: APRI NUOVO');
                
                // Apre UN pezzo
                $stock->n_pezzi = $nPezzi - 1;
                
                // Scala dal pezzo appena aperto
                $scaricabile = min($qPerPezzo, $rimanente);
                $stock->quantita_parziale = $qPerPezzo - $scaricabile;
                $daParziale = $scaricabile;
                
                Log::info('Aperto nuovo pezzo', [
                    'pezzo_aperto' => $qPerPezzo,
                    'consumato' => $scaricabile,
                    'rimasto_aperto' => $stock->quantita_parziale,
                ]);
                
            } elseif ($usaParziale && $parziale > 0) {
                // OPZIONE B: Usa da parziale aperto
                Log::info('Opzione: USA DA PARZIALE', ['parziale_disponibile' => $parziale]);
                
                $daParziale = min($parziale, $rimanente);
                $stock->quantita_parziale = $parziale - $daParziale;
                $rimanente -= $daParziale;
                
                Log::info('Scalato da parziale', [
                    'parziale_prima' => $parziale,
                    'consumato' => $daParziale,
                    'parziale_dopo' => $stock->quantita_parziale,
                    'rimanente' => $rimanente,
                ]);
                
                // Se serve ancora e ci sono pezzi chiusi, apri un pezzo
                if ($rimanente > 0 && $nPezzi > 0 && $qPerPezzo > 0) {
                    Log::info('Finito parziale, apro nuovo pezzo');
                    
                    $stock->n_pezzi = $nPezzi - 1;
                    
                    $daChiusi = min($qPerPezzo, $rimanente);
                    $stock->quantita_parziale = $qPerPezzo - $daChiusi;
                    
                    Log::info('Nuovo pezzo aperto', [
                        'nuovo_pezzo' => $qPerPezzo,
                        'consumato' => $daChiusi,
                        'rimasto_aperto' => $stock->quantita_parziale,
                    ]);
                }
                
            } else {
                // OPZIONE C: Scala direttamente dai chiusi (modalità legacy/fallback)
                // Apre pezzi necessari senza tracciare il parziale (raro, non raccomandato)
                Log::info('Opzione: SCALA DIRETTA DA CHIUSI');
                
                $totaleChiusi = $qPerPezzo * $nPezzi;
                $daChiusi = min($totaleChiusi, $rimanente);
                
                if ($daChiusi > 0 && $qPerPezzo > 0) {
                    // Calcola quanti pezzi servono
                    $pezziUsati = (int)ceil($daChiusi / $qPerPezzo);
                    $stock->n_pezzi = max(0, $nPezzi - $pezziUsati);
                    
                    // Per l'ultimo pezzo usato, imposta il parziale
                    if ($pezziUsati > 0) {
                        $resto = $daChiusi % $qPerPezzo;
                        if ($resto > 0) {
                            // C'è un pezzo parzialmente consumato
                            $stock->quantita_parziale = $qPerPezzo - $resto;
                        }
                    }
                }
            }
            
            $stock->save();
            
            Log::info('Stato dispensa DOPO', [
                'parziale' => $stock->quantita_parziale,
                'q_per_pezzo' => $stock->quantita_per_pezzo,
                'n_pezzi' => $stock->n_pezzi,
                'totale' => $stock->quantita_parziale + ($stock->quantita_per_pezzo * $stock->n_pezzi),
                'da_parziale' => $daParziale,
                'da_chiusi' => $daChiusi,
            ]);
            
            $totale = $daParziale + $daChiusi;
            return [
                'scaricato' => $totale,
                'da_parziale' => $daParziale,
                'da_chiusi' => $daChiusi,
            ];
        });
    }

    /**
     * Aggiunge stock in dispensa.
     * 
     * @param int $alimentoId
     * @param int $quantitaPerPezzo Quantità per SINGOLO pezzo
     * @param Unita $unita
     * @param int|null $nPezzi Numero di pezzi da aggiungere
     */
    public static function aggiungiStock(
        int $alimentoId,
        int $quantitaPerPezzo,
        Unita $unita,
        ?int $nPezzi = null,
        ?string $posizione = null,
        ?string $scadenza = null,
        ?string $note = null
    ): void {
        DB::transaction(function () use ($alimentoId, $quantitaPerPezzo, $unita, $nPezzi, $posizione, $scadenza, $note) {
            $row = AlimentoDispensa::lockForUpdate()->firstOrCreate(
                ['alimento_id' => $alimentoId],
                [
                    'quantita_per_pezzo' => 0,
                    'quantita_parziale' => 0,
                    'unita' => $unita,
                    'n_pezzi' => 0,
                ]
            );

            if ($row->unita !== $unita) {
                throw new InvalidArgumentException(
                    "Unità dispensa ({$row->unita->value}) ≠ richiesta ({$unita->value})"
                );
            }
            
            // Imposta o aggiorna quantità per pezzo
            if ($quantitaPerPezzo > 0) {
                $row->quantita_per_pezzo = $quantitaPerPezzo;
            }
            
            // Aggiunge pezzi
            if ($nPezzi !== null && $nPezzi > 0) {
                $row->n_pezzi = ((int)$row->n_pezzi ?? 0) + $nPezzi;
            }
            
            if ($posizione !== null && $posizione !== '') {
                $row->posizione = $posizione;
            }
            if ($note !== null && $note !== '') {
                $row->note = $note;
            }
            if ($scadenza) {
                $row->scadenza = Carbon::parse($scadenza)->toDateString();
            }

            $row->save();
        });
    }

    /**
     * Calcola il totale effettivo in dispensa.
     */
    public static function getTotaleDispensa(AlimentoDispensa $stock): int
    {
        $parziale = max(0, (int)$stock->quantita_parziale);
        $qPerPezzo = max(0, (int)$stock->quantita_per_pezzo);
        $nPezzi = max(0, (int)$stock->n_pezzi);
        
        return $parziale + ($qPerPezzo * $nPezzi);
    }

    /**
     * Chiude il parziale aperto (lo considera finito).
     */
    public static function chiudiParziale(int $dispensaId): void
    {
        $row = AlimentoDispensa::findOrFail($dispensaId);
        $row->quantita_parziale = 0;
        $row->save();
    }
}