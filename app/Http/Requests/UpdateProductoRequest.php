<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nombre_producto' => 'required|string|max:255|unique:productos,nombre_producto,' . $this->producto->id,
            'id_seccion' => 'required|exists:secciones,id',
        ];
    }
}
