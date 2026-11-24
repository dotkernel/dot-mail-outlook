<?php

declare(strict_types=1);

namespace DotTest\MailOutlook\Fixture;

use Dot\MailOutlook\Service\XOauthTokenProviderService;

/**
 * Returns a fixed HTTP response in place of the request to Microsoft, and counts the requests.
 */
class StubbedTokenProviderService extends XOauthTokenProviderService
{
    public int $requestCount = 0;

    private int $status = 200;

    private string $body = '{"token_type":"Bearer","expires_in":3599,"access_token":"fresh-token"}';

    /**
     * @return array<string, string>
     */
    public static function validConfig(string $tokenCacheFile): array
    {
        return [
            'tokenCacheFile'  => $tokenCacheFile,
            'tenant'          => 'tenant',
            'access_code_url' => 'https://login.microsoftonline.com/tenant/oauth2/v2.0/token',
            'client_id'       => 'client-id',
            'client_secret'   => 'client-secret',
            'scope'           => 'https://outlook.office.com/.default',
            'grant_type'      => 'client_credentials',
        ];
    }

    public function respondWith(int $status, string $body): void
    {
        $this->status = $status;
        $this->body   = $body;
    }

    protected function requestToken(): array
    {
        $this->requestCount++;

        return [$this->status, $this->body];
    }
}
