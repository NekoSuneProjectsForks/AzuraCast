<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\Admin\UpdateDetails;
use App\Version;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Throwable;

final class AzuraCastCentral
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    private const string BASE_URL = 'https://central.azuracast.com';

    public function __construct(
        private readonly Version $version,
        private readonly Client $httpClient,
    ) {
    }

    public const string FORK_REPOSITORY = 'NekoSuneProjectsForks/AzuraCast';
    public const string FORK_BRANCH = 'modern';
    private const string GITHUB_API_URL = 'https://api.github.com/repos/' . self::FORK_REPOSITORY;

    /**
     * Check this maintained fork's main branch directly on GitHub.
     *
     * Fork installations intentionally do not use the upstream AzuraCast
     * Central service for update decisions, otherwise a modified installation
     * can be pointed back toward upstream images/releases.
     */
    public function checkForUpdates(): UpdateDetails
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'NekoSune-AzuraCast-Fork',
        ];

        $latestResponse = $this->httpClient->request(
            'GET',
            self::GITHUB_API_URL . '/commits/' . self::FORK_BRANCH,
            [
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::TIMEOUT => 15,
                RequestOptions::HEADERS => $headers,
            ]
        );

        $latestData = json_decode(
            $latestResponse->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $latestCommit = $latestData['sha'] ?? null;
        if (!is_string($latestCommit) || '' === $latestCommit) {
            throw new RuntimeException('GitHub did not return the latest fork commit.');
        }

        $currentCommit = $this->version->getCommitHash();
        $needsUpdate = null !== $currentCommit && $currentCommit !== $latestCommit;
        $commitsBehind = $needsUpdate ? 1 : 0;

        if ($needsUpdate) {
            try {
                $compareResponse = $this->httpClient->request(
                    'GET',
                    self::GITHUB_API_URL . '/compare/' . rawurlencode($currentCommit) . '...' . self::FORK_BRANCH,
                    [
                        RequestOptions::HTTP_ERRORS => true,
                        RequestOptions::TIMEOUT => 15,
                        RequestOptions::HEADERS => $headers,
                    ]
                );

                $compareData = json_decode(
                    $compareResponse->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $commitsBehind = max(
                    1,
                    (int)($compareData['ahead_by'] ?? $compareData['total_commits'] ?? 1)
                );
            } catch (Throwable $e) {
                $this->logger->debug(
                    'Could not calculate exact fork update distance.',
                    ['exception' => $e]
                );
            }
        }

        return new UpdateDetails(
            current_release: null !== $currentCommit
                ? substr($currentCommit, 0, 7)
                : 'unknown',
            latest_release: substr($latestCommit, 0, 7),
            needs_rolling_update: $needsUpdate,
            needs_release_update: $needsUpdate,
            rolling_updates_available: $commitsBehind,
            can_switch_to_stable: false
        );
    }

    public function getUniqueIdentifier(): string
    {
        return $this->readSettings()->app_unique_identifier;
    }

    /**
     * Ping the AzuraCast Central server to retrieve this installation's likely public-facing IP.
     *
     * @param bool $cached
     */
    public function getIp(bool $cached = true): ?string
    {
        $settings = $this->readSettings();
        $ip = ($cached)
            ? $settings->external_ip
            : null;

        if (empty($ip)) {
            try {
                $response = $this->httpClient->request(
                    'GET',
                    self::BASE_URL . '/ip'
                );

                $bodyRaw = $response->getBody()->getContents();
                $body = json_decode($bodyRaw, true, 512, JSON_THROW_ON_ERROR);

                $ip = $body['ip'] ?? null;
            } catch (Exception $e) {
                $this->logger->error('Could not fetch remote IP: ' . $e->getMessage());
                $ip = null;
            }

            if (!empty($ip) && $cached) {
                $settings->external_ip = $ip;
                $this->writeSettings($settings);
            }
        }

        return $ip;
    }
}
