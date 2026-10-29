<?php

// Dosya tabanlı hız sınırlayıcı. Dizin yazılabilir değilse devre dışı kalır.
function rateLimitFilePath(string $key): ?string
{
    $dir = dirname(__DIR__) . '/storage/rate_limits';

    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return null;
    }

    return $dir . '/' . hash('sha256', $key) . '.json';
}

function rateLimitState(string $file, int $maxAttempts, int $decaySeconds): array
{
    $state = [
        'allowed' => true,
        'remaining' => $maxAttempts,
        'retry_after' => 0,
    ];

    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        return $state;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return $state;
    }

    $raw = stream_get_contents($handle);
    $data = json_decode($raw !== false && $raw !== '' ? $raw : '', true);

    $now = time();

    if (!is_array($data) || !isset($data['count'], $data['reset']) || (int) $data['reset'] <= $now) {
        $data = ['count' => 0, 'reset' => $now + $decaySeconds];
    }

    $state['remaining'] = max(0, $maxAttempts - (int) $data['count']);
    $state['allowed'] = (int) $data['count'] < $maxAttempts;
    $state['retry_after'] = max(0, (int) $data['reset'] - $now);

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data));

    flock($handle, LOCK_UN);
    fclose($handle);

    return $state;
}

function rateLimitHit(string $key, int $decaySeconds): void
{
    $file = rateLimitFilePath($key);
    if ($file === null) {
        return;
    }

    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        return;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return;
    }

    $raw = stream_get_contents($handle);
    $data = json_decode($raw !== false && $raw !== '' ? $raw : '', true);

    $now = time();

    if (!is_array($data) || !isset($data['count'], $data['reset']) || (int) $data['reset'] <= $now) {
        $data = ['count' => 0, 'reset' => $now + $decaySeconds];
    }

    $data['count'] = (int) $data['count'] + 1;

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data));

    flock($handle, LOCK_UN);
    fclose($handle);
}

function rateLimitClear(string $key): void
{
    $file = rateLimitFilePath($key);
    if ($file !== null && is_file($file)) {
        @unlink($file);
    }
}

function rateLimitCheck(string $key, int $maxAttempts, int $decaySeconds): array
{
    $file = rateLimitFilePath($key);

    if ($file === null) {
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
    }

    return rateLimitState($file, $maxAttempts, $decaySeconds);
}

function clientIp(): string
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);
        $candidate = trim($parts[0]);

        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return $candidate;
        }
    }

    $remote = $_SERVER['REMOTE_ADDR'] ?? '';

    return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '0.0.0.0';
}
