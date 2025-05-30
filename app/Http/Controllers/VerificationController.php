<?php
namespace App\Http\Controllers;

use App\Models\Verification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class VerificationController extends Controller
{
    // Add new methods for sending OTP
    public function sendEmailOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $user = User::where('email', $request->email)->first();

            $otp = Verification::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'email'
                ],
                [
                    'otp_code' => rand(100000, 999999),
                    'expires_at' => Carbon::now()->addMinutes(10),
                ]
            );

            // Here you would implement your email sending logic
            try {
                Mail::to($user->email)->send(new OtpMail($otp->otp_code));
            } catch (Exception $e) {
                return $this->reply(
                    false,
                    'Failed to send OTP email',
                    null,
                    500
                );
            }

            return $this->reply(
                true,
                'OTP sent to your email successfully',
                ['email' => $user->email]
            );

        } catch (Exception $e) {
            return $this->reply(
                false,
                'Failed to send OTP',
                null,
                500
            );
        }
    }

    public function sendPhoneOtp(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|exists:users,phone'
            ]);

            $user = User::where('phone', $request->phone)->first();

            $otp = Verification::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'phone'
                ],
                [
                    'otp_code' => rand(100000, 999999),
                    'expires_at' => Carbon::now()->addMinutes(10),
                ]
            );

            // Here you would implement your SMS sending logic
            // For example, using a SMS service provider

            return $this->reply(
                true,
                'OTP sent to your phone successfully',
                ['phone' => $user->phone]
            );

        } catch (Exception $e) {
            return $this->reply(
                false,
                'Failed to send OTP',
                null,
                500
            );
        }
    }

    public function verifyEmail(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'otp_code' => 'required',
            ]);

            $verification = Verification::where('type', 'email')
                ->where('otp_code', $request->otp_code)
                ->whereHas('user', function ($query) use ($request) {
                    $query->where('email', $request->email);
                })
                ->first();

            if (!$verification) {
                return $this->reply(false, 'Invalid OTP code', null, 400);
            }

            if (Carbon::now()->gt($verification->expires_at)) {
                return $this->reply(false, 'OTP has expired', null, 400);
            }

            $verification->user->update(['email_verified_at' => now()]);
            $verification->delete();

            return $this->reply(true, 'Email verified successfully');

        } catch (Exception $e) {
            return $this->reply(false, 'Email verification failed', null, 500);
        }
    }

    public function verifyPhone(Request $request)
    {
        try {
            $request->validate([
                'phone'    => 'required',
                'otp_code' => 'required',
            ]);

            $verification = Verification::where('type', 'phone')
                ->where('otp_code', $request->otp_code)
                ->whereHas('user', function ($query) use ($request) {
                    $query->where('phone', $request->phone);
                })
                ->first();

            if (!$verification) {
                return $this->reply(false, 'Invalid OTP code', null, 400);
            }

            if (Carbon::now()->gt($verification->expires_at)) {
                return $this->reply(false, 'OTP has expired', null, 400);
            }

            $verification->user->update(['phone_verified_at' => now()]);
            $verification->delete();

            return $this->reply(true, 'Phone verified successfully');

        } catch (Exception $e) {
            return $this->reply(false, 'Phone verification failed', null, 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required',
                'otp_code' => 'required',
            ]);

            $user = User::where('phone', $request->phone)
                        ->where('otp_code', $request->otp_code)
                        ->first();

            if (!$user) {
                return $this->reply(false, 'Invalid OTP code', null, 400);
            }

            if (Carbon::now()->gt($user->otp_expires_at)) {
                return $this->reply(false, 'OTP has expired', null, 400);
            }

            $user->phone_verified_at = now();
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            return $this->reply(true, 'OTP verified successfully');

        } catch (Exception $e) {
            return $this->reply(false, 'OTP verification failed', null, 500);
        }
    }
}
