<?php

use App\Models\Event;
use App\Models\TicketType;
use Livewire\Livewire;

test('券種が無いイベントは「─」と表示される', function () {
    Event::factory()->create();

    Livewire::test('pages::events.index')
        ->assertSee('価格')
        ->assertSee('─');
});

test('最安の券種が 0 円なら「無料」と表示される', function () {
    $event = Event::factory()->create();
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 0]);
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 3000]);

    Livewire::test('pages::events.index')
        ->assertSee('無料')
        ->assertDontSee('3,000円〜');
});

test('複数の券種があれば最安の価格が「円〜」付きで表示される', function () {
    $event = Event::factory()->create();
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 3000]);
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 1500]);

    Livewire::test('pages::events.index')
        ->assertSee('1,500円〜')
        ->assertDontSee('3,000円〜');
});

test('イベントを削除すると券種も削除される', function () {
    $event = Event::factory()->create();
    TicketType::factory()->count(2)->create(['event_id' => $event->id]);

    $event->delete();

    expect(TicketType::count())->toBe(0);
});
