<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->shop()->create([
                'shop_name' => $validated['shop_name'],
                'address' => $validated['address'],
                'zipcode' => $validated['zipcode'],
                'city' => $validated['city'],
                'country' => $validated['country'],
            ]);

            return $user;
        });

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('shop')),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->where('name', 'web')->delete();
        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('shop')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function user(Request $request): UserResource
    {
        return new UserResource($request->user()->load('shop'));
    }

    public function update(UpdateUserRequest $request): UserResource
    {
        $validated = $request->validated();
        $user = $request->user();

        $new_user_data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $new_user_data['password'] = Hash::make($validated['password']);
        }

        $user->update($new_user_data);

        $user->shop()->updateOrCreate([], [
            'shop_name' => $validated['shop_name'],
            'address' => $validated['address'],
            'zipcode' => $validated['zipcode'],
            'city' => $validated['city'],
            'country' => $validated['country'],
        ]);

        return new UserResource($request->user()->refresh()->load('shop'));
    }
}
