<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDespensaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_despensa' => ['required', 'string', 'max:50'],
            'usuarios_editores' => ['nullable', 'array'],
            'usuarios_editores.*' => ['bail', 'string', 'max:50', 'alpha_dash', 'distinct'],
        ];
    }
}
