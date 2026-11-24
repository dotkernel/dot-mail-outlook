<?php

declare(strict_types=1);

namespace DotTest\MailOutlook\Service;

use Dot\MailOutlook\Service\XOauthTokenProviderService;
use DotTest\MailOutlook\Fixture\StubbedTokenProviderService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function bin2hex;
use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function glob;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function time;
use function unlink;

use const JSON_THROW_ON_ERROR;

#[CoversClass(XOauthTokenProviderService::class)]
class XOauthTokenProviderServiceTest extends TestCase
{
    private string $directory;
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/dot-mail-outlook-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700);
        $this->cacheFile = $this->directory . '/token.json';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function invalidConfigValues(): iterable
    {
        $keys = ['tokenCacheFile', 'access_code_url', 'client_id', 'client_secret', 'scope', 'grant_type'];
        foreach ($keys as $key) {
            yield $key . ' empty'   => [$key, ''];
            yield $key . ' missing' => [$key, null];
            yield $key . ' not string' => [$key, 123];
        }
    }

    #[DataProvider('invalidConfigValues')]
    public function testRejectsInvalidConfigValue(string $key, mixed $value): void
    {
        $config = StubbedTokenProviderService::validConfig($this->cacheFile);
        if ($value === null) {
            unset($config[$key]);
        } else {
            $config[$key] = $value;
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"xoauth2_outlook.' . $key . '"');

        new XOauthTokenProviderService($config);
    }

    public function testAcceptsConfigWithoutTenant(): void
    {
        $config = StubbedTokenProviderService::validConfig($this->cacheFile);
        unset($config['tenant']);

        $provider = new StubbedTokenProviderService($config);

        $this->assertSame('fresh-token', $provider->getToken());
    }

    public function testFetchesTokenAndWritesCache(): void
    {
        $provider = $this->provider();

        $this->assertSame('fresh-token', $provider->getToken());
        $this->assertSame(1, $provider->requestCount);

        $rawJson = file_get_contents($this->cacheFile);
        $this->assertIsString($rawJson);
        $cache = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($cache);
        $this->assertSame('fresh-token', $cache['access_token']);
        $this->assertIsInt($cache['expires_in']);
        $this->assertGreaterThan(time() + 3500, $cache['expires_in']);
    }

    public function testSecondCallUsesCache(): void
    {
        $provider = $this->provider();

        $provider->getToken();
        $provider->getToken();

        $this->assertSame(1, $provider->requestCount);
    }

    public function testReturnsCachedTokenWithoutRequest(): void
    {
        $this->writeCache(['access_token' => 'cached-token', 'expires_in' => time() + 3600]);
        $provider = $this->provider();

        $this->assertSame('cached-token', $provider->getToken());
        $this->assertSame(0, $provider->requestCount);
    }

    public function testReadsCacheFileWrittenByOlderVersion(): void
    {
        $this->writeCache([
            'token_type'     => 'Bearer',
            'expires_in'     => time() + 3600,
            'ext_expires_in' => time() + 3600,
            'access_token'   => 'old-format-token',
        ]);

        $this->assertSame('old-format-token', $this->provider()->getToken());
    }

    public function testFetchesTokenWhenCacheExpiresWithinMargin(): void
    {
        $this->writeCache(['access_token' => 'cached-token', 'expires_in' => time() + 30]);
        $provider = $this->provider();

        $this->assertSame('fresh-token', $provider->getToken());
        $this->assertSame(1, $provider->requestCount);
    }

    public function testFetchesTokenWhenCacheIsCorrupt(): void
    {
        file_put_contents($this->cacheFile, '{broken');
        $provider = $this->provider();

        $this->assertSame('fresh-token', $provider->getToken());
        $this->assertSame(1, $provider->requestCount);
    }

    public function testFetchesTokenWhenCacheHasNoAccessToken(): void
    {
        $this->writeCache(['expires_in' => time() + 3600]);
        $provider = $this->provider();

        $this->assertSame('fresh-token', $provider->getToken());
        $this->assertSame(1, $provider->requestCount);
    }

    public function testFetchesTokenWhenCacheIsEmpty(): void
    {
        file_put_contents($this->cacheFile, '');

        $this->assertSame('fresh-token', $this->provider()->getToken());
    }

    public function testThrowsWithMicrosoftErrorDescription(): void
    {
        $provider = $this->provider();
        $provider->respondWith(
            400,
            '{"error":"invalid_client","error_description":"AADSTS7000215: Invalid client secret provided."}'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('(HTTP status 400): AADSTS7000215: Invalid client secret provided.');

        $provider->getToken();
    }

    public function testThrowsWithMicrosoftErrorCodeWhenDescriptionIsMissing(): void
    {
        $provider = $this->provider();
        $provider->respondWith(401, '{"error":"unauthorized_client"}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('(HTTP status 401): unauthorized_client');

        $provider->getToken();
    }

    public function testThrowsWhenSuccessfulResponseHasNoToken(): void
    {
        $provider = $this->provider();
        $provider->respondWith(200, '{"token_type":"Bearer"}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('(HTTP status 200): the response has no error description');

        $provider->getToken();
    }

    public function testThrowsRuntimeExceptionOnNonJsonBody(): void
    {
        $provider = $this->provider();
        $provider->respondWith(502, '<html>Bad gateway</html>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not JSON (HTTP status 502)');

        $provider->getToken();
    }

    public function testDoesNotWriteCacheOnError(): void
    {
        $provider = $this->provider();
        $provider->respondWith(400, '{"error":"invalid_client"}');

        try {
            $provider->getToken();
        } catch (RuntimeException) {
        }

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    #[RequiresOperatingSystemFamily('Linux')]
    public function testWritesCacheWithMode0600(): void
    {
        $this->provider()->getToken();

        $this->assertSame(0600, fileperms($this->cacheFile) & 0777);
    }

    public function testLeavesNoTemporaryFile(): void
    {
        $this->provider()->getToken();

        $this->assertSame([$this->cacheFile], glob($this->directory . '/*'));
    }

    public function testThrowsWhenCacheDirectoryDoesNotExist(): void
    {
        $provider = new StubbedTokenProviderService(
            StubbedTokenProviderService::validConfig($this->directory . '/missing/token.json')
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist or is not writable');

        $provider->getToken();
    }

    private function provider(): StubbedTokenProviderService
    {
        return new StubbedTokenProviderService(StubbedTokenProviderService::validConfig($this->cacheFile));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeCache(array $data): void
    {
        file_put_contents($this->cacheFile, json_encode($data, JSON_THROW_ON_ERROR));
    }
}
