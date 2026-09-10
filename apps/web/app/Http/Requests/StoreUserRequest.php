<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\ApplicationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'use_default_password' => ['nullable', 'boolean'],
            'password' => [
                Rule::excludeIf($this->boolean('use_default_password')),
                'required',
                Password::min(app(ApplicationSettings::class)->integer('password_min_length', 6))->letters()->numbers(),
            ],
            'is_super_admin' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'password.required' => 'Kata sandi awal wajib diisi atau pilih password awal aplikasi.',
            'password.min' => 'Kata sandi minimal :min karakter.',
            'password.letters' => 'Kata sandi wajib memiliki setidaknya satu huruf.',
            'password.numbers' => 'Kata sandi wajib memiliki setidaknya satu angka.',
        ];
    }
}
