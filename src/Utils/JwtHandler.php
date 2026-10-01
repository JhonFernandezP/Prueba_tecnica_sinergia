<?php
declare(strict_types=1);

namespace App\Utils;

use Exception;

/**
 * Gestor de JSON Web Tokens (JWT) utilizando el algoritmo HMAC-SHA256
 */
class JwtHandler
{
    private static string $secretKey = 'GestionPacientes_Secure_JWT_Secret_Key_2026_@!';
    private static string $algorithm = 'HS256';

    /**
     * Permite modificar la clave secreta
     */
    public static function setSecretKey(string $secret): void
    {
        self::$secretKey = $secret;
    }

    /**
     * Genera un token JWT firmado
     *
     * @param array $payload Datos a incluir en el cuerpo del token
     * @param int $ttl Tiempo de vida en segundos (por defecto 24 horas = 86400s)
     * @return string
     */
    public static function generateToken(array $payload, int $ttl = 86400): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ];

        $issuedAt = time();
        $expireAt = $issuedAt + $ttl;

        $tokenPayload = array_merge([
            'iat' => $issuedAt,
            'exp' => $expireAt
        ], $payload);

        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($tokenPayload));

        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, self::$secretKey, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * Valida y decodifica un token JWT
     *
     * @param string $token
     * @return array|null Retorna el payload decodificado si es válido, null si no
     */
    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;

        // Verificar firma
        $expectedSignature = hash_hmac('sha256', $header64 . '.' . $payload64, self::$secretKey, true);
        $expectedSignature64 = self::base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedSignature64, $signature64)) {
            return null;
        }

        // Decodificar payload
        $payloadJson = self::base64UrlDecode($payload64);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Codificación Base64 URL-safe
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodificación Base64 URL-safe
     */
    private static function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
