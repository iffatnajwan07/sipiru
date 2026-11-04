@php($editing = $building->exists)
<x-layouts.app :title="$editing ? 'Edit Gedung' : 'Tambah Gedung'">
    <x-page-header :title="$editing ? 'Edit Gedung' : 'Tambah Gedung'" :back="$editing ? route('admin.buildings.show', $building) : route('admin.buildings.index')" />

    <form method="POST" action="{{ $editing ? route('admin.buildings.update', $building) : route('admin.buildings.store') }}" class="card max-w-3xl"
          x-data="buildingForm({ url: @js(route('admin.buildings.check')), ignore: @js($building->id), name: @js(old('name', $building->name) ?? ''), code: @js(old('code', $building->code) ?? '') })">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body grid gap-5 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="name" class="label">Nama Gedung</label>
                <input id="name" name="name" x-model="name" required maxlength="150" class="input @error('name') input-error @enderror" :class="result?.name_exists && 'input-error'" placeholder="Contoh: Gedung ICT" autocomplete="off">
                <x-field-error name="name" />
            </div>
            <div>
                <label for="code" class="label">Kode Gedung</label>
                <input id="code" name="code" x-model="code" required maxlength="30" class="input uppercase @error('code') input-error @enderror" :class="result?.code_exists && 'input-error'" placeholder="ICT" autocomplete="off">
                <x-field-error name="code" />
            </div>

            {{-- Hasil pengecekan duplikasi --}}
            <div class="space-y-3 sm:col-span-3" x-show="result" x-cloak>
                <template x-if="result?.name_exists">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        <div class="flex items-center gap-2 font-medium"><x-icon name="triangle-alert" class="size-4" /> Gedung dengan nama tersebut sudah terdaftar.</div>
                        <div class="mt-2 rounded-md bg-white/70 px-3 py-2 text-slate-700">
                            <div class="font-semibold" x-text="result.name_match.name"></div>
                            <div class="text-xs">Kode: <span x-text="result.name_match.code"></span> · Status: <span x-text="result.name_match.status"></span></div>
                        </div>
                    </div>
                </template>
                <template x-if="result?.code_exists">
                    <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-800">
                        <x-icon name="triangle-alert" class="size-4" /> <span x-text="`Kode sudah digunakan oleh ${result.code_match.name}.`"></span>
                    </div>
                </template>
                <template x-if="!result?.name_exists && result?.similar?.length && !dismissedSimilar">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <div class="flex items-center gap-2 font-medium"><x-icon name="triangle-alert" class="size-4" /> Nama ini mirip dengan gedung yang sudah ada.</div>
                        <div class="mt-1">Apakah yang Anda maksud:</div>
                        <ul class="mt-1 space-y-0.5">
                            <template x-for="s in result.similar" :key="s.id">
                                <li><span class="font-semibold" x-text="s.name"></span> <span class="text-xs" x-text="`(Kode: ${s.code} · ${s.status})`"></span>?</li>
                            </template>
                        </ul>
                        <div class="mt-3 flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" @click="name = ''; $refs.nameFocus?.focus(); document.getElementById('name').focus()">Gunakan nama lain</button>
                            <button type="button" class="btn btn-ghost btn-sm" @click="dismissedSimilar = true">Nama ini berbeda, lanjutkan</button>
                        </div>
                    </div>
                </template>
                <template x-if="!blocked && !(result?.similar?.length && !dismissedSimilar) && name.trim()">
                    <div class="flex items-center gap-2 text-sm text-emerald-700"><x-icon name="check" class="size-4" /> Nama & kode tersedia.</div>
                </template>
            </div>

            <div class="sm:col-span-3">
                <label for="address" class="label">Alamat</label>
                <input id="address" name="address" value="{{ old('address', $building->address) }}" maxlength="255" class="input">
                <x-field-error name="address" />
            </div>
            <div class="sm:col-span-3">
                <label for="description" class="label">Deskripsi</label>
                <textarea id="description" name="description" rows="3" class="input">{{ old('description', $building->description) }}</textarea>
                <x-field-error name="description" />
            </div>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $building->is_active)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Gedung aktif
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
            <a href="{{ route('admin.buildings.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary" :disabled="blocked"><x-icon name="check" class="size-4" /> Simpan Gedung</button>
        </div>
    </form>
</x-layouts.app>
