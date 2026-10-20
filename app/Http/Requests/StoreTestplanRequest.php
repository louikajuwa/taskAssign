<?php

namespace App\Http\Requests;

use App\Rules\StorageFileUrl;

class StoreTestplanRequest extends ApiFormRequest
{
    /** Values offered by the UI selects (testplan/index.blade.php #versionType, #typeOfStatus). */
    public const VERSION_TYPES = ['sandbox', 'alpha', 'beta', 'live'];

    public const STATUS_TYPES = ['New', 'Bug'];

    public function authorize(): bool
    {
        if (! $this->user()?->canCreate()) {
            $this->deny('User is not allowed to create', 403);
        }

        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65000'],
            'categoryType' => ['required', 'integer', 'exists:categories,id'],
            'caseInformation' => ['required', 'string', 'max:65000'],
            'versionType' => ['required', 'string', 'in:'.implode(',', self::VERSION_TYPES)],
            'versionNumber' => ['nullable', 'string', 'max:100'],
            'typeOfStatus' => ['nullable', 'string', 'in:'.implode(',', self::STATUS_TYPES)],
            'priority' => ['nullable', 'boolean'],
            'itemDataFile' => ['nullable', 'array', 'max:50'],
            'itemDataFile.*' => ['string', new StorageFileUrl],
        ];

        foreach (['asignToDeVId', 'generecTesterId', 'sandboxTesterId', 'alphaTesterId', 'betaTesterId', 'liveTesterId'] as $field) {
            $rules[$field] = ['nullable', 'array', 'max:200'];
            $rules[$field.'.*'] = ['nullable', 'integer'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Missing or empty parameter: title',
            'description.required' => 'Missing or empty parameter: description',
            'categoryType.required' => 'Missing or empty parameter: categoryType',
            'caseInformation.required' => 'Missing or empty parameter: caseInformation',
            'versionType.required' => 'Missing or empty parameter: versionType',
        ];
    }
}
