<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    private const TTL_MINUTES = 10;
    private const COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    private static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key'));
    }

    // Identical response whether the address is known or not.
    // Throttle is DB-counted (single SELECTs): the database cache store
    // cannot run multi-statement writes on this pooled connection.
    public function request(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        $email = strtolower($data['email']);

        $recent = DB::table('otp_requests')
            ->where('email', $email)
            ->where('created_at', '>', now()->subMinute())
            ->count();

        if ($recent >= 5) {
            return response()->json(['message' => 'Too many requests. Wait a minute and try again.'], 429);
        }

        DB::table('otp_requests')->insert([
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recent = DB::table('email_otps')
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('created_at', '>', now()->subSeconds(self::COOLDOWN_SECONDS))
            ->exists();

        if (! $recent) {
            $code = (string) random_int(100000, 999999);
            DB::table('email_otps')->insert([
                'email' => $email,
                'code_hash' => self::hash($code),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Mail::to($email)->send(new OtpCodeMail($code));
        }

        return response()->json(['message' => 'If this address can receive mail, a code is on its way.']);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|string|size:6',
            'remember' => 'sometimes|boolean',
        ]);
        $email = strtolower($data['email']);

        $recent = DB::table('otp_requests')
            ->where('email', $email)
            ->where('created_at', '>', now()->subMinute())
            ->count();

        if ($recent >= 10) {
            return response()->json(['message' => 'Too many requests. Wait a minute and try again.'], 429);
        }

        DB::table('otp_requests')->insert([
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('email_otps')
            ->where('email', $email)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        $valid = $row
            && $row->attempts < self::MAX_ATTEMPTS
            && now()->lt($row->expires_at)
            && hash_equals($row->code_hash, self::hash($data['code']));

        if (! $valid) {
            if ($row) {
                DB::table('email_otps')->where('id', $row->id)->increment('attempts');
            }

            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        DB::table('email_otps')->where('id', $row->id)->update(['used_at' => now()]);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => strstr($email, '@', true)],
        );

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json($user->fresh()->makeHidden('password'));
    }
}
