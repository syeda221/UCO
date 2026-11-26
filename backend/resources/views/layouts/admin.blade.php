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
        <!-- Slim Left Sidebar -->
        <aside class="w-64 bg-white border-r border-[#E6E7E3] flex flex-col justify-between p-5 shrink-0">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-2 py-2 mb-8">
                    <div class="w-10 h-10 rounded-2xl bg-[#1A1C19] flex items-center justify-center">
                        <span class="w-3.5 h-3.5 rounded-full bg-[#B6F500]"></span>
                    </div>
                    <div>
                        <div class="font-display font-bold text-base leading-tight text-[#141513]">UCO Trace</div>
                        <div class="text-xs text-[#7A7D78]">Ghana Admin Console</div>
                    </div>
                </a>

                <nav class="space-y-1.5 text-sm font-medium">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center justify-between px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#1A1C19] text-white' : 'text-[#5D5F5B] hover:bg-[#F5F5F3]' }}">
                        <span>Overview</span>
                        @if(request()->routeIs('admin.dashboard'))
                            <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        @endif
                    </a>

                    <a href="{{ route('admin.vendors.index') }}"
                       class="flex items-center justify-between px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.vendors.*') ? 'bg-[#1A1C19] text-white' : 'text-[#5D5F5B] hover:bg-[#F5F5F3]' }}">
                        <span>Vendors</span>
                        @if(request()->routeIs('admin.vendors.*'))
                            <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        @endif
                    </a>

                    <a href="{{ route('admin.containers.index') }}"
                       class="flex items-center justify-between px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.containers.*') ? 'bg-[#1A1C19] text-white' : 'text-[#5D5F5B] hover:bg-[#F5F5F3]' }}">
                        <span>Containers &amp; QR</span>
                        @if(request()->routeIs('admin.containers.*'))
                            <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        @endif
                    </a>

                    <a href="{{ route('admin.events.index') }}"
                       class="flex items-center justify-between px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.events.*') ? 'bg-[#1A1C19] text-white' : 'text-[#5D5F5B] hover:bg-[#F5F5F3]' }}">
                        <span>Collection Events</span>
                        @if(request()->routeIs('admin.events.*'))
                            <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        @endif
                    </a>

                    <a href="{{ route('admin.collectors.index') }}"
                       class="flex items-center justify-between px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.collectors.*') ? 'bg-[#1A1C19] text-white' : 'text-[#5D5F5B] hover:bg-[#F5F5F3]' }}">
                        <span>Collectors</span>
                        @if(request()->routeIs('admin.collectors.*'))
                            <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        @endif
                    </a>
                </nav>
            </div>

            <div class="pt-4 border-t border-[#E6E7E3]">
                <div class="px-2 mb-3">
                    <div class="text-xs font-semibold text-[#141513] truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-[#7A7D78] truncate">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 rounded-full text-xs font-semibold text-[#5D5F5B] hover:bg-[#F5F5F3] transition">
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="px-8 py-5 flex items-center justify-between border-b border-[#E6E7E3] bg-[#F5F5F3]">
                <div>
                    <div class="text-xs font-medium text-[#7A7D78]">Phase 1 • Field Data Capture (Ghana Pilot)</div>
                    <h1 class="font-display font-bold text-xl text-[#141513]">@yield('heading', 'Overview')</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white text-xs font-medium text-[#141513] border border-[#E6E7E3]">
                        <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                        Append-Only Custody Ledger
                    </span>
                    @yield('header_actions')
                </div>
            </header>

            <main class="p-8 flex-1">
                @if(session('success'))
                    <div class="mb-6 px-5 py-3.5 rounded-2xl bg-[#1A1C19] text-white text-sm flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#B6F500]"></span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 px-5 py-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm">
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
