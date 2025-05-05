<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusesTableSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['name' => 'pending', 'badge' => 'warning'],
            ['name' => 'in-approval', 'badge' => 'warning'],
            ['name' => 'approved', 'badge' => 'success'],
            ['name' => 'reject', 'badge' => 'secondary'],
            ['name' => 'in-progress', 'badge' => 'primary'],
            ['name' => 'cancelled', 'badge' => 'danger'],
            ['name' => 'completed', 'badge' => 'success'],
        ];

        DB::table('statuses')->insert($statuses);
    }
}
