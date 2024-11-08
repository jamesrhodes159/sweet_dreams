<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subscription::create([
            'subscription_plan' => 'Dreams Dictionary',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit hendrerit erat consequat. Fusce dui dui, faucibus id ipsum ac, interdum pretium libero. Vivamus aliquam ipsum vel leo varius aliquet.',
            'price' => '9.99'
        ]);

        Subscription::create([
            'subscription_plan' => 'Dreams Stickers',
            'description' => 'Consectetur adipiscing elit hendrerit erat consequat. Vivamus aliquam ipsum vel leo varius aliquet.',
            'price' => '12.99'
        ]);

        Subscription::create([
            'subscription_plan' => 'Dreams Connection',
            'description' => 'hendrerit erat consequat. Vivamus aliquam ipsum vel leo varius aliquet.',
            'price' => '15.99'
        ]);
    }

}
