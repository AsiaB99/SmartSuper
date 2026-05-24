<?php

declare(strict_types=1);

namespace App\Support;

final class TextEncoding
{
    public static function fixMojibake(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! self::looksLikeMojibake($value)) {
            return $value;
        }

        $candidates = [
            strtr($value, self::DIRECT_REPLACEMENTS),
            self::decodeAs($value, 'ISO-8859-1'),
            self::decodeAs($value, 'Windows-1252'),
            self::reencode($value, 'ISO-8859-1'),
            self::reencode($value, 'Windows-1252'),
        ];

        $best = $value;
        $bestScore = self::mojibakeScore($value);

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            $score = self::mojibakeScore($candidate);

            if ($score < $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private static function looksLikeMojibake(string $value): bool
    {
        return preg_match('/(?:Ã.|Â|â.|ðŸ|�)/u', $value) === 1;
    }

    private static function mojibakeScore(string $value): int
    {
        return substr_count($value, 'Ã')
            + substr_count($value, 'Â')
            + substr_count($value, 'â')
            + substr_count($value, 'ðŸ')
            + substr_count($value, '�');
    }

    private static function reencode(string $value, string $singleByteEncoding): ?string
    {
        $bytes = @iconv('UTF-8', $singleByteEncoding.'//IGNORE', $value);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        $decoded = @iconv($singleByteEncoding, 'UTF-8//IGNORE', $bytes);

        return $decoded === false ? null : $decoded;
    }

    private static function decodeAs(string $value, string $sourceEncoding): ?string
    {
        $decoded = @mb_convert_encoding($value, 'UTF-8', $sourceEncoding);

        return is_string($decoded) ? $decoded : null;
    }

    private const DIRECT_REPLACEMENTS = [
        'Ãngel' => 'Ángel',
        "\u{00C3}\u{00A1}" => 'á',
        "\u{00C3}\u{00A9}" => 'é',
        "\u{00C3}\u{00AD}" => 'í',
        "\u{00C3}\u{00B3}" => 'ó',
        "\u{00C3}\u{00BA}" => 'ú',
        "\u{00C3}\u{0081}" => 'Á',
        "\u{00C3}\u{0089}" => 'É',
        "\u{00C3}\u{008D}" => 'Í',
        "\u{00C3}\u{0093}" => 'Ó',
        "\u{00C3}\u{009A}" => 'Ú',
        "\u{00C3}\u{00B1}" => 'ñ',
        "\u{00C3}\u{0091}" => 'Ñ',
        "\u{00C3}\u{00BC}" => 'ü',
        "\u{00C3}\u{009C}" => 'Ü',
        "\u{00C2}" => '',
    ];
}
