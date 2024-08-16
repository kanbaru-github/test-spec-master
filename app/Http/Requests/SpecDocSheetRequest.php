<?php

namespace App\Http\Requests;

use App\Domain\ExecutionEnvironment\ExecutionEnvironmentRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;

class SpecDocSheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exec_env_id' => 'required|integer|exists:' . ExecutionEnvironmentRepositoryInterface::TABLE_NAME . ',id',
        ];
    }
}
