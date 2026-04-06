<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\RoleType;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['nullable', 'string', 'in:client,provider'],
        ]);

        $roleCode = $data['role'] ?? 'client';

        $role = RoleType::query()
            ->where('code', $roleCode)
            ->where('is_active', true)
            ->first();

        if (!$role) {
            return response()->json([
                'message' => 'El rol solicitado no existe o no está activo.',
                'errors' => [
                    'role' => ['El rol solicitado no existe o no está activo.'],
                ],
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->roles()->syncWithoutDetaching([$role->id]);

            $token = $user->createToken('auth_token')->plainTextToken;

            UserSession::create([
                'user_id' => $user->id,
                'session_token' => hash('sha256', $token),
                'last_activity' => now(),
            ]);

            $user->load('roles');

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'data' => [
                'user' => $result['user'],
                'roles' => $result['user']->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                ])->values(),
                'token' => $result['token'],
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('roles')
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        UserSession::create([
            'user_id' => $user->id,
            'session_token' => hash('sha256', $token),
            'last_activity' => now(),
        ]);

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'user' => $user,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                ])->values(),
                'token' => $token,
            ],
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'provider']);

        UserSession::where('user_id', $user->id)
            ->latest('id')
            ->first()?->update([
                'last_activity' => now(),
            ]);

        return response()->json([
            'data' => [
                'user' => $user,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                ])->values(),
                'provider_profile' => $user->provider,
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }

        UserSession::where('user_id', $user->id)
            ->latest('id')
            ->first()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();

        UserSession::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Todas las sesiones fueron cerradas correctamente.',
        ], 200);
    }
}


