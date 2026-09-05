<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Support\Facades\Date;

/**
 * Verificador TOTP RFC-6238 compartido (confirmación de setup y challenge de login).
 *
 * Extraído de ConfirmTwoFactorAuth para que ambas flujos usen exactamente
 * la misma validación (ventana, reuso, formato).
 */
final readonly class TwoFactorCodeVerifier
{
    private const int STEP_SECONDS = 30;

    /**
     * Valida un código TOTP contra un secreto base32 encriptado (texto plano).
     *
     * @param  string  $base32Secret  Secreto en base32 (ya desencriptado).
     * @param  string  $code  Código de 6 dígitos (se normalizan espacios).
     * @param  int  $windowSeconds  Ventana de tolerancia en segundos (default 30).
     */
    public function verify(string $base32Secret, string $code, int $windowSeconds = 30): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $secret = $this->base32Decode(mb_strtoupper($base32Secret));
        if ($secret === '') {
            return false;
        }

        $windowSteps = max(0, (int) floor($windowSeconds / self::STEP_SECONDS));

        $now = Date::now()->getTimestamp();
        $counter = (int) floor($now / self::STEP_SECONDS);
        $isValid = false;

        for ($i = -$windowSteps; $i <= $windowSteps; $i++) {
            $expected = $this->totpAtCounter($secret, $counter + $i);
            if (hash_equals($expected, $code)) {
                $isValid = true;
                break;
            }
        }

        return $isValid;
    }

    private function totpAtCounter(string $secret, int $counter): string
    {
        $counter = max($counter, 0);
        $binCounter = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $binCounter, $secret, true);
        // '8bit': el hash es binario; mb_substr en UTF-8 puede truncar el
        // segmento cuando un byte multibyte queda al final del hash.
        $offset = ord(mb_substr($hash, -1, 1, '8bit')) & 0x0F;
        $segment = mb_substr($hash, $offset, 4, '8bit');
        $value = unpack('N', $segment);
        $unpacked = 0;
        if (is_array($value)) {
            $raw = $value[1] ?? 0;
            if (is_int($raw)) {
                $unpacked = $raw;
            } elseif (is_numeric($raw)) {
                $unpacked = (int) $raw;
            } else {
                $unpacked = 0;
            }
        }

        $int = $unpacked & 0x7FFFFFFF;
        $otp = $int % 1000000;

        return mb_str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $map = array_flip(mb_str_split($alphabet));
        $data = mb_rtrim($data, '=');
        $data = preg_replace('/[^A-Z2-7]/', '', $data) ?? '';

        $binary = '';
        $length = mb_strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $char = $data[$i];
            if (! isset($map[$char])) {
                return '';
            }

            $binary .= mb_str_pad(
                decbin((int) $map[$char]),
                5,
                '0',
                STR_PAD_LEFT
            );
        }

        $bytes = '';
        for ($j = 0; $j < mb_strlen($binary); $j += 8) {
            $chunk = mb_substr($binary, $j, 8);
            if (mb_strlen($chunk) < 8) {
                break;
            }

            $bytes .= chr((int) bindec($chunk));
        }

        return $bytes;
    }
}
