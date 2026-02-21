<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function jobId(): string
    {
        return $this->input('job_id');
    }
}
