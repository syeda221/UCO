<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollectorController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(): View
    {
        $collectors = User::where('role', User::ROLE_COLLECTOR)
            ->withCount('collectionEvents')
            ->withSum('collectionEvents', 'volume_liters')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.collectors.index', compact('collectors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $collector = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_COLLECTOR,
            'is_active' => true,
        ]);

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'collector_created',
            entityType: 'user',
            entityId: $collector->id,
            metadata: ['name' => $collector->name, 'phone' => $collector->phone],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.collectors.index')
            ->with('success', "Collector {$collector->name} created.");
    }

    public function update(Request $request, User $collector): RedirectResponse
    {
        abort_unless($collector->isCollector(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($collector->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($collector->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $collector->update($updateData);

        if (! $collector->is_active) {
            $collector->tokens()->delete();
        }

        $this->auditService->log(
            actorUserId: $request->user()->id,
            action: 'collector_updated',
            entityType: 'user',
            entityId: $collector->id,
            metadata: ['is_active' => $collector->is_active],
            ipAddress: $request->ip()
        );

        return redirect()->route('admin.collectors.index')
            ->with('success', "Collector {$collector->name} updated.");
    }
}
