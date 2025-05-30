<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProfileController extends Controller
{
    public function show()
    {
        try {
            $profile = Profile::where('user_id', auth()->user()->id)->first();

            if (!$profile) {
                return $this->reply(false, 'Profile not found', null, 404);
            }

            return $this->reply(true, 'Profile retrieved successfully', $profile);

        } catch (Exception $e) {
            return $this->reply(false, 'Failed to retrieve profile', null, 500);
        }
    }

    public function update(ProfileRequest $request)
    {
        try {
            $validatedData = $request->validated();

            if (isset($validatedData['id_type']) &&
                !in_array($validatedData['id_type'], ['passport', 'cni'])) {
                return $this->reply(
                    false,
                    'Invalid ID type. Must be either passport or cni',
                    null,
                    422
                );
            }

            $profile = Profile::updateOrCreate(
                ['user_id' => Auth::id()],
                $validatedData
            );

            return $this->reply(true, 'Profile updated successfully', $profile);

        } catch (Exception $e) {
            return $this->reply(false, 'Failed to update profile', null, 500);
        }
    }
}

