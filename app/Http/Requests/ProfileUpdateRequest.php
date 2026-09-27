<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^08[0-9]{8,13}$/',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input('phone')) ?? '';
        $this->merge(['phone' => str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits]);
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor WhatsApp harus diawali 08 dan berisi 10 sampai 15 digit.',
            'phone.unique' => 'Nomor WhatsApp sudah digunakan akun lain.',
        ];
    }
}
