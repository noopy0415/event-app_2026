<?php

use App\Models\Event;
use App\Models\TicketType;
use Livewire\Livewire;

test('一番安い券種の価格が「〜」付きで表示される', function () {
    $event = Event::factory()->create();
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 3000]);
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 1500]);

    Livewire::test('pages::events.index')
        ->assertSee('1,500円〜')
        ->assertDontSee('3,000円');
});

test('最安の券種が0円なら「無料」と表示される', function () {
    $event = Event::factory()->create();
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 0]);
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 2000]);

    Livewire::test('pages::events.index')->assertSee('無料');
});

test('券種が無いイベントは「─」と表示される', function () {
    Event::factory()->create();

    Livewire::test('pages::events.index')
        ->assertSee('─')
        ->assertDontSee('円〜')
        ->assertDontSee('無料');
});

test('他のイベントの券種価格は混ざらない', function () {
    $event = Event::factory()->create();
    $cheapOther = Event::factory()->create();
    TicketType::factory()->create(['event_id' => $event->id, 'price' => 4000]);
    TicketType::factory()->create(['event_id' => $cheapOther->id, 'price' => 500]);

    Livewire::test('pages::events.index')
        ->assertSee('4,000円〜')
        ->assertSee('500円〜');
});
