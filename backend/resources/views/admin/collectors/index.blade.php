@extends('layouts.admin')

@section('title', 'Collectors Management')
@section('heading', 'Field Collectors')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create Collector -->
        <form method="POST" action="{{ route('admin.collectors.store') }}" class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-4 h-fit">
            @csrf
            <h2 class="font-display font-bold text-base">Create Collector Account</h2>
            <p class="text-xs text-[#7A7D78]">Only active collector accounts can log in to the Flutter mobile app.</p>

            <div>
                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. Kwame Mensah"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Phone (for Login) *</label>
                <input type="text" name="phone" required placeholder="+233240000001"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Email *</label>
                <input type="email" name="email" required placeholder="kwame@ucotrace.gh"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-[#5D5F5B] mb-1">Initial Password (min 8 chars) *</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>

            <button type="submit" class="w-full py-2.5 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">
                + Add Collector
            </button>
        </form>

        <!-- Collectors List -->
        <div class="lg:col-span-2 bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
            <h2 class="font-display font-bold text-base mb-4">Registered Collectors ({{ $collectors->count() }})</h2>

            @if($collectors->isEmpty())
                <div class="py-12 text-center text-sm text-[#7A7D78]">
                    No collectors created yet. Use the form on the left to add your field collectors.
                </div>
            @else
                <div class="space-y-4">
                    @foreach($collectors as $col)
                        <form method="POST" action="{{ route('admin.collectors.update', $col) }}"
                              class="p-4 rounded-2xl bg-[#F5F5F3] space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $col->is_active ? 'bg-[#B6F500]' : 'bg-red-500' }}"></span>
                                    <span class="font-semibold text-sm">{{ $col->name }}</span>
                                    <span class="text-xs text-[#7A7D78]">
                                        &bull; {{ $col->collection_events_count }} pickups ({{ number_format((float) $col->collection_events_sum_volume_liters, 1) }} L)
                                    </span>
                                </div>
                                <span class="text-xs text-[#7A7D78]">
                                    Last Sync: {{ $col->last_synced_at ? $col->last_synced_at->diffForHumans() : 'Never' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                <input type="text" name="name" value="{{ $col->name }}" required
                                       class="px-3 py-2 rounded-xl bg-white text-xs">
                                <input type="text" name="phone" value="{{ $col->phone }}" required
                                       class="px-3 py-2 rounded-xl bg-white text-xs">
                                <input type="email" name="email" value="{{ $col->email }}" required
                                       class="px-3 py-2 rounded-xl bg-white text-xs">
                                <input type="password" name="password" placeholder="New password (optional)"
                                       class="px-3 py-2 rounded-xl bg-white text-xs">
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <select name="is_active" class="px-3 py-1.5 rounded-full bg-white text-xs font-semibold">
                                    <option value="1" @selected($col->is_active)>Active</option>
                                    <option value="0" @selected(!$col->is_active)>Deactivated (Revokes App Access)</option>
                                </select>
                                <button type="submit" class="px-4 py-1.5 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">
                                    Update Collector
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
