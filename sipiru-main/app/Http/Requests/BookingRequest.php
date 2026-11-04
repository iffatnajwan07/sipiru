<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'activity_type' => ['required', 'in:usk,non_usk'], // Validasi jenis kegiatan USK / Non-USK
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'ruangan',
            'activity_type' => 'jenis kegiatan', // Label atribut untuk pesan error
            'date' => 'tanggal', 
            'start_time' => 'jam mulai', 
            'end_time' => 'jam selesai', 
            'notes' => 'catatan',
        ];
    }

    public function startAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->validated('date').' '.$this->validated('start_time'))->seconds(0);
    }

    public function endAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->validated('date').' '.$this->validated('end_time'))->seconds(0);
    }
}