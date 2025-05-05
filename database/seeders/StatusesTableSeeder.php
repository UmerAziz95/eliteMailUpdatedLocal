<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusesTableSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['name' => 'pending', 'badge' => 'warning'],
            ['name' => 'in-approval', 'badge' => 'info'],
            ['name' => 'approved', 'badge' => 'light'],
            ['name' => 'reject', 'badge' => 'secondary'],
            ['name' => 'in-progress', 'badge' => 'primary'],
            ['name' => 'cancelled', 'badge' => 'danger'],
            ['name' => 'completed', 'badge' => 'success'],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(
                ['name' => $status['name']],   // Match by name
                ['badge' => $status['badge']]  // Update badge if exists
            );
        }
    }
}
