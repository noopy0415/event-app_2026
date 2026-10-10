<?php

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('イベントを編集')] class extends Component {
    public Event $event;

    #[Validate('required|string|max:100')]
    public string $title = '';

    #[Validate('required|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:100')]
    public string $venue = '';

    #[Validate('required|date')]
    public string $starts_at = '';

    #[Validate('required|date|after:starts_at')]
    public string $ends_at = '';

    public function mount(Event $event): void
    {
        // 自分が作ったイベントでなければ 403
        abort_unless($event->isOwnedBy(auth()->user()), 403);

        $this->event = $event;
        $this->title = $event->title;
        $this->description = $event->description;
        $this->venue = $event->venue;
        $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
        $this->ends_at = $event->ends_at->format('Y-m-d\TH:i');
    }

    // 券種の追加フォーム用。save() の validate() に混ざらないよう #[Validate] は付けない
    public string $ticketName = '';

    public string $ticketPrice = '';

    public string $ticketCapacity = '';

    /**
     * このイベントの券種を登録順に取り出す。
     *
     * @return Collection<int, TicketType>
     */
    #[Computed]
    public function ticketTypes(): Collection
    {
        return $this->event->ticketTypes()->orderBy('id')->get();
    }

    public function addTicketType(): void
    {
        $this->authorize('create', [TicketType::class, $this->event]);

        $validated = $this->validate([
            'ticketName' => 'required|string|max:50',
            'ticketPrice' => 'required|integer|min:0',
            'ticketCapacity' => 'required|integer|min:1',
        ], attributes: [
            'ticketName' => '券種名',
            'ticketPrice' => '価格',
            'ticketCapacity' => '定員',
        ]);

        $this->event->ticketTypes()->create([
            'name' => $validated['ticketName'],
            'price' => $validated['ticketPrice'],
            'capacity' => $validated['ticketCapacity'],
        ]);

        $this->reset('ticketName', 'ticketPrice', 'ticketCapacity');
        unset($this->ticketTypes);
    }

    public function deleteTicketType(int $ticketTypeId): void
    {
        // このイベントの券種だけを対象にする（他イベントの ID は 404）
        $ticketType = $this->event->ticketTypes()->findOrFail($ticketTypeId);

        $this->authorize('delete', $ticketType);

        $ticketType->delete();

        unset($this->ticketTypes);
    }

    public function save(): void
    {
        abort_unless($this->event->isOwnedBy(auth()->user()), 403);

        $this->event->update($this->validate());

        session()->flash('status', 'イベントを更新しました。');

        $this->redirectRoute('events.index', navigate: true);
    }

    public function delete(): void
    {
        abort_unless($this->event->isOwnedBy(auth()->user()), 403);

        $this->event->delete();

        session()->flash('status', 'イベントを削除しました。');

        $this->redirectRoute('events.index', navigate: true);
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    <flux:heading size="xl">イベントを編集</flux:heading>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="title" label="タイトル" />
        <flux:textarea wire:model="description" label="説明" rows="6" />
        <flux:input wire:model="venue" label="会場" />

        <div class="grid gap-6 sm:grid-cols-2">
            <x-drum-picker wire:model="starts_at" label="開始日時" />
            <x-drum-picker wire:model="ends_at" label="終了日時" />
        </div>

        <div class="flex justify-between">
            <flux:button wire:click="delete" wire:confirm="このイベントを削除しますか？" variant="danger" icon="trash">削除</flux:button>
            <div class="flex gap-3">
                <flux:button :href="route('events.index')" variant="ghost" wire:navigate>キャンセル</flux:button>
                <flux:button type="submit" variant="primary">更新する</flux:button>
            </div>
        </div>
    </form>

    <flux:separator />

    <section class="space-y-4">
        <flux:heading size="lg">券種</flux:heading>

        @if ($this->ticketTypes->isEmpty())
            <flux:text>券種はまだありません。</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>券種名</flux:table.column>
                    <flux:table.column>価格</flux:table.column>
                    <flux:table.column>定員</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->ticketTypes as $ticketType)
                        <flux:table.row wire:key="ticket-type-{{ $ticketType->id }}">
                            <flux:table.cell variant="strong">{{ $ticketType->name }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($ticketType->price) }}円</flux:table.cell>
                            <flux:table.cell>{{ number_format($ticketType->capacity) }}人</flux:table.cell>
                            <flux:table.cell>
                                <flux:button wire:click="deleteTicketType({{ $ticketType->id }})" wire:confirm="この券種を削除しますか？" size="sm" variant="danger" icon="trash">削除</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif

        <form wire:submit="addTicketType" class="grid items-start gap-4 sm:grid-cols-[2fr_1fr_1fr_auto]">
            <flux:input wire:model="ticketName" label="券種名" placeholder="一般" />
            <flux:input wire:model="ticketPrice" label="価格（円）" type="number" min="0" />
            <flux:input wire:model="ticketCapacity" label="定員" type="number" min="1" />
            <flux:button type="submit" icon="plus" class="sm:mt-6">追加</flux:button>
        </form>
    </section>
</div>
