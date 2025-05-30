<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        //return Hash::make("1234");
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->reply(
                    false,
                    'Invalid credentials',
                    null,
                    401
                );
            }
          //  $user->tokens()->delete();
            $token = $user->createToken($request->device_name, ['*'], now()->addSeconds(600000))->plainTextToken;

            return $this->reply(
                true,
                'Logged in successfully',
                [
                    'token' => $token,
                    'user' => $user,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration') * 60 // Convert minutes to seconds
                ]
            );
        } catch (ValidationException $e) {
            info($e);
            return $this->reply(
                false,
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (Exception $e) {
            info($e);
            return $this->reply(
                false,
                'Login failed. Please try again.',
                null,
                500
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            return $request->user();
            $request->user()->currentAccessToken()->delete();
            $request->user()->tokens()->delete();
            return $this->reply(
                true,
                'Logged out successfully',
                null,
                200
            );
        } catch (Exception $e) {
            info($e);
            return $this->reply(
                false,
                'Logout failed. Please try again.',
                null,
                500
            );
        }
    }

    public function checkEmailExists(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $exists = User::where('email', $request->email)->exists();

            return $this->reply(
                true,
                'Email check completed',
                [
                    'email' => $request->email,
                    'exists' => $exists
                ],
                200
            );

        } catch (ValidationException $e) {
            return $this->reply(
                false,
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (Exception $e) {
            return $this->reply(
                false,
                'Email check failed',
                null,
                500
            );
        }
    }
}
