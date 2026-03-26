<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\RoleType;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirigir a Google para autenticación
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Callback de Google
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Buscar usuario por email o google_id
            $user = User::where('email', $googleUser->getEmail())
                ->orWhere('google_id', $googleUser->getId())
                ->first();
            
            if (!$user) {
                // Crear nuevo usuario
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);
                
                // Asignar rol de cliente por defecto
                $role = RoleType::where('code', 'client')
                    ->where('is_active', true)
                    ->first();
                    
                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }
            } else {
                // Actualizar google_id si no lo tiene
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            }
            
            // Generar token de acceso
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Crear sesión
            UserSession::create([
                'user_id' => $user->id,
                'session_token' => hash('sha256', $token),
                'last_activity' => now(),
            ]);
            
            // Redirigir a la página de éxito con token
            return redirect()->to('/auth/google/success?token=' . $token . 
                '&user=' . urlencode(json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])));
            
        } catch (\Exception $e) {
            \Log::error('Google login error: ' . $e->getMessage());
            return redirect()->to('/login?error=google_auth_failed');
        }
    }
    
    /**
     * Endpoint para manejar la respuesta exitosa
     */
    public function success(Request $request)
    {
        $token = $request->query('token');
        $userData = json_decode($request->query('user'), true);
        
        if (!$token || !$userData) {
            return redirect()->to('/login?error=invalid_callback');
        }
        
        return view('auth.google-success', [
            'token' => $token,
            'user' => $userData,
        ]);
    }
}