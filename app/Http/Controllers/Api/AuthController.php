<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthenticateUser $action): JsonResponse
    {
        $result = $action->execute(
            $request->string('merchant')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if ($result === null) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user = $result['user'];
        $user->load('merchant');

        return response()->json([
            'user' => new UserResource($user),
            'merchant' => new MerchantResource($user->merchant),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()->delete();

        Log::info('Logout successful.', ['user_id' => $user->public_id]);

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('merchant');

        return response()->json([
            'user' => new UserResource($user),
            'merchant' => new MerchantResource($user->merchant),
        ]);
    }
}
