<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Rooms and starter accounts. Room names, capacities and people are placeholders
     * until Fabtech shares the real list. Every account below has the password set in
     * EUREKA_SEED_PASSWORD (default "Eureka2026") and must change it on first sign-in.
     */
    public function run(): void
    {
        $rooms = [
            ['Boardroom', 14, true],
            ['Vikram Sarabhai', 8, false],
            ['Kalpana Chawla', 6, false],
            ['Steve Jobs', 10, false],
            ['APJ Abdul Kalam', 8, false],
            ['Homi Bhabha', 6, false],
            ['CV Raman', 4, false],
            ['Marie Curie', 4, false],
            ['Ada Lovelace', 6, false],
            ['Nikola Tesla', 4, false],
        ];
        foreach ($rooms as $i => [$name, $capacity, $seniorOnly]) {
            Room::updateOrCreate(['name' => $name], [
                'capacity' => $capacity,
                'senior_only' => $seniorOnly,
                'equipment' => 'TV, Video call',
                'active' => true,
                'sort_order' => $i,
            ]);
        }

        $password = env('EUREKA_SEED_PASSWORD', 'Eureka2026');
        $people = [
            ['Sanjay Desai', 'admin', false, 'IT'],
            ['Nisha Kapoor', 'admin', false, 'IT'],
            ['Priya Nair', 'reception', false, 'Front office'],
            ['Deepak Rane', 'reception', false, 'Front office'],
            ['Rajesh Iyer', 'employee', true, 'Finance'],
            ['Kavita Shah', 'employee', true, 'Operations'],
            ['Ritika Menon', 'employee', false, 'Procurement'],
            ['Arjun Mehta', 'employee', false, 'Procurement'],
        ];
        foreach ($people as [$name, $role, $senior, $dept]) {
            $empId = strtolower(str_replace(' ', '.', $name));
            User::updateOrCreate(['emp_id' => $empId], [
                'name' => $name,
                'email' => $empId.'@fabtech.example',
                'department' => $dept,
                'role' => $role,
                'senior' => $senior,
                'active' => true,
                'must_change_password' => true,
                'password' => $password,
            ]);
        }
    }
}
