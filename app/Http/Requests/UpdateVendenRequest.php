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
            'moneda' => ['nullable', 'string', 'size:3'],
            'fuente_precio' => ['nullable', 'string', 'max:50'],
            'url_origen' => ['nullable', 'url', 'max:2048'],
            'fecha_precio' => ['nullable', 'date'],
        ];
    }
}
