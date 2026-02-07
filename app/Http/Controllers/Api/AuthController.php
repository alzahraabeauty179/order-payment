<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Controller instance.
     *
     * @param AuthService $authService
     */
    public function __construct(protected AuthService $authService) {}

    /**
     * Register a new user.
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register (RegisterRequest $request): JsonResponse
    {
        try {
            $this->authService->register($request->validated());

            return response()->json([
                'message' => 'User registered successfully.',
            ])->setStatusCode(201);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Registration failed.',
                'error' => $ex->getMessage(),
            ])->setStatusCode(400);
        }
    }

    /**
     * Login a user.
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login (LoginRequest $request) : JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return response()->json([
                'message' => 'Approved!',
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Login Failed.',
                'error' => $ex->getMessage(),
            ])->setStatusCode(401);
        }
    }

}