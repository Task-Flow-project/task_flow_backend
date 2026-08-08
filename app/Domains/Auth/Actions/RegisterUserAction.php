<?php

namespace App\Domains\Auth\Actions;

use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => str($data['email'])->before('@')->toString() . '_' . rand(100, 999),
            'password' => Hash::make($data['password']),
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user)->send(new SendOtpMail($otpCode, $user->name));

        return $user;
    }
}
