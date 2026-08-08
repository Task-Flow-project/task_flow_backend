<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\RegisterUserAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\Auth\Requests\RegisterRequest;
use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{
    private function withTokenCookie(JsonResponse $response, string $token): JsonResponse
    {
        return $response->cookie(\Illuminate\Support\Facades\Cookie::make(
            'taskflow_token', $token, 60 * 24 * 30, '/', null, false, true, false, 'lax'
        ));
    }

    public function register(RegisterRequest $request, RegisterUserAction $registerUserAction): JsonResponse
    {
        $user = $registerUserAction->execute($request->validated());

        return response()->json([
            'message' => 'Registration successful. Please verify your email with the OTP sent to your inbox.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->email_verified_at === null) {
            return response()->json(['message' => 'Please verify your email first. Check your inbox for the OTP.'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->withTokenCookie(response()->json([
            'user' => $user,
            'token' => $token,
        ]), $token);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return response()->json(null, 204)->withoutCookie('taskflow_token');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(Auth::user());
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        if ($user->otp_code !== $request->input('otp_code')) {
            return response()->json(['message' => 'Invalid OTP code.'], 422);
        }

        if ($user->otp_expires_at === null || $user->otp_expires_at->isPast()) {
            return response()->json(['message' => 'OTP has expired. Request a new one.'], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->withTokenCookie(response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user,
            'token' => $token,
        ]), $token);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user)->send(new SendOtpMail($otpCode, $user->name));

        return response()->json(['message' => 'A new OTP has been sent to your email.']);
    }

    public function redirectToGoogle(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function handleGoogleCallback(Request $request): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed.',
                'error' => $e->getMessage(),
            ], 400);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'username' => str($googleUser->getEmail())->before('@')->toString() . '_' . rand(100, 999),
                'password' => Hash::make(str()->random(32)),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        } elseif ($user->email_verified_at === null) {
            $user->update(['email_verified_at' => now()]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->withTokenCookie(response()->json([
            'user' => $user,
            'token' => $token,
        ]), $token);
    }
}
