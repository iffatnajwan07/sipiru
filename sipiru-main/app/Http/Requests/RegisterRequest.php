<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'nik' => preg_replace('/\D/', '', (string) $this->input('nik')),
            'phone' => preg_replace('/[\s\-()]/', '', (string) $this->input('phone')),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/'],
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'ktp' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(2 * 1024), 'mimetypes:application/pdf,image/jpeg,image/png'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'policy' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama', 'phone' => 'nomor HP', 'nik' => 'NIK', 'ktp' => 'file KTP', 'policy' => 'persetujuan kebijakan',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP tidak valid. Gunakan format 08xxxxxxxxxx atau +628xxxxxxxxxx.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ];
    }
}
