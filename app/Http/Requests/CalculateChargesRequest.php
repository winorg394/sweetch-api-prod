<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateChargesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => 'required|string|min:0',
            'operator' => 'required|string',
            'withdrawal_fee' => 'nullable|boolean'
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'withdrawal_fee' => $this->withdrawal_fee ?? false
        ]);
    }
}
