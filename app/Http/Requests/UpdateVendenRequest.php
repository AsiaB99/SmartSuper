<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'precio' => ['required', 'numeric', 'min:0'],
            'precio_unidad' => ['nullable', 'numeric', 'min:0'],
            'unidad_ref' => ['nullable', 'string', 'max:20'],
        ];
    }
}
