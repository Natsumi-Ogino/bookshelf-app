<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class TokenController extends Controller
{
    public function store(IssueTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => 'メールアドレスまたはパスワードが正しくありません。',
            ], 401);
        }

        $expiresAt = now()->addDays(30);
        $token = $user->createToken(
            $validated['device_name'],
            ['*'],
            $expiresAt
        );

        return response()->json([
            'plain_text_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
