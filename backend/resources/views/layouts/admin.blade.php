<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Console') — UCO Trace Ghana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F5F3] text-[#141513] min-h-screen antialiased">
    <div class="flex min-h-screen">
        <!-- Stitch-Aligned Left Sidebar -->
        <aside class="w-68 bg-white border-r border-[#E6E7E3] flex flex-col justify-between p-5 shrink-0 shadow-[0_1px_12px_rgba(26,28,25,0.04)]">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-2 py-2 mb-7 group">
                    <div class="w-11 h-11 rounded-2xl bg-[#1A1C19] flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-[#B6F500]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                            <path d="M12 18a4 4 0 0 1-4-4"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="font-display font-extrabold text-base leading-tight text-[#141513]">UCO Trace</span>
                            <span class="px-1.5 py-0.5 rounded-md bg-[#B6F500] text-[#141513] text-[10px] font-bold tracking-wider">GH</span>
                        </div>
                        <div class="text-[11px] text-[#7A7D78] font-medium">Ghana Admin Console</div>
                    </div>
                </a>

                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#7A7D78]">Operations</div>

                <nav class="space-y-1.5 text-sm font-medium">
                    @php
                        $navItems = [
                            [
                                'route' => 'admin.dashboard',
                                'match' => 'admin.dashboard',
                                'label' => 'Overview',
                                'icon' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
                                'fill' => true,
                            ],
                            [
                                'route' => 'admin.vendors.index',
                                'match' => 'admin.vendors.*',
                                'label' => 'Vendors',
                                'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
                                'fill' => false,
                            ],
                            [
                                'route' => 'admin.containers.index',
                                'match' => 'admin.containers.*',
                                'label' => 'Containers & QR',
                                'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zm4 4h3v3h-3zm-4 3h3m1-7h3v3"/>',
                                'fill' => false,
                            ],
                            [
                                'route' => 'admin.events.index',
                                'match' => 'admin.events.*',
                                'label' => 'Collection Events',
                                'icon' => '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/>',
                                'fill' => false,
                            ],
                            [
                                'route' => 'admin.collectors.index',
                                'match' => 'admin.collectors.*',
                                'label' => 'Collectors',
                                'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                                'fill' => false,
                            ],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        @php $isActive = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="group flex items-center justify-between px-3.5 py-2.5 rounded-2xl transition-all {{ $isActive ? 'bg-[#1A1C19] text-white shadow-sm' : 'text-[#5D5F5B] hover:bg-[#F5F5F3] hover:text-[#141513]' }}">
                            <span class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors {{ $isActive ? 'bg-[#B6F500] text-[#141513]' : 'bg-[#F5F5F3] text-[#5D5F5B] group-hover:bg-[#B6F500]/30 group-hover:text-[#141513]' }}">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="{{ $item['fill'] ? 'currentColor' : 'none' }}" stroke="{{ $item['fill'] ? 'none' : 'currentColor' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        {!! $item['icon'] !!}
                                    </svg>
                                </span>
                                <span class="font-semibold">{{ $item['label'] }}</span>
                            </span>
                            @if($isActive)
                                <span class="w-2 h-2 rounded-full bg-[#B6F500] shadow-[0_0_8px_#B6F500]"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="space-y-3">
                <div class="p-3.5 rounded-2xl bg-[#B6F500]/15 border border-[#B6F500]/50">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#141513]">
                        <span class="w-6 h-6 rounded-lg bg-[#1A1C19] text-[#B6F500] flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <span>ISCC EU Pilot Ready</span>
                    </div>
                    <div class="text-[11px] text-[#5D5F5B] mt-1">Append-only custody &amp; audit active</div>
                </div>

                <div class="pt-3 border-t border-[#E6E7E3]">
                    <div class="flex items-center gap-2.5 px-1.5 mb-3">
                        <div class="w-9 h-9 rounded-full bg-[#1A1C19] text-[#B6F500] flex items-center justify-center font-display font-bold text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-[#141513] truncate">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-[#7A7D78] truncate">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-[#F5F5F3] text-xs font-semibold text-[#5D5F5B] hover:bg-[#1A1C19] hover:text-white transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="px-8 py-5 flex flex-wrap items-center justify-between gap-4 border-b border-[#E6E7E3] bg-white/80 backdrop-blur-sm">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-[#5D5F5B]">
                        <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        <span>Phase 1 &bull; Field Data Capture (Ghana Pilot)</span>
                    </div>
                    <h1 class="font-display font-extrabold text-xl text-[#141513] mt-0.5">@yield('heading', 'Overview')</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-[#B6F500]/20 border border-[#B6F500]/60 text-xs font-bold text-[#141513]">
                        <span class="w-2 h-2 rounded-full bg-[#1A1C19]"></span>
                        <svg class="w-3.5 h-3.5 text-[#141513]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>Append-Only Custody Ledger</span>
                    </span>
                    @yield('header_actions')
                </div>
            </header>

            <main class="p-8 flex-1">
                @if(session('success'))
                    <div class="mb-6 px-5 py-3.5 rounded-2xl bg-[#1A1C19] text-white text-sm flex items-center gap-3 shadow-sm">
                        <span class="w-7 h-7 rounded-xl bg-[#B6F500] text-[#141513] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 px-5 py-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm">
                        <div class="font-bold mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>Please review the highlighted fields:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
