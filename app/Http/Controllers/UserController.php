<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function findByPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string'
        ]);

        $user = User::with('profile')->where('phone', $validated['phone'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User found successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->profile->name ?? 'Unknown',
                'phone' => $user->phone,
            ]
        ]);
    }
}
