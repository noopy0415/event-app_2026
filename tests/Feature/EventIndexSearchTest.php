<?php

use App\Models\Event;
use Livewire\Livewire;

test('タイトルで絞り込める', function () {
    Event::factory()->create(['title' => 'Laravel勉強会', 'venue' => '渋谷']);
    Event::factory()->create(['title' => 'デザイン交流会', 'venue' => '新宿']);

    Livewire::test('pages::events.index')
        ->set('search', 'Laravel')
        ->assertSee('Laravel勉強会')
        ->assertDontSee('デザイン交流会');
});

test('会場で絞り込める', function () {
    Event::factory()->create(['title' => 'Laravel勉強会', 'venue' => '渋谷']);
    Event::factory()->create(['title' => 'デザイン交流会', 'venue' => '新宿']);

    Livewire::test('pages::events.index')
        ->set('search', '新宿')
        ->assertSee('デザイン交流会')
        ->assertDontSee('Laravel勉強会');
});

test('検索語が空なら全件表示される', function () {
    Event::factory()->create(['title' => 'Laravel勉強会']);
    Event::factory()->create(['title' => 'デザイン交流会']);

    Livewire::test('pages::events.index')
        ->set('search', '')
        ->assertSee('Laravel勉強会')
        ->assertSee('デザイン交流会');
});

test('一致するイベントが無いときは専用のメッセージが出る', function () {
    Event::factory()->create(['title' => 'Laravel勉強会']);

    Livewire::test('pages::events.index')
        ->set('search', '存在しない語')
        ->assertSee('条件に合うイベントがありません。')
        ->assertDontSee('イベントはまだありません。');
});

test('% を入力してもワイルドカードとして扱われない', function () {
    Event::factory()->create(['title' => 'Laravel勉強会']);

    Livewire::test('pages::events.index')
        ->set('search', '%')
        ->assertDontSee('Laravel勉強会');
});
