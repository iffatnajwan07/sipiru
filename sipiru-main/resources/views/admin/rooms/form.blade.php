@php
    $editing = $room->exists;
@endphp

<x-layouts.app :title="$editing ? 'Edit Ruangan' : 'Tambah Ruangan'">

    <x-page-header
        :title="$editing ? 'Edit Ruangan' : 'Tambah Ruangan'"
        :back="$editing
            ? route('admin.rooms.show', $room)
            : route('admin.rooms.index')"
    />

    @if ($buildings->isEmpty())

        <div class="card">
            <x-empty
                message="Belum ada gedung. Tambahkan gedung terlebih dahulu."
                icon="building-2"
            >
                <a
                    href="{{ route('admin.buildings.create') }}"
                    class="btn btn-primary btn-sm"
                >
                    Tambah Gedung
                </a>
            </x-empty>
        </div>

    @else

        <form
            method="POST"
            action="{{ $editing
                ? route('admin.rooms.update', $room)
                : route('admin.rooms.store') }}"
            class="card max-w-3xl"
            x-data="roomForm({
                url: @js(route('admin.rooms.check')),
                ignore: @js($room->id),
                buildingId: @js((string) old('building_id', $room->building_id)),
                name: @js(old('name', $room->name ?? '')),
                code: @js(old('code', $room->code ?? ''))
            })"
        >
            @csrf

            @if ($editing)
                @method('PUT')
            @endif

            <div class="card-body grid gap-5 sm:grid-cols-2">

                {{-- Pilihan gedung --}}
                <div class="sm:col-span-2">
                    <label for="building_id" class="label">
                        Gedung
                    </label>

                    <select
                        id="building_id"
                        name="building_id"
                        x-model="buildingId"
                        required
                        class="input @error('building_id') input-error @enderror"
                    >
                        <option value="">Pilih Gedung</option>

                        @foreach ($buildings as $building)
                            <option
                                value="{{ $building->id }}"
                                @selected(
                                    (string) old('building_id', $room->building_id)
                                    === (string) $building->id
                                )
                            >
                                {{ $building->name }}
                                ({{ $building->code }}){{ $building->is_active ? '' : ' — nonaktif' }}
                            </option>
                        @endforeach
                    </select>

                    <x-field-error name="building_id" />
                </div>

                {{-- Nama ruangan --}}
                <div>
                    <label for="name" class="label">
                        Nama Ruang
                    </label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        x-model="name"
                        required
                        maxlength="150"
                        autocomplete="off"
                        placeholder="Ruang Seminar A"
                        class="input @error('name') input-error @enderror"
                        :class="result?.name_exists ? 'input-error' : ''"
                    >

                    <x-field-error name="name" />
                </div>

                {{-- Kode ruangan --}}
                <div>
                    <label for="code" class="label">
                        Kode Ruang
                    </label>

                    <input
                        id="code"
                        name="code"
                        type="text"
                        x-model="code"
                        required
                        maxlength="40"
                        autocomplete="off"
                        placeholder="ICT-SA-01"
                        class="input uppercase @error('code') input-error @enderror"
                        :class="result?.code_exists ? 'input-error' : ''"
                    >

                    <x-field-error name="code" />
                </div>

                {{-- Informasi pengecekan duplikasi --}}
                <div
                    class="space-y-2 text-sm sm:col-span-2"
                    x-show="result && buildingId"
                    x-cloak
                >
                    <template x-if="result?.name_exists">
                        <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 font-medium text-red-800">
                            <x-icon
                                name="triangle-alert"
                                class="size-4"
                            />

                            <span
                                x-text="`Ruang '${result.name_match?.name ?? name}' sudah ada di ${result.building_name ?? 'gedung ini'}.`"
                            ></span>
                        </div>
                    </template>

                    <template x-if="result?.code_exists">
                        <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 font-medium text-red-800">
                            <x-icon
                                name="triangle-alert"
                                class="size-4"
                            />

                            <span
                                x-text="`Kode ${result.code_match?.code ?? code} sudah digunakan oleh ${result.code_match?.name ?? 'ruangan lain'} di gedung ini.`"
                            ></span>
                        </div>
                    </template>

                    <template x-if="result && !result.name_exists && name.trim()">
                        <div class="flex items-center gap-2 text-emerald-700">
                            <x-icon name="check" class="size-4" />
                            Nama ruang tersedia di gedung ini.
                        </div>
                    </template>

                    <template x-if="result?.other_buildings?.length">
                        <div class="flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sky-800">
                            <x-icon
                                name="info"
                                class="mt-0.5 size-4"
                            />

                            <span>
                                Info: Nama
                                "<span x-text="name"></span>"
                                juga digunakan pada
                                <span
                                    class="font-medium"
                                    x-text="result.other_buildings.join(', ')"
                                ></span>.
                                (Diperbolehkan karena gedung berbeda.)
                            </span>
                        </div>
                    </template>
                </div>

                {{-- Lantai --}}
                <div>
                    <label for="floor" class="label">
                        Lantai
                    </label>

                    <input
                        id="floor"
                        name="floor"
                        type="text"
                        value="{{ old('floor', $room->floor) }}"
                        maxlength="20"
                        class="input @error('floor') input-error @enderror"
                        placeholder="1"
                    >

                    <x-field-error name="floor" />
                </div>

                {{-- Kapasitas --}}
                <div>
                    <label for="capacity" class="label">
                        Kapasitas (orang)
                    </label>

                    <input
                        id="capacity"
                        name="capacity"
                        type="number"
                        min="1"
                        max="10000"
                        value="{{ old('capacity', $room->capacity) }}"
                        required
                        class="input @error('capacity') input-error @enderror"
                        placeholder="50"
                    >

                    <x-field-error name="capacity" />
                </div>

                {{-- Harga sewa --}}
                <div class="sm:col-span-2">
                    <label for="price" class="label">
                        Harga Sewa (Rp)
                    </label>

                    <input
                        id="price"
                        name="price"
                        type="number"
                        min="0"
                        value="{{ old('price', $room->price) }}"
                        class="input @error('price') input-error @enderror"
                        placeholder="150000"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Kosongkan atau isi 0 jika ruangan gratis.
                    </p>

                    <x-field-error name="price" />
                </div>

                {{-- Deskripsi dan fasilitas --}}
                <div class="sm:col-span-2">
                    <label for="description" class="label">
                        Deskripsi / Fasilitas
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="input @error('description') input-error @enderror"
                        placeholder="Proyektor, AC, sound system..."
                    >{{ old('description', $room->description) }}</textarea>

                    <x-field-error name="description" />
                </div>

                {{-- Status ruangan --}}
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked((bool) old('is_active', $room->is_active))
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >

                    Ruangan aktif (dapat dipinjam)
                </label>

            </div>

            {{-- Tombol aksi --}}
            <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">

                <a
                    href="{{ route('admin.rooms.index') }}"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="blocked"
                >
                    <x-icon name="check" class="size-4" />
                    {{ $editing ? 'Simpan Perubahan' : 'Simpan Ruangan' }}
                </button>

            </div>
        </form>

    @endif

</x-layouts.app>