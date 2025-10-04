<?php

namespace App\Http\Requests\Alimentazione;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Unita;

class StoreConsumoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'alimento_id'      => ['required','exists:alimenti,id'],
            'quantita'         => ['required','numeric','min:0.01','max:999999.99'],
            'unita'            => ['required', new Enum(Unita::class)],
            'scarica_dispensa' => ['sometimes','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'alimento_id.required' => 'Seleziona un alimento.',
            'alimento_id.exists'   => 'Alimento non valido.',
            'quantita.*'           => 'Quantità non valida.',
            'unita.*'              => 'Unità non valida.',
        ];
    }
}

