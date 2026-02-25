<?php

declare(strict_types=1);

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;

/**
 * Standard response implementation with PHP 8.1+ features
 * Implements JavaScript SDK compatible interface patterns
 */
abstract class StandardResponse implements StandardResponseInterface
{
    protected function __construct(
        protected readonly bool $successful,
        protected readonly mixed $payloadData = null,
        protected readonly ?string $errorMessage = null,
        protected readonly mixed $rawData = null,
        protected readonly string $operation = 'unknown',
        protected readonly ?float $duration = null
    ) {}
    
    public function success(): bool
    {
        return $this->successful;
    }
    
    public function payload(): mixed
    {
        return $this->payloadData;
    }
    
    public function reason(): ?string
    {
        return $this->errorMessage;
    }
    
    public function data(): mixed
    {
        return $this->rawData;
    }
    
    public function toValidationResult(): ValidationResultInterface
    {
        if ($this->successful && $this->payloadData !== null) {
            return ValidationResult::success($this->payloadData);
        } else {
            return ValidationResult::failure(
                $this->errorMessage ?? 'Unknown error',
                $this->payloadData
            );
        }
    }
    
    public function onSuccess(callable $callback): static
    {
        if ($this->successful && $this->payloadData !== null) {
            try {
                $callback($this->payloadData);
            } catch (\Throwable $e) {
                error_log("StandardResponse::onSuccess callback failed: {$e->getMessage()}");
            }
        }
        return $this;
    }
    
    public function onFailure(callable $callback): static
    {
        if (!$this->successful) {
            try {
                $callback($this->errorMessage ?? 'Unknown error');
            } catch (\Throwable $e) {
                error_log("StandardResponse::onFailure callback failed: {$e->getMessage()}");
            }
        }
        return $this;
    }
    
    public function debug(?string $label = null): static
    {
        $debugPrefix = $label ?: static::class;
        
        if ($this->successful) {
            error_log("[$debugPrefix] Success: " . json_encode([
                'payload' => $this->payloadData,
                'operation' => $this->operation,
                'duration' => $this->duration
            ]));
        } else {
            error_log("[$debugPrefix] Failure: " . json_encode([
                'error' => $this->errorMessage,
                'operation' => $this->operation,
                'rawData' => $this->rawData
            ]));
        }
        
        return $this;
    }
    
    public function then(callable $onSuccess, ?callable $onFailure = null): mixed
    {
        if ($this->successful && $this->payloadData !== null) {
            return $onSuccess($this->payloadData);
        } else {
            if ($onFailure !== null) {
                return $onFailure($this->errorMessage ?? 'Unknown error');
            } else {
                throw new InvalidResponseException($this->errorMessage ?? 'Unknown error');
            }
        }
    }
    
    /**
     * Factory methods for consistent response creation
     */
    public static function success(mixed $payload, ?string $operation = null, mixed $rawData = null, ?float $duration = null): static
    {
        return new static(true, $payload, null, $rawData, $operation ?? 'unknown', $duration);
    }
    
    public static function failure(string $errorMessage, ?string $operation = null, mixed $rawData = null, ?float $duration = null): static
    {
        return new static(false, null, $errorMessage, $rawData, $operation ?? 'unknown', $duration);
    }
    
    /**
     * Convert from legacy PHP response format
     */
    public static function fromLegacyResponse(object $legacyResponse, string $operation = 'legacy_conversion'): static
    {
        try {
            $isSuccessful = method_exists($legacyResponse, 'success') && $legacyResponse->success();
            
            if ($isSuccessful) {
                $payload = method_exists($legacyResponse, 'payload') ? $legacyResponse->payload() : null;
                $rawData = method_exists($legacyResponse, 'data') ? $legacyResponse->data() : $legacyResponse;
                return static::success($payload, $operation, $rawData);
            } else {
                $errorMessage = match (true) {
                    method_exists($legacyResponse, 'reason') => $legacyResponse->reason(),
                    method_exists($legacyResponse, 'error') => $legacyResponse->error(),
                    default => 'Unknown error'
                };
                $rawData = method_exists($legacyResponse, 'data') ? $legacyResponse->data() : $legacyResponse;
                return static::failure($errorMessage ?? 'Unknown error', $operation, $rawData);
            }
        } catch (\Throwable $e) {
            return static::failure("Legacy response conversion failed: {$e->getMessage()}", $operation, $legacyResponse);
        }
    }
    
    /**
     * Enhanced PHP features with match expression
     */
    public function handle(): mixed
    {
        return match ($this->successful) {
            true => $this->payloadData,
            false => throw new InvalidResponseException($this->errorMessage ?? 'Unknown error')
        };
    }
    
    /**
     * Map operation for functional programming
     */
    public function map(callable $mapper): static
    {
        if ($this->successful && $this->payloadData !== null) {
            try {
                $mappedPayload = $mapper($this->payloadData);
                return static::success($mappedPayload, $this->operation . '_mapped', $this->rawData);
            } catch (\Throwable $e) {
                return static::failure("Mapping failed: {$e->getMessage()}", $this->operation . '_map_failed', $this->rawData);
            }
        } else {
            return $this;
        }
    }
    
    /**
     * Filter operation for functional programming
     */
    public function filter(callable $predicate): static
    {
        if ($this->successful && $this->payloadData !== null) {
            try {
                if ($predicate($this->payloadData)) {
                    return $this;
                } else {
                    return static::failure('Filter predicate failed', $this->operation . '_filter_failed', $this->rawData);
                }
            } catch (\Throwable $e) {
                return static::failure("Filter failed: {$e->getMessage()}", $this->operation . '_filter_error', $this->rawData);
            }
        } else {
            return $this;
        }
    }
    
    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'success' => $this->successful,
            'payload' => $this->payloadData,
            'reason' => $this->errorMessage,
            'data' => $this->rawData,
            'operation' => $this->operation,
            'duration' => $this->duration,
            'timestamp' => date('c')
        ];
    }
    
    /**
     * JSON serialization support
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

/**
 * Concrete response implementations for specific operations
 */
final class MetaResponse extends StandardResponse
{
    public static function create(bool $successful, mixed $payload = null, ?string $errorMessage = null, mixed $rawData = null): self
    {
        return new self($successful, $payload, $errorMessage, $rawData, 'metadata_operation');
    }
}

final class TokenResponse extends StandardResponse  
{
    public static function create(bool $successful, mixed $payload = null, ?string $errorMessage = null, mixed $rawData = null): self
    {
        return new self($successful, $payload, $errorMessage, $rawData, 'token_operation');
    }
}

final class TransferResponse extends StandardResponse
{
    public static function create(bool $successful, mixed $payload = null, ?string $errorMessage = null, mixed $rawData = null): self
    {
        return new self($successful, $payload, $errorMessage, $rawData, 'transfer_operation');
    }
}

final class BalanceResponse extends StandardResponse
{
    public static function create(bool $successful, mixed $payload = null, ?string $errorMessage = null, mixed $rawData = null): self
    {
        return new self($successful, $payload, $errorMessage, $rawData, 'balance_operation');
    }
}

final class WalletResponse extends StandardResponse
{
    public static function create(bool $successful, mixed $payload = null, ?string $errorMessage = null, mixed $rawData = null): self
    {
        return new self($successful, $payload, $errorMessage, $rawData, 'wallet_operation');
    }
}

final class AuthResponse extends StandardResponse
{
    public static function create(bool $successful, mixed $payload = null, ?string $errorMessage = null, mixed $rawData = null): self
    {
        return new self($successful, $payload, $errorMessage, $rawData, 'auth_operation');
    }
}

/**
 * Response factory for creating standardized responses
 */
class ResponseFactory
{
    public static function createSuccessResponse(
        mixed $payload,
        string $operation,
        mixed $rawData = null,
        ?float $duration = null
    ): StandardResponse {
        return new class($payload, $rawData, $operation, $duration) extends StandardResponse {
            public function __construct(mixed $payload, mixed $rawData, string $operation, ?float $duration)
            {
                parent::__construct(true, $payload, null, $rawData, $operation, $duration);
            }
        };
    }
    
    public static function createErrorResponse(
        string $errorMessage,
        string $operation,
        mixed $rawData = null,
        ?float $duration = null
    ): StandardResponse {
        return new class($errorMessage, $rawData, $operation, $duration) extends StandardResponse {
            public function __construct(string $errorMessage, mixed $rawData, string $operation, ?float $duration)
            {
                parent::__construct(false, null, $errorMessage, $rawData, $operation, $duration);
            }
        };
    }
}

/**
 * Response utilities for enhanced operations
 */
class ResponseUtils
{
    /**
     * Combine multiple responses into a single response
     */
    public static function combineResponses(array $responses): StandardResponse
    {
        $successful = array_reduce($responses, fn($carry, $response) => $carry && $response->success(), true);
        
        if ($successful) {
            $payloads = array_map(fn($response) => $response->payload(), $responses);
            return ResponseFactory::createSuccessResponse($payloads, 'combine_responses', $responses);
        } else {
            $errors = array_map(
                fn($response) => $response->success() ? null : $response->reason(),
                array_filter($responses, fn($response) => !$response->success())
            );
            return ResponseFactory::createErrorResponse(
                'Combined operation failed: ' . implode('; ', array_filter($errors)),
                'combine_responses',
                $responses
            );
        }
    }
    
    /**
     * Enhanced error handling with match expressions (PHP 8.0+)
     */
    public static function handleResponse(StandardResponse $response, callable $onSuccess, callable $onFailure): mixed
    {
        return match ($response->success()) {
            true => $onSuccess($response->payload()),
            false => $onFailure($response->reason() ?? 'Unknown error')
        };
    }
    
    /**
     * Create response from validation result
     */
    public static function fromValidationResult(ValidationResultInterface $result, string $operation): StandardResponse
    {
        if ($result->isSuccess()) {
            return ResponseFactory::createSuccessResponse($result->getData(), $operation);
        } else {
            $error = $result->getError();
            return ResponseFactory::createErrorResponse(
                $error['message'] ?? 'Validation failed',
                $operation,
                $error
            );
        }
    }
}