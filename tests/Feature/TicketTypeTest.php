<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->event = Event::factory()->create(['user_id' => $this->owner->id]);
});

test('主催者は券種を追加でき、一覧に表示される', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', '1500')
        ->set('ticketCapacity', '30')
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSet('ticketName', '')
        ->assertSee('一般')
        ->assertSee('1,500円')
        ->assertSee('30人');

    expect($this->event->ticketTypes()->count())->toBe(1);
});

test('価格 0 円の券種を追加できる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('ticketName', '無料枠')
        ->set('ticketPrice', '0')
        ->set('ticketCapacity', '10')
        ->call('addTicketType')
        ->assertHasNoErrors();

    expect($this->event->ticketTypes()->first()->price)->toBe(0);
});

test('券種の入力値が不正なら追加できない', function (string $name, string $price, string $capacity, string $errorField) {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('ticketName', $name)
        ->set('ticketPrice', $price)
        ->set('ticketCapacity', $capacity)
        ->call('addTicketType')
        ->assertHasErrors([$errorField]);

    expect(TicketType::count())->toBe(0);
})->with([
    '券種名が空' => ['', '1000', '10', 'ticketName'],
    '券種名が51文字' => [str_repeat('あ', 51), '1000', '10', 'ticketName'],
    '価格が空' => ['一般', '', '10', 'ticketPrice'],
    '価格が負数' => ['一般', '-1', '10', 'ticketPrice'],
    '価格が小数' => ['一般', '10.5', '10', 'ticketPrice'],
    '定員が空' => ['一般', '1000', '', 'ticketCapacity'],
    '定員が 0' => ['一般', '1000', '0', 'ticketCapacity'],
]);

test('主催者は券種を削除できる', function () {
    $ticketType = TicketType::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->call('deleteTicketType', $ticketType->id)
        ->assertHasNoErrors();

    expect(TicketType::count())->toBe(0);
});

test('他のイベントの券種は削除できない', function () {
    $otherTicketType = TicketType::factory()->create();

    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->call('deleteTicketType', $otherTicketType->id)
        ->assertNotFound();

    expect(TicketType::count())->toBe(1);
});

test('主催者以外は編集画面を開けない', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->assertForbidden();
});

test('券種の入力中でもイベントの更新は壊れない', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('title', '更新後のタイトル')
        ->set('ticketName', '入力途中')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->event->fresh()->title)->toBe('更新後のタイトル');
});

test('主催者以外は券種を追加できない', function () {
    $this->actingAs($this->owner);
    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);

    // 画面を開いた後でログイン中のユーザーが他人に入れ替わった場合も拒否される
    $this->actingAs(User::factory()->create());

    $component
        ->set('ticketName', '一般')
        ->set('ticketPrice', '1500')
        ->set('ticketCapacity', '30')
        ->call('addTicketType')
        ->assertForbidden();

    expect(TicketType::count())->toBe(0);
});

test('主催者以外は券種を削除できない', function () {
    $ticketType = TicketType::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->owner);
    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);

    $this->actingAs(User::factory()->create());

    $component
        ->call('deleteTicketType', $ticketType->id)
        ->assertForbidden();

    expect(TicketType::count())->toBe(1);
});

test('ポリシーは主催者にだけ券種の追加と削除を許可する', function () {
    $stranger = User::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $this->event->id]);

    expect($this->owner->can('create', [TicketType::class, $this->event]))->toBeTrue()
        ->and($this->owner->can('delete', $ticketType))->toBeTrue()
        ->and($stranger->can('create', [TicketType::class, $this->event]))->toBeFalse()
        ->and($stranger->can('delete', $ticketType))->toBeFalse();
});

test('券種名ちょうど50文字・定員1・価格0で追加できる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('ticketName', str_repeat('あ', 50))
        ->set('ticketPrice', '0')
        ->set('ticketCapacity', '1')
        ->call('addTicketType')
        ->assertHasNoErrors();

    expect(TicketType::count())->toBe(1);
});

test('券種の価格・定員が数値でない場合は追加できない', function (string $price, string $capacity, string $errorField) {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', $price)
        ->set('ticketCapacity', $capacity)
        ->call('addTicketType')
        ->assertHasErrors([$errorField]);

    expect(TicketType::count())->toBe(0);
})->with([
    '価格が文字列' => ['abc', '10', 'ticketPrice'],
    '定員が文字列' => ['1000', 'abc', 'ticketCapacity'],
    '定員が小数' => ['1000', '1.5', 'ticketCapacity'],
    '定員が負数' => ['1000', '-5', 'ticketCapacity'],
]);

test('存在しない券種の削除は 404 になる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->call('deleteTicketType', 999999)
        ->assertNotFound();
});

test('ゲストとメール未確認のユーザーは券種を扱う編集画面を開けない', function () {
    $this->get(route('events.edit', $this->event))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('events.edit', $this->event))
        ->assertRedirect(route('verification.notice'));
});
