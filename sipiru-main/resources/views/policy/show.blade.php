<x-layouts.app title="Kebijakan Peminjaman Ruang">
    <x-page-header title="Kebijakan Peminjaman Ruang" subtitle="Harap baca dan setujui kebijakan sebelum menggunakan sistem." />
    <div class="card max-w-3xl">
        <div class="card-body">@include('policy._content')</div>
        <div class="border-t border-slate-100 px-5 py-4">
            @if ($user->hasAcceptedCurrentPolicy())
                <div class="flex items-center gap-2 text-sm text-emerald-700">
                    <x-icon name="badge-check" class="size-4" />
                    Anda telah menyetujui kebijakan ini pada {{ $user->policy_accepted_at->translatedFormat('d F Y H:i') }}.
                </div>
            @else
                <form method="POST" action="{{ route('policy.accept') }}" x-data="{ agree: false }" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    @csrf
                    <label class="flex items-start gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="agree" value="1" x-model="agree" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Saya telah membaca dan menyetujui kebijakan.
                    </label>
                    <button class="btn btn-primary" :disabled="!agree">Setujui &amp; Lanjutkan</button>
                </form>
                <x-field-error name="agree" />
            @endif
        </div>
    </div>
</x-layouts.app>
