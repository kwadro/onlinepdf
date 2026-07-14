<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

final class LiqPaySigner
{
    public function __construct(
        private string $privateKey,
    ) {
    }

    /** @param array<string, mixed> $params */
    public function encodeData(array $params): string
    {
        return base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE));
    }

    public function sign(string $data, int $apiVersion = 3): string
    {
        $signString = $this->privateKey . $data . $this->privateKey;

        if ($apiVersion >= 7) {
            return base64_encode(hash('sha3-256', $signString, true));
        }

        return base64_encode(sha1($signString, true));
    }

    /** @param array<string, mixed> $params */
    public function encodeAndSign(array $params): array
    {
        $apiVersion = (int) ($params['version'] ?? 3);
        $data = $this->encodeData($params);

        return [
            'data' => $data,
            'signature' => $this->sign($data, $apiVersion),
        ];
    }

    public function verify(string $data, string $signature): bool
    {
        $decoded = json_decode(base64_decode($data, true) ?: '', true);
        $apiVersion = is_array($decoded) ? (int) ($decoded['version'] ?? 3) : 3;

        return hash_equals($this->sign($data, $apiVersion), $signature);
    }

    /** @return array<string, mixed> */
    public function decode(string $data): array
    {
        $decoded = json_decode(base64_decode($data, true) ?: '', true);

        return is_array($decoded) ? $decoded : [];
    }
}
