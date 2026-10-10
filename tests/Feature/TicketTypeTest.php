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

    $ticketType = $this->event->ticketTypes()->sole();

    expect($ticketType->name)->toBe('一般')
        ->and($ticketType->price)->toBe(1500)
        ->and($ticketType->capacity)->toBe(30);
});

test('券種が無いときは案内文が表示される', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->assertSee('券種はまだありません。');
});

test('他のイベントの券種は一覧に表示されない', function () {
    TicketType::factory()->create(['name' => '他イベントの券種']);

    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->assertDontSee('他イベントの券種');
});

test('券種名50文字・価格0円・定員1人の境界値で追加できる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('ticketName', str_repeat('あ', 50))
        ->set('ticketPrice', '0')
        ->set('ticketCapacity', '1')
        ->call('addTicketType')
        ->assertHasNoErrors();

    expect($this->event->ticketTypes()->count())->toBe(1);
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
    '価格が文字列' => ['一般', 'abc', '10', 'ticketPrice'],
    '定員が空' => ['一般', '1000', '', 'ticketCapacity'],
    '定員が0' => ['一般', '1000', '0', 'ticketCapacity'],
    '定員が小数' => ['一般', '1000', '1.5', 'ticketCapacity'],
]);

test('券種の入力途中でもイベントの更新はできる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('title', '更新後のタイトル')
        ->set('ticketName', '入力途中')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->event->fresh()->title)->toBe('更新後のタイトル');
});

test('画面を開いた後にユーザーが入れ替わると券種を追加できない', function () {
    $this->actingAs($this->owner);
    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);

    $this->actingAs(User::factory()->create());

    $component
        ->set('ticketName', '一般')
        ->set('ticketPrice', '1500')
        ->set('ticketCapacity', '30')
        ->call('addTicketType')
        ->assertForbidden();

    expect(TicketType::count())->toBe(0);
});

test('ポリシーは主催者にだけ券種の追加を許可する', function () {
    expect($this->owner->can('create', [TicketType::class, $this->event]))->toBeTrue();
    expect(User::factory()->create()->can('create', [TicketType::class, $this->event]))->toBeFalse();
});

test('イベントを削除すると券種も削除される', function () {
    TicketType::factory()->count(2)->create(['event_id' => $this->event->id]);

    $this->event->delete();

    expect(TicketType::count())->toBe(0);
});

test('主催者は券種を削除できる', function () {
    $ticketType = TicketType::factory()->create(['event_id' => $this->event->id, 'name' => '削除する券種']);
    $other = TicketType::factory()->create(['event_id' => $this->event->id, 'name' => '残す券種']);

    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->call('deleteTicketType', $ticketType->id)
        ->assertDontSee('削除する券種')
        ->assertSee('残す券種');

    expect(TicketType::find($ticketType->id))->toBeNull()
        ->and(TicketType::find($other->id))->not->toBeNull();
});

test('他のイベントの券種は削除できない', function () {
    $foreign = TicketType::factory()->create();

    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->call('deleteTicketType', $foreign->id)
        ->assertNotFound();

    expect(TicketType::find($foreign->id))->not->toBeNull();
});

test('画面を開いた後にユーザーが入れ替わると券種を削除できない', function () {
    $ticketType = TicketType::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->owner);
    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);

    $this->actingAs(User::factory()->create());

    $component->call('deleteTicketType', $ticketType->id)->assertForbidden();

    expect(TicketType::find($ticketType->id))->not->toBeNull();
});

test('ポリシーは主催者にだけ券種の削除を許可する', function () {
    $ticketType = TicketType::factory()->create(['event_id' => $this->event->id]);

    expect($this->owner->can('delete', $ticketType))->toBeTrue();
    expect(User::factory()->create()->can('delete', $ticketType))->toBeFalse();
});
