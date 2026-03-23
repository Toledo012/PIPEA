<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Roleseeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert(
            [
                ['id' => 1, 'rol' => 'ADMIN',       'activo' => true],
                ['id' => 2, 'rol' => 'ORGANISMO IMPLEMENTADOR', 'activo' => true],
            ],
            uniqueBy: ['id'],
            update: ['rol', 'activo']
        );
    }
}
