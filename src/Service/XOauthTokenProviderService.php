<?php

declare(strict_types=1);

namespace Dot\MailOutlook\Service;

use Dot\DependencyInjection\Attribute\Inject;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

use function bin2hex;
use function chmod;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function dirname;
use function fclose;
use function file_get_contents;
use function fopen;
use function fwrite;
use function http_build_query;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_string;
use function is_writable;
use function json_decode;
use function json_encode;
use function random_bytes;
use function rename;
use function sprintf;
use function time;
use function unlink;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const JSON_THROW_ON_ERROR;

class XOauthTokenProviderService
{
    /** A cached token is used only if it is valid for at least this many more seconds. */
    protected const EXPIRY_MARGIN = 60;

    private const REQUIRED_KEYS = [
        'tokenCacheFile',
        'access_code_url',
        'client_id',
        'client_secret',
        'scope',
        'grant_type',
    ];

    /**
     * The configuration comes from the application's config files, so its values are checked at runtime.
     * Required keys: tokenCacheFile, access_code_url, client_id, client_secret, scope and grant_type.
     * Each one must be a non-empty string. The optional tenant key is not read.
     *
     * @param array<array-key, mixed> $config
     * @throws InvalidArgumentException
     */
    #[Inject('config.xoauth2_outlook')]
    public function __construct(protected array $config)
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if (! isset($config[$key]) || ! is_string($config[$key]) || $config[$key] === '') {
                throw new InvalidArgumentException(
                    sprintf('The configuration value "xoauth2_outlook.%s" must be a non-empty string.', $key)
                );
            }
        }
    }

    /**
     * @throws RuntimeException
     */
    public function getToken(): string
    {
        return $this->readCachedToken() ?? $this->fetchToken();
    }

    /**
     * Returns null when the cache file is missing, unreadable, invalid or expired.
     */
    protected function readCachedToken(): ?string
    {
        $file = $this->configValue('tokenCacheFile');
        if (! is_file($file)) {
            return null;
        }

        $rawJson = file_get_contents($file);
        if (! is_string($rawJson) || $rawJson === '') {
            return null;
        }

        try {
            $auth = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (
            ! is_array($auth)
            || ! isset($auth['access_token'], $auth['expires_in'])
            || ! is_string($auth['access_token'])
            || ! is_int($auth['expires_in'])
            || $auth['expires_in'] <= time() + static::EXPIRY_MARGIN
        ) {
            return null;
        }

        return $auth['access_token'];
    }

    /**
     * @throws RuntimeException
     */
    protected function fetchToken(): string
    {
        [$status, $body] = $this->requestToken();

        try {
            $auth = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                sprintf('Microsoft returned a token response that is not JSON (HTTP status %d).', $status),
                0,
                $exception
            );
        }

        if (
            $status !== 200
            || ! is_array($auth)
            || ! isset($auth['access_token'], $auth['expires_in'])
            || ! is_string($auth['access_token'])
            || ! is_int($auth['expires_in'])
        ) {
            throw new RuntimeException(sprintf(
                'Microsoft did not return an access token (HTTP status %d): %s',
                $status,
                $this->describeError($auth)
            ));
        }

        $this->writeCache($auth['access_token'], time() + $auth['expires_in']);

        return $auth['access_token'];
    }

    /**
     * Sends the client credentials request.
     *
     * @return array{int, string} The HTTP status code and the response body.
     * @throws RuntimeException
     */
    protected function requestToken(): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->configValue('access_code_url'),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $this->configValue('client_id'),
                'client_secret' => $this->configValue('client_secret'),
                'scope'         => $this->configValue('scope'),
                'grant_type'    => $this->configValue('grant_type'),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($curl);
        if (! is_string($response)) {
            throw new RuntimeException(
                sprintf('Failed to fetch the OAuth token from Microsoft: %s', curl_error($curl))
            );
        }

        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        return [is_int($status) ? $status : 0, $response];
    }

    /**
     * Writes the token to a new file with mode 0600, then renames it over the cache file.
     *
     * @throws RuntimeException
     */
    protected function writeCache(string $token, int $expiresAt): void
    {
        $file      = $this->configValue('tokenCacheFile');
        $directory = dirname($file);
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException(
                sprintf('The token cache directory "%s" does not exist or is not writable.', $directory)
            );
        }

        $temporaryFile = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $handle        = fopen($temporaryFile, 'xb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Cannot create the token cache file "%s".', $temporaryFile));
        }

        chmod($temporaryFile, 0600);
        $written = fwrite($handle, json_encode(
            ['access_token' => $token, 'expires_in' => $expiresAt],
            JSON_THROW_ON_ERROR
        ));
        fclose($handle);

        if ($written === false || ! rename($temporaryFile, $file)) {
            unlink($temporaryFile);
            throw new RuntimeException(sprintf('Cannot write the token cache file "%s".', $file));
        }
    }

    /**
     * Returns a configuration value that the constructor validated.
     *
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    protected function configValue(string $key): string
    {
        $value = $this->config[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException(
                sprintf('The configuration value "xoauth2_outlook.%s" must be a non-empty string.', $key)
            );
        }

        return $value;
    }

    protected function describeError(mixed $auth): string
    {
        if (is_array($auth) && isset($auth['error_description']) && is_string($auth['error_description'])) {
            return $auth['error_description'];
        }

        if (is_array($auth) && isset($auth['error']) && is_string($auth['error'])) {
            return $auth['error'];
        }

        return 'the response has no error description';
    }
}
