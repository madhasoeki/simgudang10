<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat Permissions (Hak Akses)
        Permission::firstOrCreate(['name' => 'approve opname', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view history', 'guard_name' => 'web']);

        // 2. Cari Role 'super-admin' yang sudah kita buat sebelumnya
        $superAdminRole = Role::where('name', 'super-admin')->first();

        // 3. Berikan semua permission di atas ke role 'super-admin'
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo([
                'approve opname',
                'manage users',
                'view history',
            ]);
        }
    }
}