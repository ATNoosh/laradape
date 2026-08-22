<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class RunPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required', 'string'],
            'payload' => ['sometimes', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'validation_error',
                    message: 'Invalid request data',
                    details: $validator->errors()->toArray()
                )
            ), 422)
        );
    }

    public function jobId(): string
    {
        return $this->input('job_id');
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return (array) $this->input('payload', []);
    }
}
