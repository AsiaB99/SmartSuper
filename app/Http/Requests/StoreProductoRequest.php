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
            'codigo_barras' => 'nullable|string|max:32|unique:productos,codigo_barras',
            'marca' => 'nullable|string|max:50',
            'formato' => 'nullable|string|max:50',
            'cantidad_envase' => 'nullable|numeric|min:0',
            'unidad_envase' => 'nullable|string|max:20',
            'imagen' => 'nullable|string|max:255',
            'fuente_datos' => 'nullable|string|max:50',
        ];
    }
}
