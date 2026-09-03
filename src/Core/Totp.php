<?php

namespace App\Core;

/**
 * Ikki bosqichli autentifikatsiya (2FA) uchun minimal, kutubxonasiz TOTP
 * (RFC 6238) skafoldi. Faqat sozlama (security setting) yoqilganda va
 * foydalanuvchida twofa_secret mavjud bo'lganda ishlatiladi.
 *
 * - Base32 kodlangan maxfiy kalit generatsiyasi.
 * - HMAC-SHA1 asosidagi 6 xonali kod (30 soniyalik oyna).
 * - Kichik vaqt siljishiga (±1 qadam) chidamli tekshiruv.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    /**
     * Yangi Base32 maxfiy kalit hosil qiladi.
     */
    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return self::base32Encode($bytes);
    }

    /**
     * Berilgan maxfiy kalit uchun joriy TOTP kodini hisoblaydi.
     */
    public static function code(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = (int) floor($timestamp / self::PERIOD);
        return self::codeForCounter($secret, $counter);
    }

    /**
     * Foydalanuvchi kiritgan kodni tekshiradi (±1 qadam bardoshliligi bilan).
     */
    public static function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $timestamp = $timestamp ?? time();
        $counter = (int) floor($timestamp / self::PERIOD);
        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals(self::codeForCounter($secret, $counter + $offset), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function codeForCounter(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        $binCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        $mod = $part % (10 ** self::DIGITS);
        return str_pad((string) $mod, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= self::ALPHABET[bindec($chunk)];
        }
        return $out;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(rtrim($b32, '='));
        if ($b32 === '') {
            return '';
        }
        $binary = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }
        return $out;
    }
}
