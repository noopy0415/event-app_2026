<?php

use App\Models\Event;
use Livewire\Livewire;

test('タイトルの一部で絞り込める', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング', 'venue' => '札幌市民ホール']);
    Event::factory()->create(['title' => '雪上ヨガ体験', 'venue' => '札幌市民ホール']);

    Livewire::test('pages::events.index')
        ->set('search', 'トレッキング')
        ->assertSee('朝焼けトレッキング')
        ->assertDontSee('雪上ヨガ体験');
});

test('会場の一部で絞り込める', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング', 'venue' => '別府市民ホール']);
    Event::factory()->create(['title' => '雪上ヨガ体験', 'venue' => '札幌市民ホール']);

    Livewire::test('pages::events.index')
        ->set('search', '別府')
        ->assertSee('朝焼けトレッキング')
        ->assertDontSee('雪上ヨガ体験');
});

test('一致するイベントが無いときは専用の文言が出る', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング']);

    Livewire::test('pages::events.index')
        ->set('search', '存在しない語')
        ->assertSee('条件に一致するイベントはありません。')
        ->assertDontSee('朝焼けトレッキング');
});

test('検索語を空に戻すと全件が表示される', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング']);
    Event::factory()->create(['title' => '雪上ヨガ体験']);

    Livewire::test('pages::events.index')
        ->set('search', 'トレッキング')
        ->set('search', '')
        ->assertSee('朝焼けトレッキング')
        ->assertSee('雪上ヨガ体験');
});

test('ワイルドカード文字は文字として検索される', function () {
    Event::factory()->create(['title' => '100%の力で走る会']);
    Event::factory()->create(['title' => '雪上ヨガ体験']);

    Livewire::test('pages::events.index')
        ->set('search', '%')
        ->assertSee('100%の力で走る会')
        ->assertDontSee('雪上ヨガ体験');
});
