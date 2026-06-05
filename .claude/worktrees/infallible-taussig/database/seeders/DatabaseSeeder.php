<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Roles — sin dependencias
            Roleseeder::class,

            // 2. Catálogos base — sin dependencias entre sí
            //    ámbitos, niveles, poderes (los necesita OrganismoSeeder)
            CatAmbitosNivelesPoderesSeeder::class,

            // 3. Catálogos PI-PEA — en orden por FK
            //    ejes primero (sin FK)
            //    objetivos depende de ejes
            //    prioridades depende de objetivos
            //    estrategias, plazos, frecuencias sin FK
            CatEjesSeeder::class,
            CatObjetivosSeeder::class,
            CatPrioridadesSeeder::class,
            CatCatalogosSimpleSeeder::class,

            // 4. Organismos — depende de ambitos, niveles, poderes
            OrganismoSeeder::class,

            // 5. Usuarios — depende de roles y organismos
            UsuariosPruebaSeeder::class,


            OrganismosFrecuenciasFaltantesSeeder::class,


            LineasAccionSeeder::class,


        ]);
    }
}
