<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Profiles\ProfileCatalog;
use Pupgrader\UpgradeOptions;

final class QueueUpgradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required', 'string'],
            'project_path' => ['required', 'string'],
            'from' => ['required', 'string'],
            'to' => ['required', 'string'],
            'dry_run' => ['sometimes', 'boolean'],
            'profile' => ['sometimes', 'nullable', 'string', Rule::in((new ProfileCatalog())->names())],
            'options' => ['sometimes', 'array'],
            'options.engine' => ['sometimes', 'string', 'in:auto,hybrid,rector,regex'],
            'options.rector_mode' => ['sometimes', 'string', 'in:auto,bundled,project,docker'],
            'options.ai' => ['sometimes', 'string', 'in:auto,ollama,off'],
            'options.exakat' => ['sometimes', 'string', 'in:auto,off'],
            'options.ollama_url' => ['sometimes', 'string', 'url'],
            'options.ollama_model' => ['sometimes', 'string'],
            'options.ai_max_files_per_hop' => ['sometimes', 'integer', 'min:0'],
            'options.scan_dirs' => ['sometimes', 'array'],
            'options.scan_dirs.*' => ['string'],
            'options.output_path' => ['sometimes', 'nullable', 'string'],
            'options.force_overwrite' => ['sometimes', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'validation_error',
                    message: 'Invalid upgrade request',
                    details: $validator->errors()->toArray()
                )
            ), 422)
        );
    }

    public function jobId(): string
    {
        return $this->string('job_id')->toString();
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $profileName = $this->input('profile');
        $options = (array) $this->input('options', []);
        $profileSnapshot = null;

        if (is_string($profileName) && $profileName !== '') {
            $profile = (new ProfileCatalog())->find($profileName);
            $options = array_merge(
                UpgradeOptions::fromConfiguration($profile->configuration)->toArray(),
                $options
            );
            $profileSnapshot = $profile->configuration->toArray();
        }

        return [
            'project_path' => $this->string('project_path')->toString(),
            'from' => $this->string('from')->toString(),
            'to' => $this->string('to')->toString(),
            'dry_run' => $this->boolean('dry_run'),
            'profile' => $profileName,
            'profile_configuration' => $profileSnapshot,
            'options' => $options,
        ];
    }
}
