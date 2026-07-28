<?php
/**
 * Memverifikasi token reCAPTCHA checkbox ke server Google.
 *
 * @return array{valid: bool, message: string}
 */
function verifyRecaptchaCheckbox(string $token, string $remoteIp = ''): array
{
    if (RECAPTCHA_SECRET_KEY === '' || RECAPTCHA_SECRET_KEY === 'YOUR_SECRET_KEY') {
        return [
            'valid' => false,
            'message' => 'Konfigurasi reCAPTCHA server belum diatur.',
        ];
    }

    $payload = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $remoteIp,
    ];

    $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $rawResponse = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($rawResponse === false || $curlError !== '' || $httpCode !== 200) {
        return [
            'valid' => false,
            'message' => 'Verifikasi reCAPTCHA tidak dapat dilakukan.',
        ];
    }

    $response = json_decode($rawResponse, true);
    $success = is_array($response) && ($response['success'] ?? false) === true;

    if (!$success) {
        return [
            'valid' => false,
            'message' => 'Verifikasi reCAPTCHA tidak valid atau sudah kedaluwarsa.',
        ];
    }

    return [
        'valid' => true,
        'message' => 'Token reCAPTCHA valid.',
    ];
}
