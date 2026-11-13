<?php

namespace App\Http\Requests;

use App\Models\UpgradeProfileModel;
use App\Profiles\ProfileCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpgradeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $current = $this->route('profile');
        $currentName = $current instanceof UpgradeProfileModel ? $current->name : null;
        $reservedNames = array_values(array_diff(
            (new ProfileCatalog())->names(),
            $currentName === null ? [] : [$currentName]
        ));

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9][a-z0-9._-]*$/',
                Rule::notIn($reservedNames),
                Rule::unique('upgrade_profiles', 'name')->ignore($currentName, 'name'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'configuration' => ['required', 'array'],
            'configuration.engine' => ['required', 'string', 'in:auto,hybrid,rector,regex'],
            'configuration.scan_dirs' => ['required', 'array', 'min:1'],
            'configuration.scan_dirs.*' => ['required', 'string'],
            'configuration.rector' => ['required', 'array'],
            'configuration.rector.mode' => ['required', 'string', 'in:auto,bundled,project,docker'],
            'configuration.rector.config_path' => ['nullable', 'string'],
            'configuration.rector.exclude' => ['array'],
            'configuration.rector.exclude.*' => ['string'],
            'configuration.rector.timeout_seconds' => ['required', 'integer', 'min:1'],
            'configuration.exakat' => ['required', 'array'],
            'configuration.exakat.mode' => ['required', 'string', 'in:auto,off,configured'],
            'configuration.exakat.binary_path' => ['nullable', 'string'],
            'configuration.exakat.config_path' => ['nullable', 'string'],
            'configuration.exakat.analyzers' => ['array'],
            'configuration.exakat.analyzers.*' => ['string'],
            'configuration.exakat.timeout_seconds' => ['required', 'integer', 'min:1'],
            'configuration.ai' => ['required', 'array'],
            'configuration.ai.mode' => ['required', 'string', 'in:auto,ollama,off'],
            'configuration.ai.provider' => ['required', 'string', 'in:ollama'],
            'configuration.ai.base_url' => ['required', 'url'],
            'configuration.ai.model' => ['required', 'string', 'max:255'],
            'configuration.ai.max_files_per_hop' => ['required', 'integer', 'min:0'],
        ];
    }
}
