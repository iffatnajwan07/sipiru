<?php

namespace App\Support;

/**
 * Pengecekan kemiripan nama sederhana (tanpa AI/ML) — HANYA untuk peringatan UX.
 * Keunikan sebenarnya dijamin oleh unique constraint pada kolom *_normalized.
 */
final class SimilarityChecker
{
    public const THRESHOLD = 0.8;

    /** Kata umum yang diabaikan saat membandingkan inti nama. */
    private const STOPWORDS = [
        'gedung', 'ruang', 'ruangan', 'universitas', 'syiah', 'kuala', 'usk', 'unsyiah',
        'fakultas', 'the', 'dan', 'of',
    ];

    public static function score(string $a, string $b): float
    {
        $a = self::simplify($a);
        $b = self::simplify($b);

        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        $scores = [self::levenshteinRatio($a, $b)];

        // Inti nama (tanpa kata umum) sama, atau salah satu bagian dari yang lain
        $coreA = self::coreTokens($a);
        $coreB = self::coreTokens($b);
        if ($coreA !== [] && $coreB !== []) {
            if ($coreA === $coreB) {
                $scores[] = 0.95;
            } elseif (array_diff($coreA, $coreB) === [] || array_diff($coreB, $coreA) === []) {
                $scores[] = 0.85;
            }
        }

        return max($scores);
    }

    public static function isSimilar(string $a, string $b): bool
    {
        return self::score($a, $b) >= self::THRESHOLD;
    }

    private static function simplify(string $value): string
    {
        $value = NameNormalizer::normalize($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /** @return list<string> */
    private static function coreTokens(string $value): array
    {
        $tokens = array_values(array_unique(array_diff(explode(' ', $value), self::STOPWORDS, [''])));
        sort($tokens);

        return $tokens;
    }

    private static function levenshteinRatio(string $a, string $b): float
    {
        $max = max(strlen($a), strlen($b));

        return $max === 0 ? 1.0 : 1 - (levenshtein($a, $b) / $max);
    }
}
