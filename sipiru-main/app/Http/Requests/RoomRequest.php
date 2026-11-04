<?php

namespace App\Http\Requests;

use App\Models\Room;
use App\Support\NameNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $room = $this->route('room');

        return $room ? $this->user()->can('update', $room) : $this->user()->can('create', Room::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => NameNormalizer::clean($this->input('name')),
            'code' => mb_strtoupper(NameNormalizer::clean($this->input('code'))),
            'floor' => filled($this->input('floor')) ? NameNormalizer::clean($this->input('floor')) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $ignoreId = $this->route('room')?->id;

        return [
            'building_id' => ['required', 'integer', Rule::exists('buildings', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-_.]*$/', $this->uniqueInBuilding('code_normalized', 'Kode ruang sudah digunakan di gedung ini.', $ignoreId)],
            'name' => ['required', 'string', 'min:2', 'max:150', $this->uniqueInBuilding('name_normalized', null, $ignoreId)],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'price' => ['nullable', 'integer', 'min:0'], // Validasi Harga (Boleh kosong, harus angka minimal 0)
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'building_id' => 'gedung', 
            'code' => 'kode ruang', 
            'name' => 'nama ruang', 
            'floor' => 'lantai', 
            'capacity' => 'kapasitas',
            'price' => 'harga sewa' // Alias untuk pesan error
        ];
    }

    public function messages(): array
    {
        return ['code.regex' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, titik, dan garis bawah.'];
    }

    /** Unik di dalam gedung yang sama; nama yang sama di gedung lain diperbolehkan. */
    private function uniqueInBuilding(string $column, ?string $message, ?int $ignoreId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($column, $message, $ignoreId) {
            $buildingId = (int) $this->input('building_id');
            if (! $buildingId) {
                return;
            }
            $existing = Room::with('building:id,name')
                ->where('building_id', $buildingId)
                ->where($column, NameNormalizer::normalize($value))
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->first();
            if ($existing) {
                $fail($message ?? "Ruang \"{$existing->name}\" sudah ada di {$existing->building->name}.");
            }
        };
    }
}