<x-layouts.app title="Ajukan Peminjaman">
    <x-page-header
        title="Ajukan Peminjaman"
        subtitle="Pilih ruangan dan waktu, cek ketersediaan, lalu ajukan."
    />

    @if (! $period)
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="triangle-alert" class="mt-0.5 size-4" />
            <span>
                Belum ada periode peminjaman yang aktif.
                Pengajuan belum dapat dibuat.
            </span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form
            method="POST"
            action="{{ route('bookings.store') }}"
            class="card lg:col-span-2"
            x-data="bookingForm({
                roomsUrl: @js(route('bookings.rooms', '__ID__')),
                availabilityUrl: @js(route('bookings.availability')),
                buildingId: @js(old('building_id', $selectedRoom?->building_id)),
                roomId: @js(old('room_id', $selectedRoom?->id)),
                date: @js(old('date', '')),
                start: @js(old('start_time', '')),
                end: @js(old('end_time', '')),
                activityType: @js(old('activity_type', 'usk'))
            })"
        >
            @csrf

            <div class="card-body grid gap-5 sm:grid-cols-2">
                @if ($selectedRoom)
                    <div class="flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-2.5 text-sm text-indigo-800 sm:col-span-2">
                        <x-icon name="scan-line" class="size-4" />
                        <span>
                            Ruangan
                            <strong>{{ $selectedRoom->name }}</strong>
                            ({{ $selectedRoom->building?->name ?? '-' }})
                            otomatis terpilih.
                        </span>
                    </div>
                @endif

                {{-- Pilihan gedung --}}
                <div>
                    <label for="building_id" class="label">
                        Gedung
                    </label>

                    <select
                        id="building_id"
                        name="building_id"
                        x-model="buildingId"
                        class="input @error('building_id') input-error @enderror"
                        required
                    >
                        <option value="">Pilih Gedung</option>

                        @foreach ($buildings as $building)
                            <option
                                value="{{ $building->id }}"
                                @selected(
                                    (string) old('building_id', $selectedRoom?->building_id)
                                    === (string) $building->id
                                )
                            >
                                {{ $building->name }}
                            </option>
                        @endforeach
                    </select>

                    <x-field-error name="building_id" />
                </div>

                {{-- Pilihan ruangan --}}
                <div>
                    <label for="room_id" class="label">
                        Ruangan
                    </label>

                    <select
                        id="room_id"
                        name="room_id"
                        x-model="roomId"
                        class="input @error('room_id') input-error @enderror"
                        required
                        :disabled="!buildingId || loadingRooms"
                    >
                        <option
                            value=""
                            x-text="loadingRooms
                                ? 'Memuat…'
                                : (buildingId ? 'Pilih Ruangan' : 'Pilih gedung dahulu')"
                        ></option>

                        <template x-for="room in rooms" :key="room.id">
                            <option
                                :value="room.id"
                                x-text="`${room.name} (${room.code})`"
                            ></option>
                        </template>
                    </select>

                    <x-field-error name="room_id" />

                    <p
                        class="help"
                        x-show="selectedRoom"
                        x-cloak
                    >
                        <span
                            x-text="selectedRoom
                                ? `Lantai ${selectedRoom.floor ?? '-'} · Kapasitas ${selectedRoom.capacity} orang`
                                : ''"
                        ></span>

                        <span
                            x-text="selectedRoom && Number(selectedRoom.price) > 0
                                ? ` · Tarif: Rp ${Number(selectedRoom.price).toLocaleString('id-ID')}`
                                : ' · Tarif: Gratis'"
                        ></span>
                    </p>
                </div>

                {{-- Jenis kegiatan --}}
                <div class="sm:col-span-2">
                    <label class="label">
                        Jenis Kegiatan
                    </label>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm font-medium transition hover:bg-slate-50">
                            <input
                                type="radio"
                                name="activity_type"
                                value="usk"
                                x-model="activityType"
                                class="text-indigo-600 focus:ring-indigo-500"
                            >

                            <div>
                                <span class="block text-slate-900">
                                    Kegiatan USK
                                </span>
                                <span class="block text-xs font-normal text-slate-500">
                                    Gratis (Tanpa Biaya Sewa)
                                </span>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm font-medium transition hover:bg-slate-50">
                            <input
                                type="radio"
                                name="activity_type"
                                value="non_usk"
                                x-model="activityType"
                                class="text-indigo-600 focus:ring-indigo-500"
                            >

                            <div>
                                <span class="block text-slate-900">
                                    Kegiatan Non-USK
                                </span>
                                <span class="block text-xs font-normal text-slate-500">
                                    Dikenakan Tarif Sewa Ruangan
                                </span>
                            </div>
                        </label>
                    </div>

                    <x-field-error name="activity_type" />
                </div>

                {{-- Tanggal peminjaman --}}
                <div class="sm:col-span-2">
                    <label for="date" class="label">
                        Tanggal
                    </label>

                    <input
                        id="date"
                        name="date"
                        type="date"
                        x-model="date"
                        min="{{ now()->toDateString() }}"
                        max="{{ $period
                            ? $period->year . '-12-31'
                            : now()->toDateString() }}"
                        class="input @error('date') input-error @enderror"
                        required
                    >

                    <x-field-error name="date" />
                </div>

                {{-- Waktu mulai --}}
                <div>
                    <label for="start_time" class="label">
                        Mulai
                    </label>

                    <input
                        id="start_time"
                        name="start_time"
                        type="time"
                        step="900"
                        x-model="start"
                        min="{{ config('booking.open_time') }}"
                        max="{{ config('booking.close_time') }}"
                        class="input @error('start_time') input-error @enderror"
                        required
                    >

                    <x-field-error name="start_time" />
                </div>

                {{-- Waktu selesai --}}
                <div>
                    <label for="end_time" class="label">
                        Selesai
                    </label>

                    <input
                        id="end_time"
                        name="end_time"
                        type="time"
                        step="900"
                        x-model="end"
                        min="{{ config('booking.open_time') }}"
                        max="{{ config('booking.close_time') }}"
                        class="input @error('end_time') input-error @enderror"
                        required
                    >

                    <x-field-error name="end_time" />
                </div>

                {{-- Catatan --}}
                <div class="sm:col-span-2">
                    <label for="notes" class="label">
                        Catatan / Keperluan
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        maxlength="1000"
                        class="input @error('notes') input-error @enderror"
                        placeholder="Contoh: Seminar proposal skripsi, 40 peserta"
                    >{{ old('notes') }}</textarea>

                    <x-field-error name="notes" />
                </div>

                {{-- Hasil pemeriksaan ketersediaan dan estimasi biaya --}}
                <div
                    class="sm:col-span-2"
                    x-show="result"
                    x-cloak
                >
                    <template x-if="result && result.available">
                        <div class="flex flex-col gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                            <div class="flex items-center gap-2">
                                <x-icon name="circle-check" class="size-5" />
                                Ruangan tersedia
                            </div>

                            <div
                                class="pl-7 text-xs font-normal"
                                x-text="activityType === 'usk'
                                    ? 'Estimasi Biaya: Gratis (Kegiatan USK)'
                                    : (selectedRoom && Number(selectedRoom.price) > 0
                                        ? `Estimasi Biaya Sewa: Rp ${Number(selectedRoom.price).toLocaleString('id-ID')}`
                                        : 'Estimasi Biaya: Gratis')"
                            ></div>
                        </div>
                    </template>

                    <template x-if="result && !result.available">
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <div class="flex items-center gap-2 font-medium">
                                <x-icon name="circle-x" class="size-5" />
                                Ruangan tidak tersedia.
                            </div>

                            <ul
                                class="mt-2 list-disc space-y-0.5 pl-9"
                                x-show="result.errors && result.errors.length"
                            >
                                <template
                                    x-for="(error, index) in (result.errors || [])"
                                    :key="index"
                                >
                                    <li x-text="error"></li>
                                </template>
                            </ul>

                            <div
                                class="mt-2 pl-7"
                                x-show="result.conflicts && result.conflicts.length"
                            >
                                <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                                    Digunakan:
                                </div>

                                <template
                                    x-for="(conflict, index) in (result.conflicts || [])"
                                    :key="index"
                                >
                                    <div
                                        x-text="`${conflict.time} (${conflict.status})`"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Tombol tindakan --}}
            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="checkAvailability"
                    :disabled="!ready || checking"
                >
                    <x-icon name="search" class="size-4" />
                    <span
                        x-text="checking
                            ? 'Memeriksa…'
                            : 'Cek Ketersediaan'"
                    ></span>
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="!(result && result.available) || @js(! $period)"
                >
                    <x-icon name="send" class="size-4" />
                    Ajukan Peminjaman
                </button>
            </div>
        </form>

        {{-- Panel informasi --}}
        <div class="space-y-4">
            <div class="card card-body text-sm text-slate-600">
                <h3 class="mb-2 flex items-center gap-2 font-semibold text-slate-900">
                    <x-icon name="info" class="size-4 text-indigo-600" />
                    Ketentuan
                </h3>

                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Jam operasional
                        {{ config('booking.open_time') }}–{{ config('booking.close_time') }}
                        WIB.
                    </li>

                    <li>
                        Durasi minimal
                        {{ config('booking.min_duration_minutes') }}
                        menit.
                    </li>

                    <li>
                        Kegiatan sivitas akademika
                        <strong>USK digratiskan</strong>,
                        sedangkan Non-USK dikenakan tarif sewa.
                    </li>

                    @if ($period)
                        <li>
                            Periode aktif:
                            <strong>{{ $period->year }}</strong>.
                        </li>
                    @endif

                    <li>
                        Pengajuan akan ditinjau admin. Anda menerima email
                        saat disetujui atau ditolak.
                    </li>
                </ul>
            </div>

            <a
                href="{{ route('calendar') }}"
                class="card card-body flex items-center gap-3 text-sm hover:border-indigo-200"
            >
                <x-icon name="calendar-days" class="size-5 text-indigo-600" />

                <span class="flex-1">
                    Lihat kalender ketersediaan ruangan
                </span>

                <x-icon name="chevron-right" class="size-4 text-slate-400" />
            </a>
        </div>
    </div>
</x-layouts.app>