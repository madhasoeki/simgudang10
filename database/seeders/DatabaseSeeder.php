<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,       // Jalankan ini dulu untuk membuat roles
            PermissionSeeder::class, // Lalu jalankan ini untuk membuat permissions & menghubungkannya
            UserSeeder::class,       // Terakhir jalankan ini untuk membuat user & memberikan role
        ]);
    }
}