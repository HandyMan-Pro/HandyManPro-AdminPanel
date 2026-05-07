<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->id ?? null;

        return [
            'name' => [
                'required',
                Rule::unique('documents', 'name')
                    ->where(fn($query) => $query->where('type', $this->type))
                    ->ignore($id),
            ],
            'type'   => 'required',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'This document name already exists for the selected type.',
        ];
    }
}
