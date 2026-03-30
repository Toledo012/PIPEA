<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganismoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('organismos_implementadores')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // IDs de los catálogos base (se insertan antes en DatabaseSeeder)
        // cat_ambitos:  1=Estatal, 2=Municipal, 3=Federal
        // cat_niveles:  1=Secretaría/Dependencia, 2=Org. autónomo, 3=Paraestatal,
        //               4=Ayuntamiento, 5=Org. descentralizado, 6=Tribunal, 7=Órgano const. autónomo
        // cat_poderes:  1=Ejecutivo, 2=Legislativo, 3=Judicial, 4=Autónomo

        $organismos = [
            // Ejecutivo estatal
            [
                'id_ambito' => 1, 'id_nivel' => 2, 'id_poder' => 4,
                'nombre' => 'Secretaría Ejecutiva del Sistema Anticorrupción del Estado de Chiapas',
                'siglas' => 'SESAECH',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 1, 'id_poder' => 1,
                'nombre' => 'Secretaría de Finanzas',
                'siglas' => 'SF',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 1, 'id_poder' => 1,
                'nombre' => 'Secretaría de la Función Pública',
                'siglas' => 'SFP',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 1, 'id_poder' => 1,
                'nombre' => 'Secretaría de Gobierno',
                'siglas' => 'SEGOB',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 1, 'id_poder' => 1,
                'nombre' => 'Secretaría de Educación',
                'siglas' => 'SE',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 1, 'id_poder' => 1,
                'nombre' => 'Secretaría de Salud',
                'siglas' => 'SS',
            ],
            // Judicial
            [
                'id_ambito' => 1, 'id_nivel' => 6, 'id_poder' => 3,
                'nombre' => 'Poder Judicial del Estado de Chiapas',
                'siglas' => 'PJECH',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 6, 'id_poder' => 3,
                'nombre' => 'Fiscalía General del Estado de Chiapas',
                'siglas' => 'FGE',
            ],
            // Autónomos
            [
                'id_ambito' => 1, 'id_nivel' => 7, 'id_poder' => 4,
                'nombre' => 'Instituto de Transparencia, Acceso a la Información y Protección de Datos Personales del Estado de Chiapas',
                'siglas' => 'ITAIPCH',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 7, 'id_poder' => 4,
                'nombre' => 'Auditoría Superior de Chiapas',
                'siglas' => 'ASE',
            ],
            [
                'id_ambito' => 1, 'id_nivel' => 7, 'id_poder' => 4,
                'nombre' => 'Tribunal de Justicia Administrativa del Estado de Chiapas',
                'siglas' => 'TJAECH',
            ],
            // Legislativo
            [
                'id_ambito' => 1, 'id_nivel' => 1, 'id_poder' => 2,
                'nombre' => 'Congreso del Estado de Chiapas',
                'siglas' => 'CONGRESO',
            ],




        ];

        foreach ($organismos as $org) {
            DB::table('organismos_implementadores')->insert([
                ...$org,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
