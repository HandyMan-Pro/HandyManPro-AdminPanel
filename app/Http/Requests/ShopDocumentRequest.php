<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\ShopDocument;

class ShopDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id'        => 'required|exists:shops,id',
            'document_id'    => 'required|exists:documents,id',
           'shop_document' => 'nullable|file|mimetypes:application/pdf,application/x-pdf,application/acrobat,applications/vnd.pdf,text/pdf,text/x-pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,application/octet-stream|max:10240',
        ];
    }

    /**
     * Custom error response for API requests
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->is('api/*')) {
            throw new HttpResponseException(response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
