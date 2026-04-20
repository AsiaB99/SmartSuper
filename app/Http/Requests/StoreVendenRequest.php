<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'id_producto' => [
                'required',
                'integer',
                'exists:productos,id',
                Rule::unique('venden')->where(
                    fn ($query) => $query
                        ->where('id_producto', $this->integer('id_producto'))
                        ->where('id_super', $this->integer('id_super'))
                ),
            ],
            'id_super' => ['required', 'integer', 'exists:supermercados,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'precio_unidad' => ['nullable', 'numeric', 'min:0'],
            'unidad_ref' => ['nullable', 'string', 'max:20'],
        ];
    }
}
