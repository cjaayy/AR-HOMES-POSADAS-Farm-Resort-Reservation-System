<?php

namespace App\Services;

use App\Models\User;
use App\Models\Reservation;
use Exception;

class IDGeneratorService
{
    /**
     * Generate a User ID in format: USR-YYYYMMDD-XXXX
     */
    public static function generateUserId(): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $date = date('Ymd');
            $random = self::generateRandomAlphanumeric(4);
            $userId = "USR-{$date}-{$random}";

            if (!User::where('user_id', $userId)->exists()) {
                return $userId;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        throw new Exception("Unable to generate unique user ID after {$maxAttempts} attempts");
    }

    /**
     * Generate a Reservation ID in format: RES-YYYYMMDD-XXXXX
     */
    public static function generateReservationId(): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $date = date('Ymd');
            $random = self::generateRandomAlphanumeric(5);
            $reservationId = "RES-{$date}-{$random}";

            if (!Reservation::where('reservation_id', $reservationId)->exists()) {
                return $reservationId;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        throw new Exception("Unable to generate unique reservation ID after {$maxAttempts} attempts");
    }

    /**
     * Generate random alphanumeric string excluding confusing characters: 0, O, 1, I, L
     */
    public static function generateRandomAlphanumeric(int $length): string
    {
        $characters = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function validateUserId(?string $userId): bool
    {
        if (!$userId) return false;
        return preg_match('/^USR-\d{8}-[A-Z0-9]{4}$/', $userId) === 1;
    }

    public static function validateReservationId(?string $reservationId): bool
    {
        if (!$reservationId) return false;
        return preg_match('/^RES-\d{8}-[A-Z0-9]{5}$/', $reservationId) === 1;
    }

    public static function extractDateFromUserId(string $userId): ?string
    {
        if (!self::validateUserId($userId)) {
            return null;
        }

        $parts = explode('-', $userId);
        $dateStr = $parts[1];
        return substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
    }

    public static function extractDateFromReservationId(string $reservationId): ?string
    {
        if (!self::validateReservationId($reservationId)) {
            return null;
        }

        $parts = explode('-', $reservationId);
        $dateStr = $parts[1];
        return substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
    }
}
