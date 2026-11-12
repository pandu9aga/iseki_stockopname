<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $table = 'areas';

    protected $fillable = [
        'area_name',
        'area_letter',
    ];

    /**
     * Urutan abjad area standar untuk copy langsung iseki_label:
     * E, ET, M, W, P, GR, baru OTHER
     */
    public const ORDER_LIST = ['E', 'ET', 'M', 'W', 'P', 'GR', 'OTHER'];

    /**
     * Helper untuk mendeteksi abjad area dari string lokasi / cell / rack.
     * Mengutamakan prefix multi-huruf (ET, GR) sebelum single-letter (E, M, W, P).
     */
    public static function detectAreaLetter(?string $location, ?string $cell = null): string
    {
        $text = strtoupper(trim(($location ?? '') . ' ' . ($cell ?? '')));
        if ($text === '' || $text === '-') {
            return 'OTHER';
        }

        // Cek multi-huruf khusus terlebih dahulu
        if (preg_match('/\bET\b/', $text) || str_contains($text, 'ET')) {
            return 'ET';
        }
        if (preg_match('/\bGR\b/', $text) || str_contains($text, 'GR')) {
            return 'GR';
        }

        // Cek huruf tunggal standar E, M, W, P
        // Periksa huruf pertama kata atau karakter abjad pertama
        if (preg_match('/[A-Z]/', $text, $matches)) {
            $char = $matches[0];
            if (in_array($char, ['E', 'M', 'W', 'P'], true)) {
                return $char;
            }
        }

        return 'OTHER';
    }

    /**
     * Helper urutan sorting abjad area (E -> ET -> M -> W -> P -> GR -> OTHER -> dll)
     */
    public static function sortWeight(string $areaLetter): int
    {
        $key = strtoupper(trim($areaLetter));
        $idx = array_search($key, self::ORDER_LIST, true);
        return $idx !== false ? $idx : (100 + crc32($key) % 100);
    }

    /**
     * Format gabungan Location/Area dan Cell:
     * - Jika keduanya ada: "Area-Cell" (misal: "W46-5D")
     * - Jika salah satu kosong/strip/null: hanya tampilkan yang terisi (misal: "W46" atau "5D")
     * - Jika keduanya kosong: "-"
     */
    public static function formatLocDisplay(?string $loc, ?string $cell = null): string
    {
        $locClean = trim((string)$loc);
        $cellClean = trim((string)$cell);

        // Bersihkan nilai '-' atau strip kosong ganda
        if ($locClean === '-' || $locClean === '--') $locClean = '';
        if ($cellClean === '-' || $cellClean === '--') $cellClean = '';

        if ($locClean !== '' && $cellClean !== '') {
            return "{$locClean}-{$cellClean}";
        }

        if ($locClean !== '') {
            return $locClean;
        }

        if ($cellClean !== '') {
            return $cellClean;
        }

        return '-';
    }
}

