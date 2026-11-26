<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In — UCO Trace Ghana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F5F3] text-[#141513] min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 bg-white rounded-[28px] shadow-sm overflow-hidden border border-[#E6E7E3]">
        <!-- Left Charcoal Brand Panel -->
        <div class="bg-[#1A1C19] text-white p-10 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-2xl bg-[#272A25] flex items-center justify-center mb-6">
                    <span class="w-4 h-4 rounded-full bg-[#B6F500]"></span>
                </div>
                <h1 class="font-display font-bold text-2xl tracking-tight">UCO Traceability Platform</h1>
                <p class="text-sm text-[#A3A6A0] mt-2 leading-relaxed">
                    Ghana Used Cooking Oil Field-to-Export Chain of Custody Console.
                </p>
            </div>
            <div class="mt-10 pt-6 border-t border-white/10 text-xs text-[#A3A6A0] space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                    <span>Append-only pickup &amp; GPS custody records</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#B6F500]"></span>
                    <span>QR container tracking &amp; SHA-256 photo evidence</span>
                </div>
            </div>
        </div>

        <!-- Right Login Form -->
        <div class="p-10 flex flex-col justify-center">
            <h2 class="font-display font-bold text-xl text-[#141513]">Admin Sign In</h2>
            <p class="text-sm text-[#7A7D78] mt-1 mb-6">Enter your administrator email and password.</p>

            @if($errors->any())
                <div class="mb-4 px-4 py-3 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-[#5D5F5B] mb-1.5">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           placeholder="admin@ucotrace.gh"
                           class="w-full px-4 py-3 rounded-2xl bg-[#F5F5F3] border border-transparent focus:border-[#1A1C19] focus:bg-white focus:outline-none text-sm">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-[#5D5F5B] mb-1.5">Password</label>
                    <input id="password" name="password" type="password" required
                           placeholder="Enter password"
                           class="w-full px-4 py-3 rounded-2xl bg-[#F5F5F3] border border-transparent focus:border-[#1A1C19] focus:bg-white focus:outline-none text-sm">
                </div>

                <label class="flex items-center gap-2 text-xs text-[#5D5F5B] cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-[#C8CAC4]">
                    <span>Keep session active</span>
                </label>

                <button type="submit"
                        class="w-full mt-2 bg-[#1A1C19] text-white rounded-full py-2.5 px-5 flex items-center justify-between text-sm font-semibold hover:opacity-95 transition">
                    <span>Sign In to Console</span>
                    <span class="w-8 h-8 rounded-full bg-[#B6F500] text-[#141513] flex items-center justify-center font-bold">&rarr;</span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
