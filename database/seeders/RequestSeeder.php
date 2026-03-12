<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Request;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('role', ['director', 'hr_manager'])->get();
        $items = Item::take(5)->get();

        $statuses = ['pending', 'hr_approved', 'rejected', 'fulfilled'];

        foreach ($users as $index => $user) {
            foreach ($statuses as $statusIndex => $status) {
                $request = Request::create([
                    'user_id' => $user->id,
                    'status' => $status,
                    'dateCreated' => now()->subDays(rand(1, 30)),
                ]);

                $itemCount = rand(1, 3);
                for ($i = 0; $i < $itemCount; $i++) {
                    $item = $items->random();
                    RequestItem::create([
                        'request_id' => $request->id,
                        'item_id' => $item->id,
                        'quantity_requested' => rand(5, 50),
                    ]);
                }
            }
        }
    }
}
