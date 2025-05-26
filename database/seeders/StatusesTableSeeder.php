<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusesTableSeeder extends Seeder
{
    public function run()
    {
        // in-approval, approved
        // delete these two statuses
        $deleteStatuses = ['in-approval', 'approved'];
        Status::whereIn('name', $deleteStatuses)->delete();
        $statuses = [
            ['name' => 'pending', 'badge' => 'warning'],
            ['name' => 'reject', 'badge' => 'secondary'],
            ['name' => 'in-progress', 'badge' => 'primary'],
            ['name' => 'cancelled', 'badge' => 'danger'],
            ['name' => 'completed', 'badge' => 'success'],
            ['name' => 'draft', 'badge' => 'info']
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(
                ['name' => $status['name']],   // Match by name
                ['badge' => $status['badge']]  // Update badge if exists
            );
        }
    }
}
