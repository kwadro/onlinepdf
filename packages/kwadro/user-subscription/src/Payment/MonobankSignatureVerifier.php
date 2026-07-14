<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Psr\Cache\CacheItemPoolInterface;

final class MonobankSignatureVerifier
{
    private const CACHE_KEY = 'kwadro.monobank.pubkey';

    public function __construct(
        private MonobankClient $client,
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function verify(string $body, string $signature): bool
    {
        if ($signature === '' || $body === '') {
            return false;
        }

        $publicKey = $this->getPublicKey();
        if ($publicKey === '') {
            return false;
        }

        $publicKeyFormatted = str_contains($publicKey, 'BEGIN PUBLIC KEY')
            ? $publicKey
            : "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----";

        $key = openssl_pkey_get_public($publicKeyFormatted);
        if ($key === false) {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        return 1 === openssl_verify($body, $decodedSignature, $key, OPENSSL_ALGO_SHA256);
    }

    private function getPublicKey(): string
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            return (string) $item->get();
        }

        $key = $this->client->getPublicKey();
        $item->set($key);
        $item->expiresAfter(3600);
        $this->cache->set($item);

        return $key;
    }
}
