<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::create([
            'title' => 'Samuel & Angela Wedding',
            'groom_name' => 'Samuel',
            'bride_name' => 'Angela',
            'wedding_date' => '2027-07-27',
            'slug' => 'sa2027',
        ]);

        Guest::create([
            'event_id' => $event->id,
            'guest_name' => 'Demo Guest',
            'token' => 'demo-token',
        ]);
    }
}
