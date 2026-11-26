@extends('layouts.admin')

@section('title', 'Containers & QR Registry')
@section('heading', 'Qualiplast Containers & QR Stickers')

@section('header_actions')
    <a href="{{ route('admin.containers.print-qr', request()->only('vendor_id')) }}" target="_blank"
       class="px-4 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold flex items-center gap-2 hover:opacity-90">
        <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
        Print QR Stickers (PDF)
    </a>
@endsection

@section('content')
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-[20px] p-5 border border-[#E6E7E3]">
            <div class="text-xs text-[#7A7D78]">Total Containers</div>
            <div class="font-display font-bold text-2xl mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-[20px] p-5 border border-[#E6E7E3]">
            <div class="text-xs text-[#7A7D78]">Active QR Units</div>
            <div class="font-display font-bold text-2xl mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white rounded-[20px] p-5 border border-[#E6E7E3]">
            <div class="text-xs text-[#7A7D78]">Assigned to Vendors</div>
            <div class="font-display font-bold text-2xl mt-1">{{ $stats['assigned'] }}</div>
        </div>
        <div class="bg-white rounded-[20px] p-5 border border-[#E6E7E3]">
            <div class="text-xs text-[#7A7D78]">Damaged</div>
            <div class="font-display font-bold text-2xl mt-1">{{ $stats['damaged'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Single Add -->
        <form method="POST" action="{{ route('admin.containers.store') }}" class="bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-3">
            @csrf
            <h2 class="font-display font-bold text-base">Register Single Container</h2>
            <input type="text" name="qr_code" placeholder="QR Code (e.g. QP-20L-001)" required
                   class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono uppercase">
            <div class="grid grid-cols-2 gap-3">
                <input type="text" name="qualiplast_tier" value="20L Jerrycan" required
                       class="px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                <input type="number" step="0.1" name="capacity_liters" value="20" required
                       class="px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
            </div>
            <select name="assigned_vendor_id" class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                <option value="">Unassigned (Depot Stock)</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}">{{ $v->vendor_code }} — {{ $v->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="w-full py-2.5 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">
                + Add Container
            </button>
        </form>

        <!-- Batch Generate -->
        <form method="POST" action="{{ route('admin.containers.batch') }}" class="lg:col-span-2 bg-white rounded-[24px] p-6 border border-[#E6E7E3] space-y-3">
            @csrf
            <div class="flex items-center justify-between">
                <h2 class="font-display font-bold text-base">Batch Generate QR Stickers</h2>
                <span class="text-xs text-[#7A7D78]">Auto-creates unique sequential QR codes</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Prefix</label>
                    <input type="text" name="prefix" value="QP-20L" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm font-mono uppercase">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Qualiplast Tier</label>
                    <input type="text" name="qualiplast_tier" value="20L Jerrycan" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Capacity (L)</label>
                    <input type="number" step="0.1" name="capacity_liters" value="20" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5D5F5B] mb-1">Quantity (1–100)</label>
                    <input type="number" name="quantity" value="10" min="1" max="100" required
                           class="w-full px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <select name="assigned_vendor_id" class="flex-1 min-w-[220px] px-4 py-2.5 rounded-2xl bg-[#F5F5F3] text-sm">
                    <option value="">Leave Unassigned (Depot Stock)</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">Assign all to {{ $v->vendor_code }} — {{ $v->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-6 py-2.5 rounded-full bg-[#B6F500] text-[#141513] text-xs font-bold">
                    Generate QR Batch
                </button>
            </div>
        </form>
    </div>

    <!-- Filter & List -->
    <div class="bg-white rounded-[24px] p-6 border border-[#E6E7E3]">
        <form method="GET" action="{{ route('admin.containers.index') }}" class="flex flex-wrap gap-3 mb-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search QR code..."
                   class="px-4 py-2 rounded-full bg-[#F5F5F3] text-sm flex-1 min-w-[180px]">
            <select name="status" class="px-4 py-2 rounded-full bg-[#F5F5F3] text-sm">
                <option value="">All Statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="damaged" @selected(request('status') === 'damaged')>Damaged</option>
                <option value="retired" @selected(request('status') === 'retired')>Retired</option>
            </select>
            <select name="vendor_id" class="px-4 py-2 rounded-full bg-[#F5F5F3] text-sm">
                <option value="">All Vendors</option>
                <option value="unassigned" @selected(request('vendor_id') === 'unassigned')>Unassigned Only</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" @selected((string) request('vendor_id') === (string) $v->id)>{{ $v->vendor_code }} — {{ $v->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-5 py-2 rounded-full bg-[#1A1C19] text-white text-xs font-semibold">Filter</button>
        </form>

        @if($containers->isEmpty())
            <div class="py-12 text-center text-sm text-[#7A7D78]">
                No containers registered yet. Add one or generate a QR batch above.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-[#7A7D78] border-b border-[#E6E7E3]">
                        <tr>
                            <th class="py-3 pr-4">QR Preview</th>
                            <th class="py-3 pr-4">QR Code</th>
                            <th class="py-3 pr-4">Qualiplast Tier</th>
                            <th class="py-3 pr-4">Assigned Vendor &amp; Status</th>
                            <th class="py-3 pr-4">Last Scanned</th>
                            <th class="py-3">Sticker</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F1EE]">
                        @foreach($containers as $container)
                            <tr>
                                <td class="py-3 pr-4">
                                    <div class="w-12 h-12 p-1 rounded-xl bg-[#F5F5F3] flex items-center justify-center">
                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(40)->generate($container->qr_code) !!}
                                    </div>
                                </td>
                                <td class="py-3 pr-4 font-mono text-xs font-bold">{{ $container->qr_code }}</td>
                                <td class="py-3 pr-4 text-xs">
                                    {{ $container->qualiplast_tier }} ({{ number_format($container->capacity_liters, 0) }}L)
                                </td>
                                <td class="py-3 pr-4">
                                    <form method="POST" action="{{ route('admin.containers.update', $container) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <select name="assigned_vendor_id" class="px-3 py-1.5 rounded-full bg-[#F5F5F3] text-xs">
                                            <option value="">Unassigned</option>
                                            @foreach($vendors as $v)
                                                <option value="{{ $v->id }}" @selected($container->assigned_vendor_id === $v->id)>
                                                    {{ $v->vendor_code }} — {{ $v->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="status" class="px-3 py-1.5 rounded-full bg-[#F5F5F3] text-xs">
                                            @foreach(\App\Models\Container::STATUSES as $st)
                                                <option value="{{ $st }}" @selected($container->status === $st)>{{ ucfirst($st) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-3 py-1.5 rounded-full bg-[#1A1C19] text-white text-[11px] font-semibold">Save</button>
                                    </form>
                                </td>
                                <td class="py-3 pr-4 text-xs text-[#7A7D78]">
                                    {{ $container->last_scanned_at ? $container->last_scanned_at->format('d M Y, H:i') : 'Never' }}
                                </td>
                                <td class="py-3">
                                    <a href="{{ route('admin.containers.print-qr', ['ids' => $container->id]) }}" target="_blank"
                                       class="text-xs font-semibold underline">Print</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $containers->links() }}</div>
        @endif
    </div>
@endsection
