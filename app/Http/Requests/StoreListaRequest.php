<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreListaRequest extends FormRequest
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
        ];
    }
}
