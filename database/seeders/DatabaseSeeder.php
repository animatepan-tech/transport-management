<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'username'=>'admin',
            'password'=>'admin123',
            'name'=>'Administrator',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        DB::table('buses')->insert([
            'bus_number'=>'BUS-001',
            'registration_number'=>null,
            'capacity'=>40,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}
