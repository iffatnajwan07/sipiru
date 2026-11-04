<?php

namespace Tests\Unit;

use App\Support\NameNormalizer;
use App\Support\SimilarityChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NameNormalizerTest extends TestCase
{
    public static function variants(): array
    {
        return [
            ['Gedung ICT'], ['gedung ict'], ['GEDUNG ICT'], ['Gedung  ICT'], ["  Gedung\tICT \n"],
            ["Gedung\u{00A0}ICT"], ["Gedung\u{200B} ICT"], ['Ｇｅｄｕｎｇ ＩＣＴ'],
        ];
    }

    #[DataProvider('variants')]
    public function test_variants_normalize_to_same_value(string $input): void
    {
        $this->assertSame('gedung ict', NameNormalizer::normalize($input));
    }

    public function test_clean_keeps_case_but_collapses_whitespace(): void
    {
        $this->assertSame('Ruang Seminar A', NameNormalizer::clean('  Ruang   Seminar  A '));
    }

    public function test_similarity_detects_related_building_names(): void
    {
        $this->assertTrue(SimilarityChecker::isSimilar('Gedung ICT', 'Gedung ICT USK'));
        $this->assertTrue(SimilarityChecker::isSimilar('Gedung ICT USK', 'Gedung ICT-Universitas Syiah Kuala'));
        $this->assertTrue(SimilarityChecker::isSimilar('Gedung Rektorat', 'Gedung Rektrat'));
        $this->assertFalse(SimilarityChecker::isSimilar('Gedung ICT', 'Gedung FKIP'));
        $this->assertFalse(SimilarityChecker::isSimilar('Gedung Rektorat', 'Gedung FKIP'));
    }
}
