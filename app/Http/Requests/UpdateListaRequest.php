<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_lista' => ['required', 'string', 'max:50'],
            'estado' => ['required', 'in:activa,comprada'],
            'fecha_creacion' => ['nullable', 'date'],
            'usuarios_editores' => ['nullable', 'array'],
            'usuarios_editores.*' => ['bail', 'string', 'max:50', 'alpha_dash', 'distinct'],
        ];
    }
}
