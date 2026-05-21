<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nombre_producto' => 'required|string|max:255|unique:productos',
            'id_seccion' => 'required|exists:secciones,id',
            'marca' => 'nullable|string|max:50',
            'formato' => 'nullable|string|max:50',
            'imagen' => 'nullable|string|max:255',
        ];
    }
}
