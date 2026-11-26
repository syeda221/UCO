<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $identifier = trim($validated['identifier']);

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Collector account is deactivated. Please contact admin.',
            ], 403);
        }

        if (! $user->isCollector()) {
            return response()->json([
                'message' => 'Only collector accounts can sign in to the field app.',
            ], 403);
        }

        $token = $user->createToken($validated['device_name'] ?? 'flutter-collector-app')->plainTextToken;

        $this->auditService->log(
            actorUserId: $user->id,
            action: 'collector_login',
            entityType: 'user',
            entityId: $user->id,
            metadata: ['device_name' => $validated['device_name'] ?? 'flutter-collector-app'],
            ipAddress: $request->ip()
        );

        return response()->json([
            'token' => $token,
            'collector' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'collector' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'last_synced_at' => $user->last_synced_at?->toIso8601String(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
