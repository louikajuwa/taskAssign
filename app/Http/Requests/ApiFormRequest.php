<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base request that answers validation failures in the app's JSON shape:
 * { result: 'error', message: <first error>, errors: {...} } with HTTP 422.
 */
abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'result' => 'error',
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }

    protected function deny(string $message, int $status): never
    {
        throw new HttpResponseException(response()->json(['result' => 'error', 'message' => $message], $status));
    }
}
