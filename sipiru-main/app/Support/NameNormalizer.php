<?php

namespace App\Support;

use Normalizer;

/**
 * Normalisasi nama/kode untuk pencegahan duplikasi.
 *
 * "  Gedung\u{00A0}  ICT " → "gedung ict"
 */
final class NameNormalizer
{
    public static function normalize(?string $value): string
    {
        $value = (string) $value;

        // Samakan bentuk Unicode (mis. huruf full-width → ASCII)
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        // Hapus karakter zero-width
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value) ?? $value;

        // Semua jenis whitespace (spasi, tab, newline, NBSP, dll) → satu spasi
        $value = preg_replace('/[\s\p{Z}]+/u', ' ', $value) ?? $value;

        return mb_strtolower(trim($value), 'UTF-8');
    }

    /** Nilai tampilan: trim + rapikan spasi, huruf besar/kecil dipertahankan. */
    public static function clean(?string $value): string
    {
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', (string) $value) ?? '';

        return trim(preg_replace('/[\s\p{Z}]+/u', ' ', $value) ?? '');
    }
}
