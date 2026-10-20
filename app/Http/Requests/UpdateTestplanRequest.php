<?php

namespace App\Http\Requests;

use App\Models\Testplan;
use App\Rules\StorageFileUrl;

class UpdateTestplanRequest extends ApiFormRequest
{
    public ?Testplan $testplan = null;

    /** 404 / 403 are answered before validation so the legacy status order is preserved. */
    public function authorize(): bool
    {
        $this->testplan = Testplan::find($this->route('id'));
        if (! $this->testplan) {
            $this->deny('Testplan not found', 404);
        }

        if (! $this->user()->can('manage', $this->testplan)) {
            $this->deny('Permission denied', 403);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65000'],
            'caseInformation' => ['required', 'string', 'max:65000'],
            // UI: #edit-mainstatus offers Open / Completed
            'mainstatus' => ['required', 'string', 'in:Open,Completed'],
            'versionType' => ['required', 'string', 'in:'.implode(',', StoreTestplanRequest::VERSION_TYPES)],
            'categoryType' => ['required', 'integer', 'exists:categories,id'],
            'versionNumber' => ['nullable', 'string', 'max:100'],
            'typeOfStatus' => ['nullable', 'string', 'in:'.implode(',', StoreTestplanRequest::STATUS_TYPES)],
            'priority' => ['nullable', 'boolean'],
            'existingFiles' => ['nullable', 'array', 'max:50'],
            'existingFiles.*' => ['string', new StorageFileUrl],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => "Field 'title' cannot be empty",
            'description.required' => "Field 'description' cannot be empty",
            'caseInformation.required' => "Field 'caseInformation' cannot be empty",
            'mainstatus.required' => "Field 'mainstatus' cannot be empty",
            'versionType.required' => "Field 'versionType' cannot be empty",
            'categoryType.required' => "Field 'categoryType' cannot be empty",
        ];
    }
}
