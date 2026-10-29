<?php

// T.C. Kimlik Numarası sağlama algoritması
function isValidTcNo(string $tcNo): bool
{
    if (!preg_match('/^[1-9][0-9]{10}$/', $tcNo)) {
        return false;
    }

    $digits = array_map('intval', str_split($tcNo));

    $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];

    $tenth = (($oddSum * 7) - $evenSum) % 10;
    if ($tenth < 0) {
        $tenth += 10;
    }

    if ($tenth !== $digits[9]) {
        return false;
    }

    $firstTenSum = array_sum(array_slice($digits, 0, 10));

    return $firstTenSum % 10 === $digits[10];
}

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone(string $phone): bool
{
    if ($phone === '') {
        return true;
    }

    return (bool) preg_match('/^0[5][0-9]{9}$/', preg_replace('/\s+/', '', $phone));
}

function limitText(string $value, int $maxLength): string
{
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function normalizeText(string $value): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $value));
}
