<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->event = Event::factory()->create(['user_id' => $this->owner->id]);
});

// ---- 登録 ----

test('ログイン済みのユーザーはイベントを登録でき、自分が主催者になる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.create')
        ->set('title', '新しいイベント')
        ->set('description', '説明文です。')
        ->set('venue', '岩手山')
        ->set('starts_at', '2026-12-01 10:00')
        ->set('ends_at', '2026-12-01 12:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'));

    $created = Event::where('title', '新しいイベント')->first();

    expect($created)->not->toBeNull()
        ->and($created->user_id)->toBe($this->owner->id);
});

test('ゲストはイベント登録画面を開けずログインへ飛ばされる', function () {
    $this->get(route('events.create'))->assertRedirect(route('login'));
});

test('メール未確認のユーザーはイベント登録画面を開けない', function () {
    $this->actingAs(User::factory()->unverified()->create());

    $this->get(route('events.create'))->assertRedirect(route('verification.notice'));
});

test('イベント登録の入力値が不正なら登録できない', function (array $overrides, string $errorField) {
    $this->actingAs($this->owner);
    $before = Event::count();

    $input = array_merge([
        'title' => 'タイトル',
        'description' => '説明',
        'venue' => '会場',
        'starts_at' => '2026-12-01 10:00',
        'ends_at' => '2026-12-01 12:00',
    ], $overrides);

    $component = Livewire::test('pages::events.create');
    foreach ($input as $key => $value) {
        $component->set($key, $value);
    }

    $component->call('save')->assertHasErrors([$errorField]);

    expect(Event::count())->toBe($before);
})->with([
    'タイトルが空' => [['title' => ''], 'title'],
    'タイトルが101文字' => [['title' => str_repeat('あ', 101)], 'title'],
    '説明が空' => [['description' => ''], 'description'],
    '説明が2001文字' => [['description' => str_repeat('あ', 2001)], 'description'],
    '会場が空' => [['venue' => ''], 'venue'],
    '会場が101文字' => [['venue' => str_repeat('あ', 101)], 'venue'],
    '開始日時が空' => [['starts_at' => ''], 'starts_at'],
    '開始日時が日付でない' => [['starts_at' => 'あした'], 'starts_at'],
    '終了日時が空' => [['ends_at' => ''], 'ends_at'],
    '終了が開始と同時刻' => [['ends_at' => '2026-12-01 10:00'], 'ends_at'],
    '終了が開始より前' => [['ends_at' => '2026-12-01 09:00'], 'ends_at'],
]);

test('タイトル100文字・説明2000文字ちょうどなら登録できる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.create')
        ->set('title', str_repeat('あ', 100))
        ->set('description', str_repeat('あ', 2000))
        ->set('venue', str_repeat('あ', 100))
        ->set('starts_at', '2026-12-01 10:00')
        ->set('ends_at', '2026-12-01 10:01')
        ->call('save')
        ->assertHasNoErrors();
});

// ---- 編集 ----

test('主催者はイベントを編集でき、内容が更新される', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->assertSet('title', $this->event->title)
        ->set('title', '更新後のタイトル')
        ->set('venue', '更新後の会場')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'));

    $fresh = $this->event->fresh();

    expect($fresh->title)->toBe('更新後のタイトル')
        ->and($fresh->venue)->toBe('更新後の会場')
        ->and($fresh->user_id)->toBe($this->owner->id);
});

test('主催者は編集画面を HTTP で開ける', function () {
    $this->actingAs($this->owner)
        ->get(route('events.edit', $this->event))
        ->assertOk();
});

test('ゲストはイベント編集画面を開けずログインへ飛ばされる', function () {
    $this->get(route('events.edit', $this->event))->assertRedirect(route('login'));
});

test('メール未確認のユーザーはイベント編集画面を開けない', function () {
    $this->actingAs(User::factory()->unverified()->create());

    $this->get(route('events.edit', $this->event))->assertRedirect(route('verification.notice'));
});

test('他人は編集画面に HTTP でアクセスすると 403 になる', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.edit', $this->event))
        ->assertForbidden();
});

test('主催者以外は編集内容を保存できない', function () {
    $originalTitle = $this->event->title;

    $this->actingAs($this->owner);
    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);

    // 画面を開いた後でログイン中のユーザーが他人に入れ替わった場合も拒否される
    $this->actingAs(User::factory()->create());

    $component
        ->set('title', '乗っ取りタイトル')
        ->call('save')
        ->assertForbidden();

    expect($this->event->fresh()->title)->toBe($originalTitle);
});

test('イベント編集の入力値が不正なら更新できない', function (string $field, string $value) {
    $originalTitle = $this->event->title;

    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field]);

    expect($this->event->fresh()->title)->toBe($originalTitle);
})->with([
    'タイトルが空' => ['title', ''],
    'タイトルが101文字' => ['title', str_repeat('あ', 101)],
    '説明が空' => ['description', ''],
    '会場が空' => ['venue', ''],
    '開始日時が日付でない' => ['starts_at', 'あした'],
    '終了日時が空' => ['ends_at', ''],
]);

test('終了日時が開始日時以前なら更新できない', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->set('starts_at', '2026-12-01T10:00')
        ->set('ends_at', '2026-12-01T09:00')
        ->call('save')
        ->assertHasErrors(['ends_at']);
});

// ---- 削除 ----

test('主催者はイベントを削除できる', function () {
    $this->actingAs($this->owner);

    Livewire::test('pages::events.edit', ['event' => $this->event])
        ->call('delete')
        ->assertRedirect(route('events.index'));

    expect(Event::find($this->event->id))->toBeNull();
});

test('主催者以外はイベントを削除できない', function () {
    $this->actingAs($this->owner);
    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);

    $this->actingAs(User::factory()->create());

    $component->call('delete')->assertForbidden();

    expect(Event::find($this->event->id))->not->toBeNull();
});

// ---- 一覧の認可 ----

test('一覧はゲストにも表示され、編集ボタンは主催者にだけ出る', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee($this->event->title)
        ->assertDontSee(route('events.edit', $this->event));

    $this->actingAs(User::factory()->create())
        ->get(route('events.index'))
        ->assertDontSee(route('events.edit', $this->event));

    $this->actingAs($this->owner)
        ->get(route('events.index'))
        ->assertSee(route('events.edit', $this->event));
});

// ---- 追記: 不足していた組み合わせ ----

test('主催者以外は編集コンポーネントを開けず 403 になる', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $this->event])->assertForbidden();
});

test('存在しないイベントの編集画面は 404 になる', function () {
    $this->actingAs($this->owner)
        ->get(route('events.edit', 999999))
        ->assertNotFound();
});

test('イベント編集の入力値が不正なら更新できない(追加ケース)', function (array $overrides, string $errorField) {
    $this->actingAs($this->owner);
    $original = $this->event->only(['title', 'description', 'venue']);

    $component = Livewire::test('pages::events.edit', ['event' => $this->event]);
    foreach ($overrides as $key => $value) {
        $component->set($key, $value);
    }

    $component->call('save')->assertHasErrors([$errorField]);

    expect($this->event->fresh()->only(['title', 'description', 'venue']))->toBe($original);
})->with([
    '説明が2001文字' => [['description' => str_repeat('あ', 2001)], 'description'],
    '会場が101文字' => [['venue' => str_repeat('あ', 101)], 'venue'],
    '開始日時が空' => [['starts_at' => ''], 'starts_at'],
    '終了が開始と同時刻' => [['starts_at' => '2026-12-01T10:00', 'ends_at' => '2026-12-01T10:00'], 'ends_at'],
]);
