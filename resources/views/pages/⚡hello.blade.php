<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('はじめてのページ')] class extends Component {
    public string $name = '小原';

    public int $count = 0;

    public function increment(): void
    {
        // $this->count++;
        $this->count += 10;
    }
};
?>

<div class="space-y-6">
    <flux:heading size="xl">こんにちは、{{ $name }}さん</flux:heading>

    <flux:text>このページは Livewire の単一ファイルコンポーネントです。上半分が PHP（クラス）、下半分が Blade（見た目）です。</flux:text>

    <div class="flex items-center gap-4">
        <flux:button wire:click="increment" variant="primary">押した回数を増やす</flux:button>
        <flux:text>押した回数: {{ $count }}</flux:text>
    </div>

    @if ($count >= 5) {{-- True 真 の時に中を実行する --}}
        <flux:callout icon="sparkles">
            <flux:callout.heading>5回以上押しました</flux:callout.heading>
        </flux:callout>
    @endif

    <ul class="list-disc pl-6">
        {{-- @マーク付きの構文　ディレクティブ --}}
        @foreach (['安比高原', '岩手山', '八幡平', '松尾鉱山'] as $place)
            <li wire:key="place-{{ $loop->index }}">{{ $place }}</li>
        @endforeach
    </ul>
</div>
