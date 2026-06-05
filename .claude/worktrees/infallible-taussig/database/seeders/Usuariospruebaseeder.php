<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crea 3 usuarios de prueba, uno por rol.
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  ROL                │  EMAIL                    │  PASSWORD     │
 * ├─────────────────────┼───────────────────────────┼───────────────┤
 * │  SUPER_ADMIN        │  superadmin@sesaech.gob   │  Admin@1234   │
 * │  ADMIN_DEPENDENCIA  │  admindep@sesaech.gob     │  Admin@1234   │
 * │  USUARIO_ORGANISMO  │  usuario@sfp.gob          │  Admin@1234   │
 * └─────────────────────┴───────────────────────────┴───────────────┘
 *
 * IMPORTANTE: Cambiar contraseñas en producción.
 */
class Usuariospruebaseeder extends Seeder
{
    public function run(): void
    {
        $ROL_ORGANISMO = 2;
        $now = now();

        /*
         * Un usuario institucional por organismo.
         * Email: siglas@sesaech.chiapas.gob.mx  (en minúsculas)
         * Password temporal: Chiapas2025! (se debe cambiar en primer acceso)
         *
         * IDs de organismos_implementadores (según tu BD):
         *  1 SESAECH | 2 SF | 3 SFP | 4 SEGOB | 5 SE | 6 SS
         *  7 PJECH   | 8 FGE | 9 ITAIPCH | 10 ASE | 11 TJAECH | 12 CONGRESO
         */
        $usuarios = [
            [
//                'id_organismo' => 1,
//                'nombre' => 'Secretaría Ejecutiva',
//                'primer_apellido' => 'SESAECH',
//                'segundo_apellido' => '',
//                'email' => 'sesaech@sesaech.chiapas.gob.mx',
//                'curp' => null,
//                'rfc' => null,
//            ],
//            [
                'id_organismo' => 2,
                'nombre' => 'Secretaría',
                'primer_apellido' => 'de Finanzas',
                'segundo_apellido' => '',
                'email' => 'sf@sesaech.chiapas.gob.mx',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 3,
                'nombre' => 'Secretaría',
                'primer_apellido' => 'de la Función Pública',
                'segundo_apellido' => '',
                'email' => 'usuario_1@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 4,
                'nombre' => 'Secretaría',
                'primer_apellido' => 'de Gobierno',
                'segundo_apellido' => '',
                'email' => 'usuario_2@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 5,
                'nombre' => 'Secretaría',
                'primer_apellido' => 'de Educación',
                'segundo_apellido' => '',
                'email' => 'usuario_3@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 6,
                'nombre' => 'Secretaría',
                'primer_apellido' => 'de Salud',
                'segundo_apellido' => '',
                'email' => 'usuario_4@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 7,
                'nombre' => 'Poder Judicial',
                'primer_apellido' => 'del Estado',
                'segundo_apellido' => 'de Chiapas',
                'email' => 'usuario_5@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 8,
                'nombre' => 'Fiscalía General',
                'primer_apellido' => 'del Estado',
                'segundo_apellido' => 'de Chiapas',
                'email' => 'usuario_6@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 9,
                'nombre' => 'Instituto de Transparencia',
                'primer_apellido' => 'ITAIPCH',
                'segundo_apellido' => '',
                'email' => 'usuario_7@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 10,
                'nombre' => 'Auditoría Superior',
                'primer_apellido' => 'de Chiapas',
                'segundo_apellido' => '',
                'email' => 'usuario_8@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 11,
                'nombre' => 'Tribunal de Justicia',
                'primer_apellido' => 'Administrativa',
                'segundo_apellido' => 'Chiapas',
                'email' => 'usuario_9@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
            [
                'id_organismo' => 12,
                'nombre' => 'Congreso',
                'primer_apellido' => 'del Estado',
                'segundo_apellido' => 'de Chiapas',
                'email' => 'usuario_10@test.gob',
                'curp' => null,
                'rfc' => null,
            ],
        ];

//        $password = Hash::make('Admin@1234');
        $ahora    = now();

        DB::table('usuarios')->upsert(
            [
                // ── SUPER_ADMIN ───────────────────────────────────────────────
                [
                    'id'              => 1,
                    'nombre'          => 'Administrador',
                    'primer_apellido' => 'Sistema',
                    'segundo_apellido'=> null,
                    'email'           => 'admin@test.gob',
                    'password'        => Hash::make('password'),
                    'id_rol'          => 1, // SUPER_ADMIN
                    'id_organismo'    => null, // SESAECH
                    'activo'          => true,
                    'created_at'      => $ahora,
                    'updated_at'      => $ahora,
                ],

                // ── ADMIN_DEPENDENCIA ─────────────────────────────────────────
                [
                    'id'              => 2,
                    'nombre'          => 'Fernando',
                    'primer_apellido' => 'Sandoval',
                    'segundo_apellido'=> null,
                    'email'           => 'admindep@test.gob',
                    'password'        => Hash::make('password'),
                    'id_rol'          => 1, // ADMIN_DEPENDENCIA
                    'id_organismo'    => 1, // SESAECH
                    'activo'          => true,
                    'created_at'      => $ahora,
                    'updated_at'      => $ahora,
                ],

                // ── USUARIO_ORGANISMO ─────────────────────────────────────────
//                [
//                    'id'              => 3,
//                    'nombre'          => 'Usuario',
//                    'primer_apellido' => 'Organismo',
//                    'segundo_apellido'=> null,
//                    'email'           => 'usuario@test.gob',
//                    'password'        => Hash::make('password'),
//                    'id_rol'          => 2, // USUARIO_ORGANISMO
//                    'id_organismo'    => 2, // Secretaría de Finanzas
//                    'activo'          => true,
//                    'created_at'      => $ahora,
//                    'updated_at'      => $ahora,
//                ],
            ],
            uniqueBy: ['id'],
            update: ['nombre', 'primer_apellido', 'email', 'password', 'id_rol', 'id_organismo', 'activo']
        );
        foreach ($usuarios as $u) {
            // Evita duplicados si se corre el seeder más de una vez
            $existe = DB::table('usuarios')
                ->where('id_organismo', $u['id_organismo'])
                ->where('id_rol', $ROL_ORGANISMO)
                ->exists();

            if ($existe) continue;

            DB::table('usuarios')->insert([
                'id_rol' => $ROL_ORGANISMO,
                'id_organismo' => $u['id_organismo'],
                'nombre' => $u['nombre'],
                'primer_apellido' => $u['primer_apellido'],
                'segundo_apellido' => $u['segundo_apellido'],
                'curp' => $u['curp'],
                'rfc' => $u['rfc'],
                'email' => $u['email'],
                'password' => Hash::make('Chiapas2025!'),
                'activo' => true,
                'fecha_registro' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
    }


        }


}
