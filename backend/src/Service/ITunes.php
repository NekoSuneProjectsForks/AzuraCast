<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Interfaces\SongInterface;
use App\Entity\StationMedia;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class ITunes
{
    public const string API_BASE_URL = 'https://itunes.apple.com/';

    public function __construct(
        private readonly Client $httpClient,
    ) {
    }

    public function findArtwork(SongInterface $song): ?string
    {
        $results = [];

        if ($song instanceof StationMedia && !empty($song->upc)) {
            $results = $this->request('lookup', [
                'upc' => $song->upc,
                'entity' => 'song',
                'limit' => 10,
            ]);
        }

        if (empty($results)) {
            $term = trim(implode(' ', array_filter([
                $song->artist,
                $song->album,
                $song->title,
            ])));

            if ($term === '') {
                return null;
            }

            $results = $this->request('search', [
                'term' => $term,
                'media' => 'music',
                'entity' => 'song',
                'limit' => 10,
            ]);
        }

        foreach ($results as $result) {
            $url = $result['artworkUrl100'] ?? null;
            if (!is_string($url) || $url === '') {
                continue;
            }

            // Apple artwork URLs accept larger square sizes by replacing the
            // final size component. Fall back to the supplied URL if the
            // expected pattern is absent.
            return preg_replace(
                '/\/100x100[^\/]*\.jpg$/i',
                '/1200x1200bb.jpg',
                $url
            ) ?: $url;
        }

        return null;
    }

    private function request(string $endpoint, array $query): array
    {
        $response = $this->httpClient->request(
            'GET',
            self::API_BASE_URL . $endpoint,
            [
                RequestOptions::TIMEOUT => 7,
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::STABLE_VERSION,
                    'Accept' => 'application/json',
                ],
                RequestOptions::QUERY => $query,
            ]
        );

        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        return is_array($data['results'] ?? null) ? $data['results'] : [];
    }
}
