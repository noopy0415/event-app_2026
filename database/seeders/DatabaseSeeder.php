<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);

        Event::factory()->count(5)->create(['user_id' => $user->id])->each(function (Event $event, int $index): void {
            // 先頭は券種なしのまま残し、残りは一般・学生の券種を付ける
            if ($index === 0) {
                return;
            }

            $event->ticketTypes()->createMany([
                ['name' => '一般', 'price' => $index === 1 ? 0 : 3000, 'capacity' => 50],
                ['name' => '学生', 'price' => $index === 1 ? 0 : 1500, 'capacity' => 20],
            ]);
        });
    }
}
