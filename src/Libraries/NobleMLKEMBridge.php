<?php

namespace WishKnish\KnishIO\Client\Libraries;

use Exception;

/**
 * Class NobleMLKEMBridge
 *
 * ML-KEM through the JavaScript @noble/post-quantum library, for when the bundled libkcore cannot
 * be loaded (no ext-ffi). One long-lived Node.js helper (bin/noble-mlkem-bridge.js serve) per PHP
 * process answers newline-delimited JSON requests over its stdin/stdout, so a call costs a pipe
 * round trip instead of a Node.js start (up to 1.6.1 every call spawned a new `node`).
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class NobleMLKEMBridge
{
    private const DEFAULT_TIMEOUT_SECONDS = 30.0;
    /** How long stopHelper() lets a helper exit on its own after its stdin closes. */
    private const EXIT_GRACE_SECONDS = 0.5;

    private static float $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS;

    private static ?string $bridgeScript = null;
    private static ?string $nodeCommand = null;

    /** @var resource|null */
    private static $proc = null;
    /** @var resource|null */
    private static $stdin = null;
    /** @var resource|null */
    private static $stdout = null;
    /** Bytes read from the helper past the last complete answer line. */
    private static string $buffer = '';
    private static ?int $ownerPid = null;
    private static int $nextId = 0;
    private static bool $shutdownRegistered = false;

    /**
     * Initialize bridge configuration
     *
     * @throws Exception If Node.js or bridge script not found
     */
    private static function initialize(): void
    {
        if (self::$bridgeScript !== null) {
            return;
        }

        // Find Node.js command
        $nodeCommands = ['node', 'nodejs'];
        foreach ($nodeCommands as $cmd) {
            $output = [];
            $returnCode = 0;
            exec("which $cmd 2>/dev/null", $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                self::$nodeCommand = trim($output[0]);
                break;
            }
        }

        if (self::$nodeCommand === null) {
            throw new Exception(
                'Node.js not found. Please install Node.js to use ML-KEM cryptography. ' .
                'Visit https://nodejs.org/ for installation instructions.'
            );
        }

        // Find bridge script
        $possiblePaths = [
            __DIR__ . '/../../bin/noble-mlkem-bridge.js',
            __DIR__ . '/../../../bin/noble-mlkem-bridge.js',
            dirname(dirname(__DIR__)) . '/bin/noble-mlkem-bridge.js',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                self::$bridgeScript = realpath($path);
                break;
            }
        }

        if (self::$bridgeScript === null) {
            throw new Exception(
                'Noble ML-KEM bridge script not found. Expected at: ' . $possiblePaths[0]
            );
        }

        // Verify @noble/post-quantum is installed
        $packageDir = dirname(self::$bridgeScript);
        $nodeModulesPath = dirname($packageDir) . '/node_modules/@noble/post-quantum';

        if (!is_dir($nodeModulesPath)) {
            throw new Exception(
                '@noble/post-quantum not installed. Please run: npm install @noble/post-quantum ' .
                'in directory: ' . dirname($packageDir)
            );
        }
    }

    /**
     * Send one request to the helper and return its result
     *
     * @param array $args Command name, then its arguments
     * @return array The helper's result object
     * @throws Exception On helper failure, timeout or a reported error
     */
    private static function executeCommand(array $args): array
    {
        self::initialize();
        $request = ['id' => ++self::$nextId, 'cmd' => $args[0], 'args' => array_slice($args, 1)];

        $line = self::roundTrip($request);
        if ($line === null) {
            // The helper died (crash or kill) since the last call: one fresh helper, one retry.
            self::stopHelper();
            $line = self::roundTrip($request);
            if ($line === null) {
                $code = self::exitCode();
                self::stopHelper();
                throw new Exception('Noble ML-KEM helper exited (code ' . ($code ?? 'unknown') . ')');
            }
        }

        $response = json_decode($line, true);
        if (!is_array($response) || ($response['id'] ?? null) !== $request['id']) {
            self::stopHelper();
            throw new Exception('Noble ML-KEM helper answered out of order');
        }
        if (isset($response['error'])) {
            throw new Exception('Noble ML-KEM error: ' . $response['error']);
        }
        if (!isset($response['result']) || !is_array($response['result'])) {
            throw new Exception('Invalid response from Noble ML-KEM helper: no result');
        }

        return $response['result'];
    }

    /**
     * Write one request line and read one answer line. Returns null when the helper is gone (EOF or
     * a failed write), so the caller can retry once on a fresh helper.
     *
     * @throws Exception On timeout
     */
    private static function roundTrip(array $request): ?string
    {
        self::ensureHelper();
        $payload = json_encode($request, JSON_THROW_ON_ERROR) . "\n";
        if (@fwrite(self::$stdin, $payload) !== strlen($payload) || !@fflush(self::$stdin)) {
            return null;
        }

        return self::readLine();
    }

    /**
     * One answer line from the helper within the timeout; null at EOF (the helper is gone).
     *
     * stream_set_timeout() does not apply to proc_open pipes, so the read is non-blocking under a
     * stream_select() deadline. Windows cannot select() on proc_open pipes: there the read blocks
     * without a deadline.
     *
     * @throws Exception On timeout (the helper is stopped first)
     */
    private static function readLine(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $line = fgets(self::$stdout);
            return $line === false ? null : $line;
        }

        $deadline = microtime(true) + self::$timeoutSeconds;
        while (($newline = strpos(self::$buffer, "\n")) === false) {
            $left = $deadline - microtime(true);
            if ($left <= 0) {
                self::stopHelper();
                throw new Exception(sprintf('Noble ML-KEM helper did not answer within %g s', self::$timeoutSeconds));
            }
            $read = [self::$stdout];
            $write = $except = null;
            // false: interrupted by a signal; the deadline still bounds the loop.
            if (!@stream_select($read, $write, $except, (int) $left, (int) (fmod($left, 1.0) * 1e6))) {
                continue;
            }
            $chunk = fread(self::$stdout, 65536);
            if ($chunk === false || $chunk === '') {
                if (feof(self::$stdout)) {
                    return null;
                }
                continue;
            }
            self::$buffer .= $chunk;
        }

        $line = substr(self::$buffer, 0, $newline + 1);
        self::$buffer = substr(self::$buffer, $newline + 1);
        return $line;
    }

    /**
     * Start the helper unless this process already owns a running one. A forked child inherits the
     * parent's pipes but not its helper: it drops them without waiting on the parent's process.
     *
     * @throws Exception If proc_open is unavailable or the helper cannot be started
     */
    private static function ensureHelper(): void
    {
        if (self::$proc !== null && self::$ownerPid === getmypid()) {
            return;
        }
        if (self::$proc !== null) {
            self::stopHelper();
        }
        if (!function_exists('proc_open')) {
            throw new Exception('proc_open is disabled; the Noble ML-KEM helper needs it (or install ext-ffi to use the bundled libkcore)');
        }

        $null = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        $proc = proc_open(
            [self::$nodeCommand, self::$bridgeScript, 'serve'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', $null, 'w']],
            $pipes
        );
        if (!is_resource($proc)) {
            throw new Exception('Could not start the Noble ML-KEM helper: ' . self::$nodeCommand . ' ' . self::$bridgeScript . ' serve');
        }

        self::$proc = $proc;
        self::$stdin = $pipes[0];
        self::$stdout = $pipes[1];
        self::$buffer = '';
        if (PHP_OS_FAMILY !== 'Windows') {
            stream_set_blocking(self::$stdout, false);
        }
        self::$ownerPid = getmypid();
        if (!self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'stopHelper']);
            self::$shutdownRegistered = true;
        }
    }

    private static function exitCode(): ?int
    {
        if (self::$proc === null || self::$ownerPid !== getmypid()) {
            return null;
        }
        $status = proc_get_status(self::$proc);
        return $status['running'] ? null : $status['exitcode'];
    }

    /**
     * Stop this process's helper: closing its stdin ends a healthy one; one that has not exited
     * after EXIT_GRACE_SECONDS (wedged) is killed, so this never blocks. In a forked child only the
     * inherited pipes are closed; the parent's helper is left running for the parent.
     *
     * @internal also the shutdown hook
     */
    public static function stopHelper(): void
    {
        if (self::$proc === null) {
            return;
        }
        $owned = self::$ownerPid === getmypid();
        if (is_resource(self::$stdin)) {
            @fclose(self::$stdin);
        }
        if (is_resource(self::$stdout)) {
            @fclose(self::$stdout);
        }
        if ($owned) {
            $deadline = microtime(true) + self::EXIT_GRACE_SECONDS;
            while (proc_get_status(self::$proc)['running'] && microtime(true) < $deadline) {
                usleep(10000);
            }
            if (proc_get_status(self::$proc)['running']) {
                proc_terminate(self::$proc, 9);
            }
            proc_close(self::$proc);
        }
        self::$proc = self::$stdin = self::$stdout = null;
        self::$buffer = '';
        self::$ownerPid = null;
    }

    /**
     * Run a different helper script with a different answer timeout (null: the packaged script
     * and the 30 s default). Stops the current helper.
     *
     * @internal tests only
     * @throws Exception If Node.js cannot be found
     */
    public static function overrideHelperForTests(?string $script, ?float $timeoutSeconds = null): void
    {
        self::stopHelper();
        self::$bridgeScript = null;
        self::initialize();
        if ($script !== null) {
            self::$bridgeScript = $script;
        }
        self::$timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_TIMEOUT_SECONDS;
    }

    /**
     * PID of this process's running helper, or null when none is running.
     *
     * @internal tests only
     */
    public static function helperPid(): ?int
    {
        if (self::$proc === null || self::$ownerPid !== getmypid()) {
            return null;
        }
        $status = proc_get_status(self::$proc);
        return $status['running'] ? $status['pid'] : null;
    }

    /**
     * Generate an ML-KEM key pair from seed (deterministic) at the requested parameter set
     *
     * Uses JavaScript @noble/post-quantum library to ensure exact compatibility
     * with JavaScript SDK implementation.
     *
     * @param string $seedHex 128 hex characters (64 bytes)
     * @return array ['publicKey' => base64, 'secretKey' => base64]
     * @throws Exception
     */
    public static function generateMLKEMKeyPairFromSeed(string $seedHex, int $paramSet = 1024): array
    {
        if (strlen($seedHex) !== 128) {
            throw new Exception('Seed must be exactly 128 hex characters');
        }

        $result = self::executeCommand(['keygen', $seedHex, (string)$paramSet]);

        if (!isset($result['publicKey']) || !isset($result['secretKey'])) {
            throw new Exception('Invalid response from Noble ML-KEM bridge: missing keys');
        }

        return [
            'publicKey' => $result['publicKey'],
            'secretKey' => $result['secretKey']
        ];
    }

    /**
     * Encapsulate - generate shared secret and ciphertext from public key
     *
     * @param string $publicKeyBase64 Base64-encoded public key
     * @return array ['ciphertext' => base64, 'sharedSecret' => base64]
     * @throws Exception
     */
    public static function encapsulate(string $publicKeyBase64): array
    {
        $result = self::executeCommand(['encaps', $publicKeyBase64]);

        if (!isset($result['ciphertext']) || !isset($result['sharedSecret'])) {
            throw new Exception('Invalid response from Noble ML-KEM bridge: missing encapsulation data');
        }

        return [
            'ciphertext' => $result['ciphertext'],
            'sharedSecret' => $result['sharedSecret']
        ];
    }

    /**
     * Decapsulate - recover shared secret from ciphertext using secret key
     *
     * @param string $ciphertextBase64 Base64-encoded ciphertext
     * @param string $secretKeyBase64 Base64-encoded secret key
     * @return array ['sharedSecret' => base64]
     * @throws Exception
     */
    public static function decapsulate(string $ciphertextBase64, string $secretKeyBase64): array
    {
        $result = self::executeCommand(['decaps', $ciphertextBase64, $secretKeyBase64]);

        if (!isset($result['sharedSecret'])) {
            throw new Exception('Invalid response from Noble ML-KEM bridge: missing shared secret');
        }

        return [
            'sharedSecret' => $result['sharedSecret']
        ];
    }

    /**
     * Check if Noble ML-KEM bridge is available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        try {
            self::initialize();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get bridge status and version information
     *
     * @return array Status information
     */
    public static function getStatus(): array
    {
        try {
            self::initialize();

            // Get Node.js version
            exec(self::$nodeCommand . ' --version 2>&1', $nodeVersion, $returnCode);

            return [
                'available' => true,
                'nodeCommand' => self::$nodeCommand,
                'nodeVersion' => $returnCode === 0 ? trim($nodeVersion[0] ?? 'unknown') : 'unknown',
                'bridgeScript' => self::$bridgeScript,
                'method' => 'Persistent Node.js helper to @noble/post-quantum (one per PHP process)'
            ];
        } catch (Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}