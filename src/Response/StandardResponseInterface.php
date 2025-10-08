<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
*/

declare(strict_types=1);

namespace WishKnish\KnishIO\Client\Response;

/**
 * Universal response interface matching JavaScript SDK pattern
 */
interface UniversalResponseInterface
{
    public function success(): bool;
    public function payload(): mixed;
    public function reason(): ?string;
    public function data(): mixed;
}

/**
 * Enhanced validation result structure
 */
interface ValidationResultInterface
{
    public function isSuccess(): bool;
    public function getData(): mixed;
    public function getError(): ?array;
    public function getWarnings(): array;
}

/**
 * Standard response interface matching JavaScript SDK pattern
 * Enhanced with modern PHP 8.1+ features
 */
interface StandardResponseInterface extends UniversalResponseInterface
{
    // Enhanced validation result integration
    public function toValidationResult(): ValidationResultInterface;
    
    // Enhanced error handling with method chaining
    public function onSuccess(callable $callback): static;
    public function onFailure(callable $callback): static;
    
    // Enhanced debugging capabilities
    public function debug(?string $label = null): static;
    
    // Promise-like patterns for PHP
    public function then(callable $onSuccess, ?callable $onFailure = null): mixed;
}

/**
 * Enhanced error information structure
 */
readonly class ResponseError
{
    public function __construct(
        public string $message,
        public ?string $code = null,
        public array $details = [],
        public ?string $context = null,
        public string $timestamp = '',
        public ?string $operation = null
    ) {
        if (empty($this->timestamp)) {
            $this->timestamp = date('c');
        }
    }
    
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'details' => $this->details,
            'context' => $this->context,
            'timestamp' => $this->timestamp,
            'operation' => $this->operation
        ];
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'] ?? 'Unknown error',
            code: $data['code'] ?? null,
            details: $data['details'] ?? [],
            context: $data['context'] ?? null,
            timestamp: $data['timestamp'] ?? date('c'),
            operation: $data['operation'] ?? null
        );
    }
}

/**
 * Validation result implementation
 */
readonly class ValidationResult implements ValidationResultInterface
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?ResponseError $error = null,
        public array $warnings = []
    ) {}
    
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    public function getData(): mixed
    {
        return $this->data;
    }
    
    public function getError(): ?array
    {
        return $this->error?->toArray();
    }
    
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    
    public static function success(mixed $data, array $warnings = []): self
    {
        return new self(true, $data, null, $warnings);
    }
    
    public static function failure(string|ResponseError $error, mixed $data = null): self
    {
        $errorObj = $error instanceof ResponseError ? $error : new ResponseError($error);
        return new self(false, $data, $errorObj, []);
    }
}