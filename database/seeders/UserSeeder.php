<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Departments
        $pm1 = Department::where('name', 'PM1 - Strategic Leadership')->first();
        $pm2 = Department::where('name', 'PM2 - Quality Management')->first();

        $pe1 = Department::where('name', 'PE1 - Preschool Education')->first();
        $pe2 = Department::where('name', 'PE2 - Primary Education')->first();
        $pe3 = Department::where('name', 'PE3 - Secondary Education')->first();

        $ps1 = Department::where('name', 'PS1 - Human Resources & Communication')->first();
        $ps2 = Department::where('name', 'PS2 - Logistics, Maintenance & Transport')->first();

        // HR
        User::create([
            'name' => 'HR Manager',
            'username' => 'hr_manager',
            'email' => 'hr@school.com',
            'password' => Hash::make('password'),
            'role' => 'hr_manager',
            'department_id' => $ps1->id,
        ]);

        // PM1 (same role as HR)
        User::create([
            'name' => 'PM1 Manager',
            'username' => 'pm1_manager',
            'email' => 'pm1@school.com',
            'password' => Hash::make('password'),
            'role' => 'hr_manager',
            'department_id' => $pm1->id,
        ]);

        // PM2 (same role as HR)
        User::create([
            'name' => 'PM2 Manager',
            'username' => 'pm2_manager',
            'email' => 'pm2@school.com',
            'password' => Hash::make('password'),
            'role' => 'hr_manager',
            'department_id' => $pm2->id,
        ]);

        // Finance
        User::create([
            'name' => 'Finance Manager',
            'username' => 'finance_manager',
            'email' => 'finance@school.com',
            'password' => Hash::make('password'),
            'role' => 'finance_manager',
            'department_id' => $ps1->id,
        ]);

        // Stock Manager
        User::create([
            'name' => 'Stock Manager',
            'username' => 'fix the ',
            'email' => 'stock@school.com',
            'password' => Hash::make('password'),
            'role' => 'stock_manager',
            'department_id' => $ps2->id,
        ]);

        User::create([
            'name' => 'Preschool Director',
            'username' => 'director_pe1',
            'email' => 'director_pe1@school.com',
            'password' => Hash::make('password'),
            'role' => 'director',
            'department_id' => $pe1->id,
        ]);

        User::create([
            'name' => 'Primary Director',
            'username' => 'director_pe2',
            'email' => 'director_pe2@school.com',
            'password' => Hash::make('password'),
            'role' => 'director',
            'department_id' => $pe2->id,
        ]);

        User::create([
            'name' => 'Secondary Director',
            'username' => 'director_pe3',
            'email' => 'director_pe3@school.com',
            'password' => Hash::make('password'),
            'role' => 'director',
            'department_id' => $pe3->id,
        ]);

        User::create([
            'name' => 'Logistics Manager',
            'username' => 'logistics_manager',
            'email' => 'logistics@school.com',
            'password' => Hash::make('password'),
            'role' => 'logistics_manager',
            'department_id' => $ps2->id,
        ]);
    }
}
