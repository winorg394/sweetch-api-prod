<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name'       => 'required|string|max:255',
            'second_name'      => 'required|string|max:255',
            'whatsapp'         => 'nullable|string',
            'id_type'          => 'nullable|string',
            'niu'              => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            'comment'          => 'nullable|string',
        ];
    }
}
