<?php

use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'actionFilter'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'actionFilter');
        $this->resetPage();
    }

    public function with(): array
    {
        Gate::authorize('audit-logs.view');

        return [
            'logs' => AuditLog::with('user:id,name')
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('description', 'like', '%'.$this->search.'%')->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$this->search.'%'))))
                ->when($this->actionFilter !== '', fn ($query) => $query->where('action', $this->actionFilter))
                ->latest('id')->paginate(15),
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Audit log" description="A read-only history of important administrative changes." />
    <div class="grid items-end gap-3 sm:grid-cols-3"><x-ui.search-input wire:model.live.debounce.300ms="search" label="Search history" placeholder="Action or actor name" /><x-ui.filter-select wire:model.live="actionFilter" label="Action">@foreach($actions as $action)<option value="{{ $action }}">{{ $action }}</option>@endforeach</x-ui.filter-select><x-ui.button wire:click="resetFilters">Reset filters</x-ui.button></div>
    <x-ui.table caption="Administrative audit history">
        <thead><tr><x-ui.table-header>When</x-ui.table-header><x-ui.table-header>Actor</x-ui.table-header><x-ui.table-header>Action</x-ui.table-header><x-ui.table-header>Changes</x-ui.table-header></tr></thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">@forelse($logs as $log)<tr wire:key="audit-{{ $log->id }}">
            <td class="whitespace-nowrap px-4 py-4">{{ App\Models\Setting::formatDate($log->created_at) }}<p class="text-xs text-zinc-500">{{ $log->created_at->copy()->setTimezone(app('starter.settings')['timezone'])->format('H:i:s') }}</p></td>
            <td class="px-4 py-4">{{ $log->user?->name ?? 'Deleted user / system' }}<p class="text-xs text-zinc-500">{{ $log->ip_address }}</p></td>
            <td class="px-4 py-4">{{ $log->action }}<p class="text-xs text-zinc-500">{{ class_basename($log->auditable_type ?? '') }} {{ $log->auditable_id ? '#'.$log->auditable_id : '' }}</p></td>
            <td class="px-4 py-4"><details><summary class="cursor-pointer text-accent-content focus-visible:outline-2 focus-visible:outline-accent">View changes</summary><div class="mt-2 max-w-lg space-y-2 text-xs"><p class="font-medium">Before</p><pre class="whitespace-pre-wrap break-all">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><p class="font-medium">After</p><pre class="whitespace-pre-wrap break-all">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details></td>
        </tr>@empty<tr><td colspan="4"><x-ui.empty-state title="No audit entries" description="Administrative changes will appear here." /></td></tr>@endforelse</tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$logs" />
</div>
