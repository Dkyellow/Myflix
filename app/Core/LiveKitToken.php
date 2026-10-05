<?php

namespace App\Core;

class LiveKitToken {
    private string $apiKey;
    private string $apiSecret;
    private string $identity;
    private string $name = '';
    private string $metadata = '';
    private int $ttl = 14400; // 4 hours
    private array $grants = [];

    public function __construct(?string $apiKey = null, ?string $apiSecret = null) {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->apiKey = $apiKey ?: ($config['livekit']['api_key'] ?? 'devkey');
        $this->apiSecret = $apiSecret ?: ($config['livekit']['api_secret'] ?? 'secret');
    }

    public function setIdentity(string $identity): self {
        $this->identity = $identity;
        return $this;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function setMetadata(string $metadata): self {
        $this->metadata = $metadata;
        return $this;
    }

    public function setTtl(int $seconds): self {
        $this->ttl = $seconds;
        return $this;
    }

    public function addGrant(array $grant): self {
        $this->grants = array_merge($this->grants, $grant);
        return $this;
    }

    public function toJwt(): string {
        $now = time();
        $payload = [
            'iss' => $this->apiKey,
            'sub' => $this->identity,
            'nbf' => $now - 10,
            'exp' => $now + $this->ttl,
            'name' => $this->name,
            'video' => $this->grants,
        ];

        if (!empty($this->metadata)) {
            $payload['metadata'] = $this->metadata;
        }

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $base64Header = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $base64Payload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->apiSecret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
