<?php

namespace App\Http\Controllers\Alimentazione;

use App\Http\Controllers\Controller;
use App\Models\Alimento;
use App\Models\AlimentoDispensa;
use App\Enums\Unita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportDispensaController extends Controller
{
    public function form()
    {
        return view('alimentazione.dispensa.import');
    }

    public function store(Request $request)
    {
        if (!$request->hasFile('file')) {
            return back()->with('err','Seleziona un file.')->withInput();
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        // Leggi righe: supporta CSV nativamente; per XLSX usa PhpSpreadsheet se presente.
        $rows = [];
        if ($ext === 'csv') {
            $rows = $this->readCsv($file->getRealPath());
        } else {
            // XLSX/XLS tramite PhpSpreadsheet (composer require phpoffice/phpspreadsheet)
            try {
                if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                    throw new \RuntimeException('Per .xlsx installa: composer require phpoffice/phpspreadsheet');
                }
                $rows = $this->readXlsx($file->getRealPath());
            } catch (\Throwable $e) {
                return back()->with('err', 'Errore lettura Excel: '.$e->getMessage());
            }
        }

        if (empty($rows)) {
            return back()->with('err','Nessuna riga valida nel file.');
        }

        $createdA = $updatedA = $updatedD = $createdD = 0;
        $errors   = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $r) {
                $rowNum = $i + 2; // considerando header in riga 1

                // mapping campi (case-insensitive, default safe)
                $nome   = trim((string)($r['nome'] ?? ''));
                if ($nome === '') { continue; } // riga vuota
                $marca  = trim((string)($r['marca'] ?? ''));
                $distrib = trim((string)($r['distributore'] ?? ''));
                $cat    = trim((string)($r['categoria'] ?? ''));
                $prezzo = $r['prezzo_medio'] !== '' ? (float)$r['prezzo_medio'] : null;
                $bn     = in_array(($r['base_nutrizionale'] ?? '100g'), ['100g','100ml','unit'], true) ? $r['base_nutrizionale'] : '100g';
                $up     = in_array(($r['unita_preferita'] ?? 'g'), ['g','ml','u'], true) ? $r['unita_preferita'] : 'g';

                $kcal   = (int)($r['kcal_ref'] ?? 0);
                $fat    = (int)($r['grassi_ref_g'] ?? 0);
                $prot   = (int)($r['prot_ref_g'] ?? 0);
                $carb   = (int)($r['carbo_ref_g'] ?? 0);
                $noteA  = trim((string)($r['note_alimento'] ?? ''));

                $quant  = (int)($r['quantita'] ?? 0);
                $uDisp  = in_array(($r['unita_dispensa'] ?? $up), ['g','ml','u'], true) ? $r['unita_dispensa'] : $up;
                $npezzi = isset($r['n_pezzi']) && $r['n_pezzi'] !== '' ? (int)$r['n_pezzi'] : null;
                $posiz  = trim((string)($r['posizione'] ?? 'Dispensa'));
                $scad   = trim((string)($r['scadenza'] ?? ''));
                $noteD  = trim((string)($r['note_dispensa'] ?? ''));

                // 1) Alimento upsert (match su nome+marca)
                $alimento = Alimento::where('nome', $nome)
                    ->when($marca !== '', fn($q) => $q->where('marca', $marca))
                    ->first();

                if (!$alimento) {
                    $alimento = new Alimento();
                    $createdA++;
                } else {
                    $updatedA++;
                }

                $alimento->nome              = $nome;
                $alimento->marca             = $marca !== '' ? $marca : null;
                $alimento->distributore      = $distrib !== '' ? $distrib : null;
                $alimento->categoria         = $cat !== '' ? $cat : null;
                $alimento->prezzo_medio      = $prezzo;
                $alimento->base_nutrizionale = $bn;
                $alimento->unita_preferita   = $up;
                $alimento->kcal_ref          = $kcal;
                $alimento->grassi_ref_g      = $fat;
                $alimento->prot_ref_g        = $prot;
                $alimento->carbo_ref_g       = $carb;
                if ($noteA !== '') $alimento->note = $noteA;
                $alimento->save();

                // 2) Dispensa: set (sostituzione) quantità e campi anagrafici
                if ($quant > 0) {
                    $disp = AlimentoDispensa::firstOrNew(['alimento_id' => $alimento->id]);
                    $isNew = !$disp->exists;

                    // se la riga esiste ed ha un'unità diversa, la sovrascriviamo (import imposta lo stato fedele al file)
                    $disp->unita                = $uDisp;
                    $disp->quantita_disponibile = $quant;
                    $disp->n_pezzi              = $npezzi;
                    $disp->posizione            = $posiz !== '' ? $posiz : null;
                    $disp->note                 = $noteD !== '' ? $noteD : null;
                    $disp->scadenza             = $scad !== '' ? \Carbon\Carbon::parse($scad)->toDateString() : null;
                    $disp->save();

                    $isNew ? $createdD++ : $updatedD++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('err', 'Import fallito: '.$e->getMessage());
        }

        return redirect()
            ->route('alimentazione.dispensa.index')
            ->with('ok', "Import OK — Alimenti: +$createdA / ~ $updatedA, Dispensa: +$createdD / ~ $updatedD");
    }

    private function readCsv(string $path): array
    {
        $f = fopen($path, 'r');
        if (!$f) return [];
        $header = null;
        $rows = [];
        while (($row = fgetcsv($f, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(fn($h) => strtolower(trim($h)), $row);
                continue;
            }
            if (count(array_filter($row, fn($v)=>$v!==null && $v!=='')) === 0) continue;
            $assoc = [];
            foreach ($header as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }
            $rows[] = $assoc;
        }
        fclose($f);
        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('import') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $header = null;
        $out = [];
        foreach ($rows as $r) {
            $line = array_values($r);
            if ($header === null) {
                $header = array_map(fn($h)=> strtolower(trim((string)$h)), $line);
                continue;
            }
            if (count(array_filter($line, fn($v)=>$v!==null && $v!=='')) === 0) continue;
            $assoc = [];
            foreach ($header as $i => $key) {
                $assoc[$key] = $line[$i] ?? null;
            }
            $out[] = $assoc;
        }
        return $out;
    }
}
