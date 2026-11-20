<?php

/**
 * @package Cropper
 */

namespace FriendsOfRedaxo\Cropper\Cropper;

use function is_string;

/**
 * Liest die Seitenverhältnisse aus der AddOn-Einstellung `aspect_ratios`.
 *
 * Eine Zeile je Verhältnis, optional mit Beschriftung dahinter:
 *
 *     16:9 Hero
 *     16:7 | Banner
 *     4:3
 *
 * Dezimalwerte dürfen Komma oder Punkt verwenden (`2,35:1`). Ungültige Zeilen werden übersprungen.
 */
final class AspectRatioConfig
{
    private const NUMBER = '\d+(?:[.,]\d+)?';

    /**
     * @return list<array{w: string, h: string, r: float, label: string}>
     */
    public static function parse(mixed $config): array
    {
        if (!is_string($config) || '' === trim($config)) {
            return [];
        }

        $lines = preg_split('/\R/u', $config) ?: [];
        $ratios = [];

        foreach ($lines as $line) {
            $ratio = self::parseLine($line);
            if (null !== $ratio) {
                $ratios[] = $ratio;
            }
        }

        return $ratios;
    }

    /**
     * @return array{w: string, h: string, r: float, label: string}|null
     */
    public static function parseLine(string $line): ?array
    {
        $pattern = '/^\s*(' . self::NUMBER . ')\s*:\s*(' . self::NUMBER . ')(?:\s+(.*))?$/u';
        if (1 !== preg_match($pattern, $line, $matches)) {
            return null;
        }

        $width = str_replace(',', '.', $matches[1]);
        $height = str_replace(',', '.', $matches[2]);

        if ((float) $width <= 0.0 || (float) $height <= 0.0) {
            return null;
        }

        $label = trim($matches[3] ?? '');
        // Optionales Trennzeichen zwischen Verhältnis und Beschriftung: "16:9 | Hero", "16:9 - Hero"
        $label = trim((string) preg_replace('/^[|=\-–—]\s*/u', '', $label));

        return [
            'w' => $width,
            'h' => $height,
            'r' => (float) $width / (float) $height,
            'label' => $label,
        ];
    }
}
