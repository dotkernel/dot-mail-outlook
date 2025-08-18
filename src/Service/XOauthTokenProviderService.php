<?php

declare(strict_types=1);

namespace Dot\MailOutlook\Service;

use Dot\DependencyInjection\Attribute\Inject;
use JsonException;
use RuntimeException;

use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function http_build_query;
use function is_string;
use function json_decode;
use function json_encode;
use function time;

use const CURLOPT_HEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const JSON_THROW_ON_ERROR;

class XOauthTokenProviderService
{
    /**
     * @phpcs:ignore
     * @param array{
     *     tokenCacheFile: non-empty-string,
     *     tenant: non-empty-string,
     *     access_code_url: non-empty-string,
     *     client_id: non-empty-string,
     *     client_secret: non-empty-string,
     *     scope: non-empty-string,
     *     grant_type: non-empty-string
     * } $config
     */
    #[Inject("config.xoauth2_outlook")]
    public function __construct(protected array $config)
    {
    }

    /**
     * @throws JsonException
     */
    public function getToken(): string
    {
        if (file_exists($this->config["tokenCacheFile"])) {
            $rawJson = file_get_contents($this->config["tokenCacheFile"]);
            if (is_string($rawJson) && $rawJson !== '') {
                $auth = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);

                if (isset($auth['expires_in']) && $auth['expires_in'] > time() + 60) {
                    return $auth['access_token'];
                }
            }
        }

        return $this->fetchToken();
    }

    /**
     * @throws JsonException
     */
    protected function fetchToken(): string
    {
        // POST to {$tenant}/oauth2/v2.0/token
        // also requires "client_credentials" grant
        $data = [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'scope'         => $this->config['scope'],
            'grant_type'    => $this->config['grant_type'],
        ];

        $body = http_build_query($data);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_HEADER         => 'Content-Type: application/x-www-form-urlencoded',
            CURLOPT_URL            => $this->config['access_code_url'],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            throw new RuntimeException('Failed to fetch oauth token from Microsoft');
        }

        $auth = json_decode(
            (string) $response,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (
            $auth === false
            || $auth === null
            || ! isset($auth['access_token'])
            || ! isset($auth['expires_in'])
            || ! isset($auth['ext_expires_in'])
        ) {
            throw new RuntimeException('Invalid response from Microsoft.');
        }

        $auth['expires_in']     += time();
        $auth['ext_expires_in'] += time();

        file_put_contents($this->config['tokenCacheFile'], json_encode($auth));

        return $auth['access_token'];
    }
}
