<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Search roles by name
        $adminRole = Role::where('name', 'administrador')->first();
        $vendedorRole = Role::where('name', 'vendedor')->first();
        $almacenRole = Role::where('name', 'almacen')->first();
        $auditorRole = Role::where('name', 'auditor')->first();
        
        // Create users and assign roles
        $admin_1 = User::create([
            'name' => 'Jesus Junior',
            'email' => 'junior@gmail.com',
            'username' => 'junior15',
            'password' => Hash::make('12345678'),
            'status' => 1,
        ]);
        $admin_1->assignRole($adminRole);

     
        $vendedor_1 = User::create([
            'name' => 'Karin Hair',
            'email' => 'kayisanta5@gmail.com',
            'username' => 'kchozo27',
            'password' => Hash::make('12345678'),
            'status' => 1,
        ]);
        $vendedor_1->assignRole($vendedorRole);

        $almacen_1 = User::create([
            'name' => 'Renato Moran',
            'email' => 'renato123@gmail.com',
            'username' => 'renato20',
            'password' => Hash::make('12345678'),
            'status' => 1,
        ]);
        $almacen_1->assignRole($almacenRole);

        $auditor_1 = User::create([
            'name' => 'Jeferson Coveñas',
            'email' => 'jeferson321@gmail.com',
            'username' => 'jeferson32',
            'password' => Hash::make('12345678'),
            'status' => 1,
        ]);
        $auditor_1->assignRole($auditorRole);

        $admin_2 = User::create([
            'name' => 'Pablo Lupu',
            'email' => 'pablolupu2020@gmail.com',
            'username' => 'PabloLupu',
            'password' => Hash::make('12345678'),
            'status' => 1,
        ]);
        $admin_2->assignRole($adminRole);
        $admin_3= User::create([
            'name' => 'Anthony Marck',
            'email' => 'thonymarck385213xd@gmail.com',
            'username' => 'thonymarck',
            'password' => Hash::make('12345678'),
            'status' => 1,
        ]);   
      
        $admin_3->assignRole($adminRole);
    }
}
