<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Department::where('name', 'Administration')->first();
        $nursery = Department::where('name', 'Nursery')->first();
        $primary = Department::where('name', 'Primary')->first();

        // HR Manager
        User::create([
            'name' => 'HR Manager',
            'username' => 'hr_manager',
            'email' => 'hr@school.com',
            'password' => Hash::make('password'),
            'role' => 'hr_manager',
            'department_id' => $admin->id,
        ]);

        // Stock Manager
        User::create([
            'name' => 'Stock Manager',
            'username' => 'stock_manager',
            'email' => 'stock@school.com',
            'password' => Hash::make('password'),
            'role' => 'stock_manager',
            'department_id' => $admin->id,
        ]);

        // Finance Manager
        User::create([
            'name' => 'Finance Manager',
            'username' => 'finance_manager',
            'email' => 'finance@school.com',
            'password' => Hash::make('password'),
            'role' => 'finance_manager',
            'department_id' => $admin->id,
        ]);

        // Teachers
        User::create([
            'name' => 'Nursery Teacher',
            'username' => 'teacher_nursery',
            'email' => 'teacher1@school.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'department_id' => $nursery->id,
        ]);

        User::create([
            'name' => 'Primary Teacher',
            'username' => 'teacher_primary',
            'email' => 'teacher2@school.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'department_id' => $primary->id,
        ]);
    }
}
