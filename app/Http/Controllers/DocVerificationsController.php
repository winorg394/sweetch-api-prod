<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class DocVerificationsController extends Controller
{
    public function uploadIdCard(Request $request)
    {
        try {
            $request->validate([
                'id_number' => 'required|string',
                'id_image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $user = Auth::user();
            $path = null;

            if ($request->hasFile('id_image')) {
                $path = $request->file('id_image')->store('id-cards', 'public');
                $user->id_card_path = $path;
            }

            $user->id_number = $request->id_number;
            $user->save();

            return $this->reply(true, 'ID Card uploaded successfully', [
                'file_path' => $path
            ]);

        } catch (Exception $e) {
            return $this->reply(false, 'Failed to upload ID Card', null, 500);
        }
    }

    public function uploadNiu(Request $request)
    {
        try {
            $request->validate([
                'niu' => 'required|string',
                'niu_document' => 'required|mimes:pdf,jpeg,png,jpg|max:2048'
            ]);

            $user = Auth::user();
            $path = null;

            if ($request->hasFile('niu_document')) {
                $path = $request->file('niu_document')->store('niu-documents', 'public');
                $user->niu_document_path = $path;
            }

            $user->niu = $request->niu;
            $user->save();

            return $this->reply(true, 'NIU uploaded successfully', [
                'file_path' => $path
            ]);

        } catch (Exception $e) {
            return $this->reply(false, 'Failed to upload NIU', null, 500);
        }
    }

    public function verifyIdCard(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->id_number || !$user->id_card_path) {
                return $this->reply(false, 'ID Card not found', null, 404);
            }

            if ($user->id_number === $request->id_number) {
                return $this->reply(true, 'ID Card verified successfully', [
                    'file_path' => $user->id_card_path
                ]);
            }

            return $this->reply(false, 'ID Card verification failed', null, 422);

        } catch (Exception $e) {
            return $this->reply(false, 'Verification process failed', null, 500);
        }
    }

    public function verifyNiu(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->niu || !$user->niu_document_path) {
                return $this->reply(false, 'NIU document not found', null, 404);
            }

            if ($user->niu === $request->niu) {
                return $this->reply(true, 'NIU verified successfully', [
                    'file_path' => $user->niu_document_path
                ]);
            }

            return $this->reply(false, 'NIU verification failed', null, 422);

        } catch (Exception $e) {
            return $this->reply(false, 'Verification process failed', null, 500);
        }
    }
}
