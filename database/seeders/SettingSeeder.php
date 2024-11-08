<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create([
            'points_per_comment' => '10',
            'points_per_review' => '20',
            'points_per_post' => '30',
            'points_per_chat_message' => '5'
        ]);

    }
}
