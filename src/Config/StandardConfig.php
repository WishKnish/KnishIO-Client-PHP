<?php
/*
Enhanced Configuration System for PHP SDK

Implements JavaScript/TypeScript reference patterns using modern PHP 8.1+ features
with comprehensive type safety, validation, and fluent interfaces.
*/

declare(strict_types=1);

namespace WishKnish\KnishIO\Client\Config;

use WishKnish\KnishIO\Client\Exception\InvalidConfigurationException;
use WishKnish\KnishIO\Client\Response\ValidationResult;
use WishKnish\KnishIO\Client\Response\ResponseError;

/**
 * Socket configuration for real-time features
 */
readonly class SocketConfig
{
    public function __construct(
        public ?string $socketUri = null,
        public ?string $appKey = null
    ) {}
    
    public static function fromArray(array $config): self
    {
        return new self(
            socketUri: $config['socketUri'] ?? $config['socket_uri'] ?? null,
            appKey: $config['appKey'] ?? $config['app_key'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'socketUri' => $this->socketUri,
            'appKey' => $this->appKey
        ];
    }
}

/**
 * Core client configuration (mirrors JavaScript object pattern)
 */
readonly class ClientConfig
{
    public function __construct(
        public string $uri,
        public ?string $cellSlug = null,
        public ?string $client = null,
        public ?SocketConfig $socket = null,
        public int $serverSdkVersion = 3,
        public bool $logging = false
    ) {
        $this->validate();
    }
    
    /**
     * Validate configuration with enhanced error messages
     */
    public function validate(): void
    {
        $errors = [];
        
        if (empty($this->uri)) {
            $errors[] = 'URI cannot be empty';
        }
        
        if ($this->serverSdkVersion < 1) {
            $errors[] = 'Server SDK version must be positive';
        }
        
        // Validate URI format
        if (!filter_var($this->uri, FILTER_VALIDATE_URL) && !str_starts_with($this->uri, 'localhost')) {
            $errors[] = 'Invalid URI format';
        }
        
        if (!empty($errors)) {
            throw new InvalidConfigurationException('ClientConfig validation failed: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Create from JavaScript-style associative array
     */
    public static function fromArray(array $config): self
    {
        return new self(
            uri: $config['uri'] ?? throw new InvalidConfigurationException('URI is required'),
            cellSlug: $config['cellSlug'] ?? $config['cell_slug'] ?? null,
            client: $config['client'] ?? null,
            socket: isset($config['socket']) ? SocketConfig::fromArray($config['socket']) : null,
            serverSdkVersion: $config['serverSdkVersion'] ?? $config['server_sdk_version'] ?? 3,
            logging: $config['logging'] ?? false
        );
    }
    
    /**
     * Convert to array format for compatibility
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'cellSlug' => $this->cellSlug,
            'client' => $this->client,
            'socket' => $this->socket?->toArray(),
            'serverSdkVersion' => $this->serverSdkVersion,
            'logging' => $this->logging
        ];
    }
    
    /**
     * Builder pattern methods for fluent configuration
     */
    public function withCellSlug(string $cellSlug): self
    {
        return new self($this->uri, $cellSlug, $this->client, $this->socket, $this->serverSdkVersion, $this->logging);
    }
    
    public function withLogging(bool $logging): self
    {
        return new self($this->uri, $this->cellSlug, $this->client, $this->socket, $this->serverSdkVersion, $logging);
    }
    
    public function withSocket(SocketConfig $socket): self
    {
        return new self($this->uri, $this->cellSlug, $this->client, $socket, $this->serverSdkVersion, $this->logging);
    }
}

/**
 * Authentication configuration
 */
readonly class AuthTokenConfig
{
    public function __construct(
        public ?string $secret = null,
        public ?string $seed = null,
        public ?string $cellSlug = null,
        public bool $encrypt = false
    ) {}
    
    public static function fromArray(array $config): self
    {
        return new self(
            secret: $config['secret'] ?? null,
            seed: $config['seed'] ?? null,
            cellSlug: $config['cellSlug'] ?? $config['cell_slug'] ?? null,
            encrypt: $config['encrypt'] ?? false
        );
    }
    
    public function toArray(): array
    {
        return [
            'secret' => $this->secret,
            'seed' => $this->seed,
            'cellSlug' => $this->cellSlug,
            'encrypt' => $this->encrypt
        ];
    }
}

/**
 * Metadata operation configuration
 */
readonly class MetaConfig
{
    public function __construct(
        public string $metaType,
        public string $metaId,
        public array $meta,
        public ?array $policy = null
    ) {
        $this->validate();
    }
    
    public function validate(): void
    {
        $errors = [];
        
        if (empty($this->metaType)) {
            $errors[] = 'MetaType cannot be empty';
        }
        
        if (empty($this->metaId)) {
            $errors[] = 'MetaId cannot be empty';
        }
        
        if (!empty($errors)) {
            throw new InvalidConfigurationException('MetaConfig validation failed: ' . implode(', ', $errors));
        }
    }
    
    public static function fromArray(array $config): self
    {
        // Handle both JavaScript object format and PHP structured format
        $meta = self::normalizeMetadata($config['meta'] ?? []);
        
        return new self(
            metaType: $config['metaType'] ?? throw new InvalidConfigurationException('metaType is required'),
            metaId: $config['metaId'] ?? throw new InvalidConfigurationException('metaId is required'),
            meta: $meta,
            policy: $config['policy'] ?? null
        );
    }
    
    /**
     * Convert JavaScript object format to PHP structured format
     */
    private static function normalizeMetadata(array $meta): array
    {
        $normalized = [];
        
        foreach ($meta as $key => $value) {
            if (is_array($value) && isset($value['key'], $value['value'])) {
                // Already in structured format [['key' => 'name', 'value' => 'John']]
                $normalized[] = $value;
            } else {
                // Convert from JS object format ['name' => 'John'] to structured format
                $normalized[] = ['key' => (string)$key, 'value' => (string)$value];
            }
        }
        
        return $normalized;
    }
    
    public function toArray(): array
    {
        return [
            'metaType' => $this->metaType,
            'metaId' => $this->metaId,
            'meta' => $this->meta,
            'policy' => $this->policy
        ];
    }
}

/**
 * Token creation configuration
 */
readonly class TokenConfig
{
    public function __construct(
        public string $token,
        public ?int $amount = null,
        public array $meta = [],
        public ?string $batchId = null,
        public array $units = []
    ) {
        $this->validate();
    }
    
    public function validate(): void
    {
        $errors = [];
        
        if (empty($this->token)) {
            $errors[] = 'Token identifier cannot be empty';
        }
        
        if ($this->amount !== null && $this->amount < 0) {
            $errors[] = 'Token amount cannot be negative';
        }
        
        if (!empty($errors)) {
            throw new InvalidConfigurationException('TokenConfig validation failed: ' . implode(', ', $errors));
        }
    }
    
    public static function fromArray(array $config): self
    {
        $meta = MetaConfig::normalizeMetadata($config['meta'] ?? []);
        
        return new self(
            token: $config['token'] ?? throw new InvalidConfigurationException('token is required'),
            amount: $config['amount'] ?? null,
            meta: $meta,
            batchId: $config['batchId'] ?? null,
            units: $config['units'] ?? []
        );
    }
    
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'amount' => $this->amount,
            'meta' => $this->meta,
            'batchId' => $this->batchId,
            'units' => $this->units
        ];
    }
}

/**
 * Transfer configuration
 */
readonly class TransferConfig
{
    public function __construct(
        public string $bundleHash,
        public string $token,
        public ?int $amount = null,
        public array $units = [],
        public ?string $batchId = null,
        public ?object $sourceWallet = null
    ) {
        $this->validate();
    }
    
    public function validate(): void
    {
        $errors = [];
        
        if (empty($this->bundleHash)) {
            $errors[] = 'Bundle hash cannot be empty';
        }
        
        if (empty($this->token)) {
            $errors[] = 'Token cannot be empty';
        }
        
        if (($this->amount === null || $this->amount <= 0) && empty($this->units)) {
            $errors[] = 'Either positive amount or units must be provided';
        }
        
        if (!empty($errors)) {
            throw new InvalidConfigurationException('TransferConfig validation failed: ' . implode(', ', $errors));
        }
    }
    
    public static function fromArray(array $config): self
    {
        return new self(
            bundleHash: $config['bundleHash'] ?? throw new InvalidConfigurationException('bundleHash is required'),
            token: $config['token'] ?? throw new InvalidConfigurationException('token is required'),
            amount: $config['amount'] ?? null,
            units: $config['units'] ?? [],
            batchId: $config['batchId'] ?? null,
            sourceWallet: $config['sourceWallet'] ?? null
        );
    }
}

/**
 * Balance query configuration
 */
readonly class QueryBalanceConfig
{
    public function __construct(
        public string $token,
        public ?string $bundle = null,
        public string $type = 'regular'
    ) {}
    
    public static function fromArray(array $config): self
    {
        return new self(
            token: $config['token'] ?? throw new InvalidConfigurationException('token is required'),
            bundle: $config['bundle'] ?? null,
            type: $config['type'] ?? 'regular'
        );
    }
}

/**
 * Wallet creation configuration
 */
readonly class WalletConfig
{
    public function __construct(
        public string $token
    ) {
        if (empty($this->token)) {
            throw new InvalidConfigurationException('Token cannot be empty');
        }
    }
    
    public static function fromArray(array $config): self
    {
        return new self(
            token: $config['token'] ?? throw new InvalidConfigurationException('token is required')
        );
    }
}

/**
 * Configuration factory for creating standardized configurations
 */
class ConfigFactory
{
    public static function createClientConfig(
        string $uri,
        ?string $cellSlug = null,
        bool $logging = false,
        int $serverSdkVersion = 3
    ): ClientConfig {
        return new ClientConfig($uri, $cellSlug, null, null, $serverSdkVersion, $logging);
    }
    
    public static function createMetaConfig(
        string $metaType,
        string $metaId,
        array $meta,
        ?array $policy = null
    ): MetaConfig {
        return new MetaConfig($metaType, $metaId, $meta, $policy);
    }
    
    public static function createTokenConfig(
        string $token,
        ?int $amount = null,
        array $meta = []
    ): TokenConfig {
        return new TokenConfig($token, $amount, $meta);
    }
}

/**
 * Configuration utilities for validation and conversion
 */
class ConfigUtils
{
    /**
     * Convert JavaScript camelCase keys to PHP-appropriate format
     */
    public static function normalizeCamelCase(array $config): array
    {
        $camelCaseMapping = [
            'cellSlug' => 'cellSlug',
            'metaType' => 'metaType',
            'metaId' => 'metaId',
            'bundleHash' => 'bundleHash',
            'batchId' => 'batchId',
            'sourceWallet' => 'sourceWallet',
            'serverSdkVersion' => 'serverSdkVersion',
            'queryArgs' => 'queryArgs',
            'countBy' => 'countBy'
        ];
        
        $normalized = $config;
        
        // Handle snake_case to camelCase conversion for cross-platform compatibility
        foreach ($camelCaseMapping as $camelCase => $phpCase) {
            $snakeCase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $camelCase));
            
            if (isset($config[$snakeCase]) && !isset($config[$camelCase])) {
                $normalized[$phpCase] = $config[$snakeCase];
                unset($normalized[$snakeCase]);
            }
        }
        
        return $normalized;
    }
    
    /**
     * Validate any configuration array using appropriate config class
     */
    public static function validateConfig(array $config, string $configType): ValidationResult
    {
        try {
            match ($configType) {
                'client' => ClientConfig::fromArray($config),
                'auth' => AuthTokenConfig::fromArray($config),
                'meta' => MetaConfig::fromArray($config),
                'token' => TokenConfig::fromArray($config),
                'transfer' => TransferConfig::fromArray($config),
                'balance' => QueryBalanceConfig::fromArray($config),
                'wallet' => WalletConfig::fromArray($config),
                default => throw new InvalidConfigurationException("Unknown config type: $configType")
            };
            
            return ValidationResult::success($config);
        } catch (InvalidConfigurationException $e) {
            return ValidationResult::failure($e->getMessage(), $config);
        }
    }
    
    /**
     * Convert configuration to JSON for cross-platform compatibility
     */
    public static function configToJson(object $config): string
    {
        return json_encode($config->toArray(), JSON_THROW_ON_ERROR);
    }
    
    /**
     * Create configuration from JSON (JavaScript compatibility)
     */
    public static function configFromJson(string $json, string $configType): object
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        
        return match ($configType) {
            'client' => ClientConfig::fromArray($data),
            'auth' => AuthTokenConfig::fromArray($data),
            'meta' => MetaConfig::fromArray($data),
            'token' => TokenConfig::fromArray($data),
            'transfer' => TransferConfig::fromArray($data),
            'balance' => QueryBalanceConfig::fromArray($data),
            'wallet' => WalletConfig::fromArray($data),
            default => throw new InvalidConfigurationException("Unknown config type: $configType")
        };
    }
}

/**
 * Enhanced configuration validation with detailed error reporting
 */
class ConfigValidator
{
    /**
     * Validate client configuration with detailed error reporting
     */
    public static function validateClientConfig(array $config): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        // Required field validation
        if (!isset($config['uri']) || empty($config['uri'])) {
            $errors[] = 'URI is required and cannot be empty';
        }
        
        // Optional field validation with warnings
        if (isset($config['serverSdkVersion']) && $config['serverSdkVersion'] < 3) {
            $warnings[] = 'Server SDK version below 3 may not support all features';
        }
        
        if (isset($config['logging']) && $config['logging'] === true && !isset($config['cellSlug'])) {
            $warnings[] = 'Logging enabled without cellSlug may reduce debugging effectiveness';
        }
        
        if (empty($errors)) {
            try {
                $validConfig = ClientConfig::fromArray($config);
                return ValidationResult::success($validConfig->toArray())->withWarnings($warnings);
            } catch (InvalidConfigurationException $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        return ValidationResult::failure(
            'ClientConfig validation failed: ' . implode(', ', $errors),
            $config
        )->withWarnings($warnings);
    }
    
    /**
     * Validate metadata configuration with business logic checks
     */
    public static function validateMetaConfig(array $config): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        // Required field validation
        if (!isset($config['metaType']) || empty($config['metaType'])) {
            $errors[] = 'metaType is required and cannot be empty';
        }
        
        if (!isset($config['metaId']) || empty($config['metaId'])) {
            $errors[] = 'metaId is required and cannot be empty';
        }
        
        // Metadata validation
        if (!isset($config['meta']) || !is_array($config['meta'])) {
            $errors[] = 'meta must be provided as an array or object';
        } elseif (empty($config['meta'])) {
            $warnings[] = 'Empty metadata - consider adding descriptive metadata';
        }
        
        // Business logic validation
        if (isset($config['metaType']) && strlen($config['metaType']) > 50) {
            $warnings[] = 'MetaType is quite long - consider using shorter identifiers for better performance';
        }
        
        if (empty($errors)) {
            try {
                $validConfig = MetaConfig::fromArray($config);
                return ValidationResult::success($validConfig->toArray())->withWarnings($warnings);
            } catch (InvalidConfigurationException $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        return ValidationResult::failure(
            'MetaConfig validation failed: ' . implode(', ', $errors),
            $config
        )->withWarnings($warnings);
    }
}