<?php
/**
 * Invite Link Encryption Helpers
 * Generates and decodes encrypted invite tokens for referral-based self-registration.
 */

function getInviteSecretKey(): string {
    $config = parse_ini_file(__DIR__ . '/../config/config.ini', true);
    $raw = $config['invite']['secret_key'] ?? 'AALAYAInvite2024@SecretKey#XY';
    return hash('sha256', $raw, true); // 32-byte binary key for AES-256
}

/**
 * Encrypt a referral_code into a URL-safe token.
 */
function generateInviteToken(string $referral_code): string {
    $key = getInviteSecretKey();
    $iv  = random_bytes(16);
    $encrypted = openssl_encrypt($referral_code, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    // Prepend IV so we can decrypt later
    $payload = $iv . $encrypted;
    return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
}

/**
 * Decode an invite token back to a referral_code.
 * Returns false if the token is invalid.
 */
function decodeInviteToken(string $token): string|false {
    $key = getInviteSecretKey();
    // Restore base64 padding
    $b64 = strtr($token, '-_', '+/');
    $b64 = str_pad($b64, strlen($b64) + (4 - strlen($b64) % 4) % 4, '=');
    $payload = base64_decode($b64, true);
    if ($payload === false || strlen($payload) < 17) {
        return false;
    }
    $iv        = substr($payload, 0, 16);
    $encrypted = substr($payload, 16);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}
