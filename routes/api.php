<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Common\NotificationController;
use App\Http\Controllers\Api\V1\Common\ProfileController;
use App\Http\Controllers\Api\V1\Client\ClientVehicleController;
use App\Http\Controllers\Api\V1\Client\ClientAssistanceRequestController;
use App\Http\Controllers\Api\V1\Common\ServiceController;
use App\Http\Controllers\Api\V1\Common\ServiceAreaController;
use App\Http\Controllers\Api\V1\Client\ClientAddressController;
use App\Http\Controllers\Api\V1\Client\ClientPaymentMethodController;
use App\Http\Controllers\Api\V1\Client\ClientPaymentController;
use App\Http\Controllers\Api\V1\Client\ClientServiceRequestController;
use App\Http\Controllers\Api\V1\Provider\ProviderProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\VehicleType; // 👈 Agrega esta línea



Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | RUTAS PÚBLICAS
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

         Route::get('/google', [SocialAuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
    });

    Route::get('/services', [ServiceController::class, 'index']);
    //Route::get('/service-areas', [ServiceAreaController::class, 'index']);

    // 👇 AGREGAR ESTA RUTA PARA TIPOS DE VEHÍCULOS
    Route::get('/vehicle-types', function() {
        $types = VehicleType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        
        return response()->json([
            'message' => 'Tipos de vehículos obtenidos correctamente.',
            'data' => $types,
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS AUTENTICADAS
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | AUTH / PERFIL
        |--------------------------------------------------------------------------
        */
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        });

        Route::prefix('me')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::patch('/', [ProfileController::class, 'update']);
        });

        /*
        |--------------------------------------------------------------------------
        | NOTIFICACIONES
        |--------------------------------------------------------------------------
        */
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/{id}', [NotificationController::class, 'show']);
            Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        });

        /*
        |--------------------------------------------------------------------------
        | CLIENTE
        |--------------------------------------------------------------------------
        */
                Route::prefix('client')->group(function () {

            // Vehículos
            Route::get('/vehicles', [ClientVehicleController::class, 'index']);
            Route::post('/vehicles', [ClientVehicleController::class, 'store']);
            Route::get('/vehicles/{id}', [ClientVehicleController::class, 'show']);
            Route::put('/vehicles/{id}', [ClientVehicleController::class, 'update']);
            Route::patch('/vehicles/{id}', [ClientVehicleController::class, 'update']);
            Route::delete('/vehicles/{id}', [ClientVehicleController::class, 'destroy']);

            // Direcciones
            Route::get('/addresses', [ClientAddressController::class, 'index']);
            Route::post('/addresses', [ClientAddressController::class, 'store']);
            Route::get('/addresses/{id}', [ClientAddressController::class, 'show']);
            Route::put('/addresses/{id}', [ClientAddressController::class, 'update']);
            Route::patch('/addresses/{id}', [ClientAddressController::class, 'update']);
            Route::delete('/addresses/{id}', [ClientAddressController::class, 'destroy']);

            // Métodos de pago
            Route::get('/payment-methods', [ClientPaymentMethodController::class, 'index']);
            Route::post('/payment-methods', [ClientPaymentMethodController::class, 'store']);
            Route::get('/payment-methods/{id}', [ClientPaymentMethodController::class, 'show']);
            Route::put('/payment-methods/{id}', [ClientPaymentMethodController::class, 'update']);
            Route::patch('/payment-methods/{id}', [ClientPaymentMethodController::class, 'update']);
            Route::delete('/payment-methods/{id}', [ClientPaymentMethodController::class, 'destroy']);

            // Solicitudes de asistencia
            Route::get('/assistance-requests', [ClientAssistanceRequestController::class, 'index']);
            Route::post('/assistance-requests', [ClientAssistanceRequestController::class, 'store']);
            Route::get('/assistance-requests/{id}', [ClientAssistanceRequestController::class, 'show']);
            Route::patch('/assistance-requests/{id}/cancel', [ClientAssistanceRequestController::class, 'cancel']);
            Route::get('/assistance-requests/{id}/status', [ClientAssistanceRequestController::class, 'status']);
            Route::get('/assistance-requests/{id}/timeline', [ClientAssistanceRequestController::class, 'timeline']);

                        // Pagos del cliente
            Route::get('/payments', [ClientPaymentController::class, 'index']);
            Route::post('/payments', [ClientPaymentController::class, 'store']);
            Route::get('/payments/{id}', [ClientPaymentController::class, 'show']);
            Route::get('/payments/{id}/receipt', [ClientPaymentController::class, 'receipt']);

                        // Solicitudes de servicio
            Route::get('/service-requests', [ClientServiceRequestController::class, 'index']);
            Route::post('/service-requests', [ClientServiceRequestController::class, 'store']);
            Route::get('/service-requests/{id}', [ClientServiceRequestController::class, 'show']);
            Route::patch('/service-requests/{id}/quote', [ClientServiceRequestController::class, 'quote']);
            Route::patch('/service-requests/{id}/confirm', [ClientServiceRequestController::class, 'confirm']);
        });

        Route::prefix('provider')->group(function () {

                    // Perfil / gestión básica del proveedor
            Route::get('/profiles', [ProviderProfileController::class, 'index']);
            Route::post('/profile', [ProviderProfileController::class, 'store']);
            Route::get('/profiles/{id}', [ProviderProfileController::class, 'show']);
            Route::put('/profiles/{id}', [ProviderProfileController::class, 'update']);
            Route::patch('/profiles/{id}', [ProviderProfileController::class, 'update']);
            Route::delete('/profiles/{id}', [ProviderProfileController::class, 'destroy']);

            // Acciones auxiliares del proveedor
            Route::patch('/profiles/{id}/services', [ProviderProfileController::class, 'updateServices']);
            Route::patch('/profiles/{id}/schedule', [ProviderProfileController::class, 'updateSchedule']);

        });
    });
});