<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Verification;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'email'    => $request->email,
                'phone'    => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Create user profile
            $profile = Profile::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'second_name' => $request->last_name,
            ]);

            // Generate OTP for email and phone verification
            /*       $emailOtp = $this->generateOtp($user->id, 'email');
            $phoneOtp = $this->generateOtp($user->id, 'phone');
            */
            DB::commit();
            return (new LoginController())->login($request);

        } catch (Exception $e) {
            DB::rollBack();
            info($e);
            return $this->reply(
                false,
                'Registration failed. Please try again.',
                null,
                500
            );
        }
    }

    private function generateOtp($userId, $type)
    {
        try {
            $otp = Verification::create([
                'user_id'    => $userId,
                'type'       => $type,
                'otp_code'   => rand(100000, 999999),
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            return $otp;
        } catch (Exception $e) {
            throw new Exception('Failed to generate OTP: ' . $e->getMessage());
        }
    }
}
