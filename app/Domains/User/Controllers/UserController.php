<?php

namespace App\Domains\User\Controllers;

use App\Domains\User\Requests\DeleteAccountRequest;
use App\Domains\User\Requests\UpdatePasswordRequest;
use App\Domains\User\Requests\UpdateProfileRequest;
use App\Domains\User\Requests\UpdateSettingsRequest;
use App\Domains\User\Resources\PublicProfileResource;
use App\Domains\User\Resources\UserResource;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController
{
    public function show(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        return response()->json(new PublicProfileResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->only('name', 'username', 'bio'));

        return response()->json(new UserResource($user));
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);

        $user = $request->user();

        if ($user->avatar_url) {
            $oldPath = str_replace(Storage::disk('public')->url(''), '', $user->avatar_url);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_url' => Storage::disk('public')->url($path)]);

        return response()->json(['avatar_url' => $user->avatar_url]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['The current password is incorrect.']]);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()?->id)->delete();

        return response()->json(null, 204);
    }

    public function sessions(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken()?->id;

        $sessions = $request->user()->tokens()->get()->map(fn ($token) => [
            'id' => $token->id,
            'name' => $token->name,
            'last_used_at' => $token->last_used_at,
            'created_at' => $token->created_at,
            'current' => $token->id === $current,
        ]);

        return response()->json($sessions);
    }

    public function revokeSession(Request $request, string $id): JsonResponse
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json(null, 204);
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => ['The password is incorrect.']]);
        }

        if ($user->ownedWorkspaces()->exists()) {
            return response()->json([
                'message' => 'Transfer or delete the workspaces you own before deleting your account.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }

    public function settings(Request $request): JsonResponse
    {
        $settings = $request->user()->settings ?? UserSetting::create(['user_id' => $request->user()->id]);

        return response()->json($settings);
    }

    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = $request->user()->settings ?? UserSetting::create(['user_id' => $request->user()->id]);
        $settings->update($request->only('theme', 'language', 'notification_prefs'));

        return response()->json($settings);
    }
}
