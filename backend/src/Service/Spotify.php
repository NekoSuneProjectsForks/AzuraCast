<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\SettingsAwareTrait;
use App\Entity\Interfaces\SongInterface;
use App\Entity\StationMedia;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\SimpleCache\CacheInterface;

final class Spotify
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly Client $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function isConfigured(): bool
    {
        $settings = $this->readSettings();

        return !empty($settings->spotify_client_id)
            && !empty($settings->spotify_client_secret);
    }

    public function findArtwork(SongInterface $song): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $query = [];

        if ($song instanceof StationMedia && !empty($song->isrc)) {
            $query[] = 'isrc:' . $song->isrc;
        } else {
            if (!empty($song->title)) {
                $query[] = 'track:' . $song->title;
            }
            if (!empty($song->artist)) {
                $query[] = 'artist:' . $song->artist;
            }
            if (!empty($song->album)) {
                $query[] = 'album:' . $song->album;
            }
        }

        if (empty($query)) {
            return null;
        }

        $response = $this->httpClient->request(
            'GET',
            'https://api.spotify.com/v1/search',
            [
                RequestOptions::TIMEOUT => 7,
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'User-Agent' => 'AzuraCast ' . Version::STABLE_VERSION,
                    'Accept' => 'application/json',
                ],
                RequestOptions::QUERY => [
                    'q' => implode(' ', $query),
                    'type' => 'track',
                    'limit' => 5,
                ],
            ]
        );

        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        foreach (($data['tracks']['items'] ?? []) as $track) {
            $images = $track['album']['images'] ?? [];
            if (!empty($images[0]['url'])) {
                return (string)$images[0]['url'];
            }
        }

        return null;
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'spotify.client_credentials_token';
        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $settings = $this->readSettings();

        $response = $this->httpClient->request(
            'POST',
            'https://accounts.spotify.com/api/token',
            [
                RequestOptions::TIMEOUT => 7,
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Basic ' . base64_encode(
                        $settings->spotify_client_id . ':' . $settings->spotify_client_secret
                    ),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $token = (string)($data['access_token'] ?? '');

        if ($token === '') {
            throw new \RuntimeException('Spotify did not return an access token.');
        }

        $expiresIn = max(60, (int)($data['expires_in'] ?? 3600) - 60);
        $this->cache->set($cacheKey, $token, $expiresIn);

        return $token;
    }
}
