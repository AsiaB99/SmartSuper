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
            'fecha_creacion' => ['nullable', 'date'],
        ];
    }
}