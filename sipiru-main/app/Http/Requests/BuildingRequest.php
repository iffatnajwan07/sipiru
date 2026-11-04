<?php

namespace App\Http\Requests;

use App\Models\Building;
use App\Support\NameNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class BuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $building = $this->route('building');

        return $building ? $this->user()->can('update', $building) : $this->user()->can('create', Building::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => NameNormalizer::clean($this->input('name')),
            'code' => mb_strtoupper(NameNormalizer::clean($this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $ignoreId = $this->route('building')?->id;

        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-_.]*$/', $this->uniqueNormalized('code_normalized', 'Kode gedung sudah digunakan.', $ignoreId)],
            'name' => ['required', 'string', 'min:3', 'max:150', $this->uniqueNormalized('name_normalized', 'Gedung dengan nama tersebut sudah terdaftar.', $ignoreId)],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'kode gedung', 'name' => 'nama gedung', 'address' => 'alamat', 'description' => 'deskripsi'];
    }

    public function messages(): array
    {
        return ['code.regex' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, titik, dan garis bawah.'];
    }

    /** Validasi cepat berbasis nilai ternormalisasi; database tetap pengaman terakhir. */
    private function uniqueNormalized(string $column, string $message, ?int $ignoreId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($column, $message, $ignoreId) {
            $exists = Building::where($column, NameNormalizer::normalize($value))
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists();
            if ($exists) {
                $fail($message);
            }
        };
    }
}
