<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
//use App\Http\Controllers\Alimentazione\PastoController;
use App\Http\Controllers\Alimentazione\PastiController;
use App\Http\Controllers\Alimentazione\DispensaController;
use App\Http\Controllers\Alimentazione\AlimentiController;
use App\Http\Controllers\Alimentazione\ImportDispensaController;

use App\Http\Controllers\Alimentazione\PlannerController as PlannerAlim;
use App\Http\Controllers\Allenamento\PlannerController as PlannerAll;

Route::prefix('alimentazione')->name('planner.alimentazione.')->group(function () {
    Route::get('planner/{offset?}', [PlannerAlim::class, 'index'])->name('index')->whereNumber('offset');
    Route::post('planner',         [PlannerAlim::class, 'store'])->name('store');
	Route::patch('/qty/{id}', [PlannerAlim::class, 'updateQuantita'])->name('updateQty');
    Route::post('planner/reorder', [PlannerAlim::class, 'reorder'])->name('reorder');     
    Route::delete('planner/{id}',  [PlannerAlim::class, 'destroy'])->name('destroy');
	
});

Route::prefix('planner/allenamento')->name('planner.allenamento.')->group(function () {
	
	Route::get('/esecuzione/oggi',  [PlannerAll::class, 'esecuzioneOggi'])->name('esecuzione.oggi');
    Route::post('/esecuzione/oggi', [PlannerAll::class, 'salvaEsecuzione'])->name('esecuzione.salva');
	Route::get('/oggi', 			[PlannerAll::class, 'esecuzioneOggi'])->name('oggi');
	Route::get('/storico', 	    	[PlannerAll::class, 'storico'])->name('storico');
    Route::get('/{offset?}',       [PlannerAll::class, 'index'])->name('index')->whereNumber('offset')->name('index');
    Route::post('/',               [PlannerAll::class, 'store'])->name('store');
    Route::post('/reorder',        [PlannerAll::class, 'reorder'])->name('reorder');      
    Route::delete('/{id}',         [PlannerAll::class, 'destroy'])->name('destroy');

});

Route::get('alimentazione/storico', [PastiController::class, 'storico'])
    ->name('alimentazione.storico');
	
Route::prefix('alimentazione')->name('alimentazione.')->group(function () {
    Route::get('dispensa/import', [ImportDispensaController::class, 'form'])->name('dispensa.import.form');
    Route::post('dispensa/import', [ImportDispensaController::class, 'store'])->name('dispensa.import.store');
});

Route::prefix('alimentazione')->name('alimentazione.')->group(function () {
	Route::resource('alimenti', AlimentiController::class)->parameters(['alimenti' => 'alimento']);
	Route::get('dispensa', [DispensaController::class, 'index'])->name('dispensa.index');
	Route::get('dispensa/create', [DispensaController::class, 'create'])->name('dispensa.create');
	Route::post('dispensa',         [DispensaController::class, 'store'])->name('dispensa.store');
	Route::put('dispensa/{dispensa}', [DispensaController::class, 'update'])->name('dispensa.update');
    Route::delete('dispensa/{dispensa}', [DispensaController::class, 'destroy'])->name('dispensa.destroy'); // opzionale
	Route::post('dispensa/{id}/chiudi-parziale', [DispensaController::class, 'chiudiParziale'])->name('dispensa.chiudi-parziale');
	/*
    Route::resource('alimenti', AlimentiController::class)
        ->parameters(['alimenti' => 'alimento'])
        ->names([
            'index'   => 'alimenti.index',
            'create'  => 'alimenti.create',
            'store'   => 'alimenti.store',
            'edit'    => 'alimenti.edit',
            'update'  => 'alimenti.update',
            'destroy' => 'alimenti.destroy',
        ]); */
});
Route::get('planner/alimentazione/oggi', [PastiController::class, 'oggi'])->name('planner.alimentazione.oggi');
//Route::get('/alimentazione/oggi', [\App\Http\Controllers\Alimentazione\PlannerController::class, 'oggi'])->name('planner.alimentazione.oggi');
Route::prefix('alimentazione')->name('alimentazione.')->group(function () {
    Route::get('oggi/{data?}', [PastiController::class, 'oggi'])->name('oggi');

    Route::post('planning/{planning}/consumi', [PastiController::class, 'store'])->name('consumi.store');
    Route::delete('consumi/{id}', [PastiController::class, 'destroy'])->name('consumi.destroy');
    Route::delete('consumi/{id}/restore', [PastiController::class, 'destroyAndRestore'])->name('consumi.destroy_restore');

    //Route::get('dispensa', [DispensaController::class, 'index'])->name('dispensa.index');
    //Route::post('dispensa/add', [DispensaController::class, 'add'])->name('dispensa.add');
});


Route::get('/', [HomeController::class, 'index']);

// Allenamento
Route::get('/allenamento', [\App\Http\Controllers\Allenamento\DashboardAllenamentoController::class, 'index'])->name('allenamento.dashboard');

// Allenamento - Lezioni
Route::resource('/allenamento/lezioni', \App\Http\Controllers\Allenamento\LezioneController::class)->parameters(['lezioni' => 'lezione']);
// Allenamento - Planner Oggi
Route::get('/planner/allenamento/oggi', [\App\Http\Controllers\Allenamento\PlannerController::class, 'esecuzioneOggi'])->name('planner.allenamento.oggi');
//Route::post('/planner/allenamento/oggi', [PlannerController::class, 'salvaEsecuzione'])->name('planner.allenamento.salva');
Route::post('/planner/allenamento/oggi', [\App\Http\Controllers\Allenamento\PlannerController::class, 'salvaEsecuzione'])->name('planner.allenamento.salva');
Route::get('/planner/allenamento/storico', [\App\Http\Controllers\Allenamento\PlannerController::class, 'esecuzioneStorico'])->name('planner.allenamento.storico');


// Allenamento - Planner Settimana
Route::get('/planner/allenamento/{offset?}', [\App\Http\Controllers\Allenamento\PlannerController::class, 'index'])->name('planner.allenamento.index');
Route::post('/planner/allenamento', [\App\Http\Controllers\Allenamento\PlannerController::class, 'store'])->name('planner.allenamento.store');
Route::delete('/planner/allenamento/{id}', [\App\Http\Controllers\Allenamento\PlannerController::class, 'destroy'])->name('planner.allenamento.destroy');
Route::post('/planner/allenamento/riordina', [\App\Http\Controllers\Allenamento\PlannerController::class, 'reorder'])->name('planner.allenamento.reorder');

/*
// Alimentazione: Alimenti
Route::resource('alimentazione/alimenti', \App\Http\Controllers\Alimentazione\AlimentoController::class)
    ->parameters(['alimenti' => 'alimento'])
    ->names('alimentazione.alimenti');



*/

// Alimentazione - Planner Oggi
//Route::get('/alimentazione/oggi', [\App\Http\Controllers\Alimentazione\PlannerController::class, 'oggi'])->name('planner.alimentazione.oggi');
// Alimentazione - Planner Settimana
Route::get('/alimentazione/planner/{offset?}', [\App\Http\Controllers\Alimentazione\PlannerController::class, 'index'])->name('planner.alimentazione.index');
Route::post('/alimentazione/planner', [\App\Http\Controllers\Alimentazione\PlannerController::class, 'store'])->name('planner.alimentazione.store');
Route::delete('/alimentazione/planner/{id}', [\App\Http\Controllers\Alimentazione\PlannerController::class, 'destroy'])->name('planner.alimentazione.destroy');
/*
// Rotte per la registrazione dei pasti consumati (alimenti_pasti)
Route::get('alimentazione/pasti/oggi', [PastoController::class, 'oggi'])->name('alimentazione.pasti.oggi');
//Route::get('alimentazione/pasti/oggi', [PastoController::class, 'oggi'])->name('planner.alimentazione.oggi');


Route::prefix('alimentazione')->name('alimentazione.')->group(function () {
    Route::get('pasti/{planning}', [PastoController::class, 'index'])->name('pasti.index');
    Route::get('pasti/{planning}/create', [PastoController::class, 'create'])->name('pasti.create');
    Route::post('pasti/{planning}', [PastoController::class, 'store'])->name('pasti.store');
    Route::get('pasti/{pasto}/edit', [PastoController::class, 'edit'])->name('pasti.edit');
    Route::put('pasti/{pasto}', [PastoController::class, 'update'])->name('pasti.update');
    Route::delete('pasti/{pasto}', [PastoController::class, 'destroy'])->name('pasti.destroy');
});

*/


Route::get('/casa', [\App\Http\Controllers\Casa\DashboardCasaController::class, 'index'])->name('casa.dashboard');
Route::get('/persona', [\App\Http\Controllers\Persona\DashboardPersonaController::class, 'index'])->name('persona.dashboard');
Route::get('/sociale', [\App\Http\Controllers\Sociale\DashboardSocialeController::class, 'index'])->name('sociale.dashboard');
Route::get('/mentale', [\App\Http\Controllers\Mentale\DashboardMentaleController::class, 'index'])->name('mentale.dashboard');

