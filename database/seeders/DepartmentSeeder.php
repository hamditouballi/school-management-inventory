<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'PM1 - Strategic Leadership',
            'PM2 - Quality Management',
            'PE1 - Preschool Education',
            'PE2 - Primary Education',
            'PE3 - Secondary Education',
            'PS1 - Human Resources & Communication',
            'PS2 - Logistics, Maintenance & Transport',
        ];

        foreach ($departments as $department) {
            Department::create([
                'name' => $department
            ]);
        }
    }
}
