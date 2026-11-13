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

        if ($locClean === '' && $cellClean === '') {
            return '-';
        }

        // Jika salah satu kosong, sanitize duplikasi pada string yang ada
        if ($locClean === '') {
            return self::sanitizeLocationString($cellClean);
        }
        if ($cellClean === '') {
            return self::sanitizeLocationString($locClean);
        }

        // Tangani jika cell berisi koma (multi-lokasi)
        if (str_contains($cellClean, ',')) {
            $parts = array_map('trim', explode(',', $cellClean));
            $res = [];
            foreach ($parts as $p) {
                $formatted = self::formatLocDisplay($locClean, $p);
                if ($formatted !== '-' && !in_array($formatted, $res, true)) {
                    $res[] = $formatted;
                }
            }
            return !empty($res) ? implode(', ', $res) : '-';
        }

        // Bersihkan jika cell sudah diawali dengan locClean (misal loc=M7 dan cell=M7-1B atau cell=M7-M7-1B)
        while (preg_match('/^' . preg_quote($locClean, '/') . '[-_ ]*(.*)$/i', $cellClean, $matches)) {
            if ($matches[1] === '') {
                return self::sanitizeLocationString($locClean);
            }
            $cellClean = trim($matches[1], "-_ ");
        }

        if ($cellClean === '') {
            return self::sanitizeLocationString($locClean);
        }

        $combined = "{$locClean}-{$cellClean}";
        return self::sanitizeLocationString($combined);
    }

    /**
     * Helper merapikan dan menghapus segmen lokasi berulang (misal M7-M7-M7-1B -> M7-1B)
     */
    public static function sanitizeLocationString(?string $text): string
    {
        $val = trim((string)$text);
        if ($val === '' || $val === '-' || $val === '--') {
            return '-';
        }

        if (str_contains($val, ',')) {
            $parts = array_map('trim', explode(',', $val));
            $res = [];
            foreach ($parts as $p) {
                $cleaned = self::sanitizeLocationString($p);
                if ($cleaned !== '-' && !in_array($cleaned, $res, true)) {
                    $res[] = $cleaned;
                }
            }
            return !empty($res) ? implode(', ', $res) : '-';
        }

        $segments = array_values(array_filter(explode('-', $val), fn($s) => trim($s) !== ''));
        if (empty($segments)) {
            return '-';
        }

        $deduped = [];
        foreach ($segments as $seg) {
            $segClean = trim($seg);
            if (empty($deduped) || strtoupper($deduped[count($deduped) - 1]) !== strtoupper($segClean)) {
                $deduped[] = $segClean;
            }
        }

        return implode('-', $deduped);
    }
}

