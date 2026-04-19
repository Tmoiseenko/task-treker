<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
            'task_id' => ['required', 'exists:tasks,id'],
            'moonshine_user_id' => ['required', 'exists:moonshine_users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Содержание комментария обязательно для заполнения',
            'content.max' => 'Комментарий не должен превышать 10000 символов',
        ];
    }
}
