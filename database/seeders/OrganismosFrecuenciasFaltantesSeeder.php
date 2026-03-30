<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Agrega a la BD los organismos y frecuencias que existen en el Excel
 * de seguimiento pero no en los catálogos actuales.
 *
 * Organismos nuevos (ids 13-18):
 *   13 - Comisión Estatal de Mejora Regulatoria
 *   14 - Oficialía Mayor del Estado de Chiapas
 *   15 - Secretaría Anticorrupción y Buen Gobierno
 *   16 - Secretaría de Infraestructura
 *   17 - Instituto de la Consejería Jurídica
 *   18 - Secretaría General de Gobierno y Mediación
 *
 * Frecuencias nuevas (ids 6-9):
 *   6 - Cuatrimestral
 *   7 - Sexenal
 *   8 - Bianual
 *   9 - Trianual
 *
 * También crea un usuario institucional (rol=2) por cada organismo nuevo.
 */
class OrganismosFrecuenciasFaltantesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── 1. Organismos faltantes ───────────────────────────────────────────
        // ambito=1 (Estatal) | nivel=1 (Secretaría/Dependencia) | poder=1 (Ejecutivo)
        $organismos = [
            ['id' => 13, 'nombre' => 'Comisión Estatal de Mejora Regulatoria',     'siglas' => 'COESMER',  'id_nivel' => 1, 'id_poder' => 1],
            ['id' => 14, 'nombre' => 'Oficialía Mayor del Estado de Chiapas',       'siglas' => 'OM',       'id_nivel' => 1, 'id_poder' => 1],
            ['id' => 15, 'nombre' => 'Secretaría Anticorrupción y Buen Gobierno',   'siglas' => 'SABG',     'id_nivel' => 1, 'id_poder' => 1],
            ['id' => 16, 'nombre' => 'Secretaría de Infraestructura',               'siglas' => 'SINF',     'id_nivel' => 1, 'id_poder' => 1],
            ['id' => 17, 'nombre' => 'Instituto de la Consejería Jurídica',         'siglas' => 'ICJ',      'id_nivel' => 2, 'id_poder' => 1],
            ['id' => 18, 'nombre' => 'Secretaría General de Gobierno y Mediación',  'siglas' => 'SGGM',     'id_nivel' => 1, 'id_poder' => 1],
        ];

        foreach ($organismos as $org) {
            $existe = DB::table('organismos_implementadores')->where('id', $org['id'])->exists();
            if ($existe) continue;

            DB::table('organismos_implementadores')->insert([
                'id'          => $org['id'],
                'id_ambito'   => 1,
                'id_nivel'    => $org['id_nivel'],
                'id_poder'    => $org['id_poder'],
                'nombre'      => $org['nombre'],
                'siglas'      => $org['siglas'],
                'activo'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // ── 2. Frecuencias faltantes ──────────────────────────────────────────
        $frecuencias = [
            ['id' => 6, 'frecuencia' => 'Cuatrimestral'],
            ['id' => 7, 'frecuencia' => 'Sexenal'],
            ['id' => 8, 'frecuencia' => 'Bianual'],
            ['id' => 9, 'frecuencia' => 'Trianual'],
        ];

        foreach ($frecuencias as $f) {
            $existe = DB::table('cat_lineas_accion_frec_medicion')->where('id', $f['id'])->exists();
            if ($existe) continue;

            DB::table('cat_lineas_accion_frec_medicion')->insert([
                'id'         => $f['id'],
                'frecuencia' => $f['frecuencia'],
                'activo'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── 3. Usuarios institucionales para los nuevos organismos ────────────
        $usuariosNuevos = [
            ['id_organismo' => 13, 'nombre' => 'Mejora Regulatoria',      'primer_apellido' => 'COESMER',    'email' => 'coesmer@sesaech.chiapas.gob.mx'],
            ['id_organismo' => 14, 'nombre' => 'Oficialía Mayor',          'primer_apellido' => 'Chiapas',    'email' => 'om@sesaech.chiapas.gob.mx'],
            ['id_organismo' => 15, 'nombre' => 'Secretaría Anticorrupción','primer_apellido' => 'Buen Gobierno', 'email' => 'sabg@sesaech.chiapas.gob.mx'],
            ['id_organismo' => 16, 'nombre' => 'Secretaría',               'primer_apellido' => 'de Infraestructura', 'email' => 'sinf@sesaech.chiapas.gob.mx'],
            ['id_organismo' => 17, 'nombre' => 'Instituto',                'primer_apellido' => 'Consejería Jurídica', 'email' => 'icj@sesaech.chiapas.gob.mx'],
            ['id_organismo' => 18, 'nombre' => 'Secretaría General',       'primer_apellido' => 'de Gobierno', 'email' => 'sggm@sesaech.chiapas.gob.mx'],
        ];

        foreach ($usuariosNuevos as $u) {
            $existe = DB::table('usuarios')
                ->where('id_organismo', $u['id_organismo'])
                ->where('id_rol', 2)
                ->exists();

            if ($existe) continue;

            DB::table('usuarios')->insert([
                'id_rol'           => 2,
                'id_organismo'     => $u['id_organismo'],
                'nombre'           => $u['nombre'],
                'primer_apellido'  => $u['primer_apellido'],
                'segundo_apellido' => null,
                'curp'             => null,
                'rfc'              => null,
                'email'            => $u['email'],
                'password'         => Hash::make('Chiapas2025!'),
                'activo'           => true,
                'fecha_registro'   => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}
