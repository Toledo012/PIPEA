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
use App\Models\CatLineasAccionEje;
use App\Models\CatLineasAccionObjetivo;
use App\Models\CatLineasAccionPrioridad;
use App\Models\CatLineasAccionEstatus;
use App\Models\CatLineasAccionPlazo;
use App\Models\LineaAccion;
use App\Models\OrganismoImplementador;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Página pública ───────────────────────────────────────────────────────────
Route::get('/', function () {
    $estadisticas = [
        'ejes'        => CatLineasAccionEje::where('activo', true)->count(),
        'objetivos'   => CatLineasAccionObjetivo::where('activo', true)->count(),
        'prioridades' => CatLineasAccionPrioridad::where('activo', true)->count(),
        'organismos'  => OrganismoImplementador::where('activo', true)->count(),
        'lineas'      => LineaAccion::where('activo', true)->count(),
    ];

    $ejes = CatLineasAccionEje::where('activo', true)
        ->withCount(['lineasAccion as lineas_accion_count' => fn ($q) => $q->where('activo', true)])
        ->orderBy('numero_eje')
        ->get(['id', 'numero_eje', 'eje']);

    $organismos = OrganismoImplementador::where('activo', true)
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'siglas']);

    $lineasAccion = LineaAccion::where('activo', true)
        ->with([
            'eje:id,numero_eje,eje',
            'objetivo:id,numero_objetivo,objetivo',
            'prioridad:id,numero,prioridad',
            'estrategia:id,estrategia',
            'plazo:id,plazo',
            'frecuencia:id,frecuencia',
            'estatus:id,nombre',
            'usuario:id,id_organismo',
            'usuario.organismo:id,nombre,siglas',
            'ultimoAvance',
        ])
        ->get()
        ->map(fn ($l) => [
            'id'                => $l->id,
            'nombre_indicador'  => $l->nombre_indicador,
            'meta'              => $l->meta,
            'unidad_medida'     => $l->unidad_medida,
            'avance_actual'     => $l->ultimoAvance?->avance_cualitativo,
            'porcentaje_avance' => $l->porcentajeAvance,
            'eje'               => $l->eje        ? ['id' => $l->eje->id,        'numero_eje' => $l->eje->numero_eje,       'eje'        => $l->eje->eje]               : null,
            'objetivo'          => $l->objetivo   ? ['id' => $l->objetivo->id,   'objetivo'   => $l->objetivo->objetivo]                                                 : null,
            'prioridad'         => $l->prioridad  ? ['id' => $l->prioridad->id,  'prioridad'  => $l->prioridad->prioridad]                                               : null,
            'estrategia'        => $l->estrategia ? ['id' => $l->estrategia->id, 'estrategia' => $l->estrategia->estrategia]                                             : null,
            'plazo'             => $l->plazo      ? ['id' => $l->plazo->id,      'plazo'      => $l->plazo->plazo]                                                       : null,
            'frecuencia'        => $l->frecuencia ? ['id' => $l->frecuencia->id, 'frecuencia' => $l->frecuencia->frecuencia]                                             : null,
            'estatus'           => $l->estatus    ? ['id' => $l->estatus->id,    'nombre'     => $l->estatus->nombre]                                                    : null,
            'organismo'         => $l->usuario?->organismo ? [
                'id'     => $l->usuario->organismo->id,
                'nombre' => $l->usuario->organismo->nombre,
                'siglas' => $l->usuario->organismo->siglas,
            ] : null,
        ]);

    return Inertia::render('Welcome', [
        'canLogin'     => Route::has('login'),
        'estadisticas' => $estadisticas,
        'ejes'         => $ejes,
        'organismos'   => $organismos,
        'lineasAccion' => $lineasAccion,
    ]);
})->name('home');

// ─── Consulta pública PI-PEA ──────────────────────────────────────────────────
Route::get('/consulta', function () {
    $docs = [
        0 => 'Programa de Implementación de la Política Estatal Anticorrupción. documento oficial.pdf',
        1 => '67 prioridades de la Politica Estatal Anticorrupcion.pdf',
        2 => 'politica-estatal-anticorrupcion.pdf',
        3 => 'MANUAL DE IDENTIDAD HUMANISMO QUE TRANSFORMA (1)_compressed.pdf',
        4 => '20250210 - Inducción al Sistema Anticorrupción del Estado de Chiapas (Municipios) (1) (1).pptx',
    ];

    $documentos = [
        ['index' => 0, 'titulo' => 'Programa de Implementación de la Política Estatal Anticorrupción', 'descripcion' => 'Documento oficial del PI-PEA', 'tipo' => 'PDF'],
        ['index' => 1, 'titulo' => '67 Prioridades de la Política Estatal Anticorrupción', 'descripcion' => 'Catálogo de prioridades estratégicas', 'tipo' => 'PDF'],
        ['index' => 2, 'titulo' => 'Política Estatal Anticorrupción', 'descripcion' => 'Marco normativo y de política pública', 'tipo' => 'PDF'],
        ['index' => 4, 'titulo' => 'Inducción al Sistema Anticorrupción de Chiapas', 'descripcion' => 'Material de inducción para municipios (2025)', 'tipo' => 'PPTX'],
    ];

    $ejes = CatLineasAccionEje::where('activo', true)
        ->withCount(['lineasAccion as lineas_accion_count' => fn ($q) => $q->where('activo', true)])
        ->orderBy('numero_eje')
        ->get(['id', 'numero_eje', 'eje']);

    $organismos = OrganismoImplementador::where('activo', true)
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'siglas']);

    $plazos = CatLineasAccionPlazo::orderBy('id')->get(['id', 'plazo']);

    $estatus = CatLineasAccionEstatus::orderBy('id')->get(['id', 'nombre']);

    $lineasAccion = LineaAccion::where('activo', true)
        ->with([
            'eje:id,numero_eje,eje',
            'objetivo:id,numero_objetivo,objetivo',
            'prioridad:id,numero,prioridad',
            'estrategia:id,estrategia',
            'plazo:id,plazo',
            'frecuencia:id,frecuencia',
            'estatus:id,nombre',
            'usuario:id,id_organismo',
            'usuario.organismo:id,nombre,siglas',
            'ultimoAvance',
        ])
        ->get()
        ->map(fn ($l) => [
            'id'                => $l->id,
            'nombre_indicador'  => $l->nombre_indicador,
            'meta'              => $l->meta,
            'unidad_medida'     => $l->unidad_medida,
            'avance_actual'     => $l->ultimoAvance?->avance_cualitativo,
            'porcentaje_avance' => $l->porcentajeAvance,
            'eje'               => $l->eje       ? ['id' => $l->eje->id,       'numero_eje' => $l->eje->numero_eje, 'eje'        => $l->eje->eje]           : null,
            'objetivo'          => $l->objetivo  ? ['id' => $l->objetivo->id,  'objetivo'   => $l->objetivo->objetivo]                                      : null,
            'prioridad'         => $l->prioridad ? ['id' => $l->prioridad->id, 'prioridad'  => $l->prioridad->prioridad]                                    : null,
            'estrategia'        => $l->estrategia? ['id' => $l->estrategia->id,'estrategia' => $l->estrategia->estrategia]                                  : null,
            'plazo'             => $l->plazo     ? ['id' => $l->plazo->id,     'plazo'      => $l->plazo->plazo]                                            : null,
            'frecuencia'        => $l->frecuencia? ['id' => $l->frecuencia->id,'frecuencia' => $l->frecuencia->frecuencia]                                  : null,
            'estatus'           => $l->estatus   ? ['id' => $l->estatus->id,   'nombre'     => $l->estatus->nombre]                                         : null,
            'organismo'         => $l->usuario?->organismo ? [
                'id'     => $l->usuario->organismo->id,
                'nombre' => $l->usuario->organismo->nombre,
                'siglas' => $l->usuario->organismo->siglas,
            ] : null,
        ]);

    return Inertia::render('Consulta/Index', [
        'lineasAccion' => $lineasAccion,
        'organismos'   => $organismos,
        'ejes'         => $ejes,
        'plazos'       => $plazos,
        'estatus'      => $estatus,
        'documentos'   => $documentos,
    ]);
})->name('consulta');

// ─── Estadísticas públicas ────────────────────────────────────────────────────
Route::get('/estadisticas', function () {
    $lineas = LineaAccion::where('activo', true)
        ->with([
            'eje:id,numero_eje,eje',
            'estatus:id,nombre',
            'usuario:id,id_organismo',
            'usuario.organismo:id,nombre,siglas',
            'ultimoAvance',
        ])
        ->get();

    // Resumen general
    $total    = $lineas->count();
    $conAvance = $lineas->filter(fn ($l) => $l->porcentajeAvance > 0)->count();
    $promGen  = $total > 0 ? round($lineas->avg(fn ($l) => $l->porcentajeAvance), 1) : 0;

    $resumen = [
        'total_lineas'      => $total,
        'con_avance'        => $conAvance,
        'promedio_general'  => $promGen,
        'total_organismos'  => OrganismoImplementador::where('activo', true)->count(),
    ];

    // Avance por organismo
    $avancePorOrganismo = $lineas
        ->filter(fn ($l) => $l->usuario?->organismo !== null)
        ->groupBy(fn ($l) => $l->usuario->organismo->id)
        ->map(function ($grupo) {
            $org    = $grupo->first()->usuario->organismo;
            $eje    = $grupo->first()->eje;
            $total  = $grupo->count();
            $con    = $grupo->filter(fn ($l) => $l->porcentajeAvance > 0)->count();
            $prom   = $total > 0 ? round($grupo->avg(fn ($l) => $l->porcentajeAvance), 1) : 0;
            return [
                'id_organismo'  => $org->id,
                'organismo'     => $org->nombre,
                'siglas'        => $org->siglas,
                'promedio'      => $prom,
                'total_lineas'  => $total,
                'con_avance'    => $con,
                'id_eje'        => $eje?->id,
                'numero_eje'    => $eje?->numero_eje,
            ];
        })
        ->values();

    // Avance por eje
    $avancePorEje = $lineas
        ->filter(fn ($l) => $l->eje !== null)
        ->groupBy(fn ($l) => $l->eje->id)
        ->map(function ($grupo) {
            $eje   = $grupo->first()->eje;
            $total = $grupo->count();
            $prom  = $total > 0 ? round($grupo->avg(fn ($l) => $l->porcentajeAvance), 1) : 0;
            return [
                'id_eje'       => $eje->id,
                'numero_eje'   => $eje->numero_eje,
                'eje'          => $eje->eje,
                'promedio'     => $prom,
                'total_lineas' => $total,
            ];
        })
        ->values();

    // Distribución por rango de porcentaje (histograma)
    $rangos = ['0%' => 0, '1-20%' => 0, '21-40%' => 0, '41-60%' => 0, '61-80%' => 0, '81-99%' => 0, '100%' => 0];
    foreach ($lineas as $l) {
        $p = $l->porcentajeAvance;
        if ($p == 0)        $rangos['0%']++;
        elseif ($p <= 20)   $rangos['1-20%']++;
        elseif ($p <= 40)   $rangos['21-40%']++;
        elseif ($p <= 60)   $rangos['41-60%']++;
        elseif ($p <= 80)   $rangos['61-80%']++;
        elseif ($p < 100)   $rangos['81-99%']++;
        else                $rangos['100%']++;
    }
    $distribucionPct = collect($rangos)->map(fn ($count, $rango) => ['rango' => $rango, 'count' => $count])->values();

    // Distribución por estatus
    $distribucionEstatus = $lineas
        ->groupBy(fn ($l) => $l->estatus?->nombre ?? 'Sin estatus')
        ->map(fn ($g, $nombre) => ['estatus' => $nombre, 'count' => $g->count()])
        ->values();

    return Inertia::render('Estadisticas/Index', [
        'resumen'              => $resumen,
        'avancePorOrganismo'   => $avancePorOrganismo,
        'avancePorEje'         => $avancePorEje,
        'distribucionPct'      => $distribucionPct,
        'distribucionEstatus'  => $distribucionEstatus,
    ]);
})->name('estadisticas');

// ─── Descarga de documentos PI-PEA ───────────────────────────────────────────
Route::get('/documentos/{index}', function (int $index) {
    $docs = [
        0 => 'Programa de Implementación de la Política Estatal Anticorrupción. documento oficial.pdf',
        1 => '67 prioridades de la Politica Estatal Anticorrupcion.pdf',
        2 => 'politica-estatal-anticorrupcion.pdf',
        3 => 'MANUAL DE IDENTIDAD HUMANISMO QUE TRANSFORMA (1)_compressed.pdf',
        4 => '20250210 - Inducción al Sistema Anticorrupción del Estado de Chiapas (Municipios) (1) (1).pptx',
    ];

    abort_if(!isset($docs[$index]), 404);

    $path = base_path('docs/pipea/' . $docs[$index]);

    abort_if(!file_exists($path), 404);

    return response()->download($path, $docs[$index]);
})->name('documentos.download');

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

                // Evidencias / Mis avances
                Route::get('evidencias', [EvidenciasController::class, 'index'])
                    ->name('evidencias.index');
                Route::get('evidencias/export/xlsx', [EvidenciasController::class, 'exportXlsx'])
                    ->name('evidencias.export.xlsx');
                Route::get('evidencias/export/pdf', [EvidenciasController::class, 'exportPdf'])
                    ->name('evidencias.export.pdf');

                // Dinámicas después
                Route::get('lineas/{lineaAccion}', [UserDashboardController::class, 'show'])
                   ->name('lineas.show');

                Route::post('lineas/{lineaAccion}/avances', [HistorialAvanceController::class, 'store'])
                    ->name('lineas.avances.store');
        });
});
