<?php

use App\Http\Controllers\Admin\Catalogos\CatEjeController;
use App\Http\Controllers\Admin\Catalogos\CatEstrategiaController;
use App\Http\Controllers\Admin\Catalogos\CatFrecuenciaController;
use App\Http\Controllers\Admin\Catalogos\CatObjetivoController;
use App\Http\Controllers\Admin\Catalogos\CatPlazoController;
use App\Http\Controllers\Admin\Catalogos\CatPrioridadController;
use App\Http\Controllers\Admin\Dashboard\AdminDashboardController;
use App\Http\Controllers\Admin\Organismos\OrganismoController;
use App\Http\Controllers\Admin\Usuarios\UsuarioController;
use App\Http\Controllers\Admin\Avance\PeriodoReporteController;
use App\Http\Controllers\Admin\Avance\ExportPeriodoController;
use App\Http\Controllers\Admin\LineasAccion\LineaAccionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\User\Avance\EvidenciasController;
use App\Http\Controllers\User\Avance\HistorialAvanceController;
use App\Http\Controllers\User\Dashboard\UserDashboardController;
use App\Http\Controllers\User\UserLineas\UserLineasController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Página pública ───────────────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
})->name('home');

// ─── Solo invitados ───────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// ─── Logout ───────────────────────────────────────────────────────────────────
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ─── Rutas protegidas ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'activo'])->group(function () {

    // ══════════════════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════════════════
    Route::middleware('role:ADMIN')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get('dashboard', [AdminDashboardController::class, 'index'])
                ->name('dashboard');

            // ── Catálogos PI-PEA ──────────────────────────────────────────────
            Route::prefix('catalogos')->name('catalogos.')->group(function () {
                Route::resource('ejes',        CatEjeController::class)      ->only(['index','store','update','destroy']);
                Route::resource('objetivos',   CatObjetivoController::class) ->only(['index','store','update','destroy']);
                Route::resource('prioridades', CatPrioridadController::class)->only(['index','store','update','destroy']);
                Route::resource('estrategias', CatEstrategiaController::class)->only(['index','store','update','destroy']);
                Route::resource('plazos',      CatPlazoController::class)    ->only(['index','store','update','destroy']);
                Route::resource('frecuencias', CatFrecuenciaController::class)->only(['index','store','update','destroy']);
            });

            // ── Líneas de acción ──────────────────────────────────────────────
            Route::prefix('lineas-accion')->name('lineas.')->group(function () {
                Route::get   ('/',                [LineaAccionController::class, 'index'])  ->name('index');
                Route::post  ('/',                [LineaAccionController::class, 'store'])  ->name('store');
                Route::put   ('/{lineaAccion}',   [LineaAccionController::class, 'update']) ->name('update');
                Route::delete('/{lineaAccion}',   [LineaAccionController::class, 'destroy'])->name('destroy');

                // Cascades para selects dependientes
                Route::get('/cascade/objetivos/{eje}',
                    [LineaAccionController::class, 'objetivosPorEje'])
                    ->name('cascade.objetivos');
                Route::get('/cascade/prioridades/{objetivo}',
                    [LineaAccionController::class, 'prioridadesPorObjetivo'])
                    ->name('cascade.prioridades');
            });

            // ── Organismos ────────────────────────────────────────────────────
            Route::resource('organismos', OrganismoController::class)
                ->only(['index','store','update','destroy']);

            // ── Usuarios ──────────────────────────────────────────────────────
            Route::resource('usuarios', UsuarioController::class)
                ->only(['index','store','update','destroy']);

            // ── Períodos de reporte ── NUEVO ──────────────────────────────────
            Route::prefix('periodos')->name('periodos.')->group(function () {
                // Lista + detalle en una sola vista
                Route::get   ('/',                    [PeriodoReporteController::class, 'index'])       ->name('index');
                Route::post  ('/',                    [PeriodoReporteController::class, 'store'])       ->name('store');
                // Abrir / cerrar período completo
                Route::patch ('/{periodo}/toggle',    [PeriodoReporteController::class, 'toggleActivo'])->name('toggle');
                // Dar prórroga individual a una línea
                Route::post  ('/{periodo}/prorroga',  [PeriodoReporteController::class, 'prorroga'])    ->name('prorroga');
                // Datos del período para el modal de detalle (JSON)
                Route::get   ('/{periodo}/detalle',   [PeriodoReporteController::class, 'detalle'])     ->name('detalle');
                // Exportación
                Route::get   ('/{periodo}/exportar/pdf',   [ExportPeriodoController::class, 'pdf'])   ->name('exportar.pdf');
                Route::get   ('/{periodo}/exportar/excel', [ExportPeriodoController::class, 'excel']) ->name('exportar.excel');
            });
        });


    Route::middleware('role:ORGANISMO IMPLEMENTADOR')
        ->prefix('organismo')
        ->name('organismo.')
        ->group(function () {

                Route::get('dashboard', [UserDashboardController::class, 'Index'])
                    ->name('dashboard');

                // Lista primero — rutas estáticas antes que las dinámicas
                Route::get('lineas', [UserLineasController::class, 'Index'])
                    ->name('lineas.index');

                // Evidencias
              Route::get('evidencias', [EvidenciasController::class, 'Index'])
                    ->name('evidencias.index');

                // Dinámicas después
                Route::get('lineas/{lineaAccion}', [UserDashboardController::class, 'show'])
                   ->name('lineas.show');

                Route::post('lineas/{lineaAccion}/avances', [HistorialAvanceController::class, 'store'])
                    ->name('lineas.avances.store');
        });
});
