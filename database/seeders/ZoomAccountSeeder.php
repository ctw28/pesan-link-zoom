<?php

namespace Database\Seeders;

use App\Models\ZoomAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ZoomAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        ZoomAccount::truncate(); // reset biar bersih

        ZoomAccount::create([
            'name' => 'Zoom 1',
            'email' => 'tipdiainkendari1@gmail.com',
            'capacity' => 100,
            'account_id' => env('ZOOM_1_ACCOUNT_ID'),
            'client_id' => env('ZOOM_1_CLIENT_ID'),
            'client_secret' => env('ZOOM_1_CLIENT_SECRET'),
            'is_active' => true
        ]);

        ZoomAccount::create([
            'name' => 'Zoom 2',
            'email' => 'iainkendari3@gmail.com',
            'capacity' => 100,
            'account_id' => env('ZOOM_2_ACCOUNT_ID'),
            'client_id' => env('ZOOM_2_CLIENT_ID'),
            'client_secret' => env('ZOOM_2_CLIENT_SECRET'),
            'is_active' => true
        ]);
    }
}
