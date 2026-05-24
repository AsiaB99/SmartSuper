<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:120',
            'email' => 'required|email:rfc,dns|max:255',
            'asunto' => 'nullable|string|max:150',
            'mensaje' => 'required|string|min:10|max:2000',
            'empresa' => 'nullable|size:0',
        ];
    }
}
