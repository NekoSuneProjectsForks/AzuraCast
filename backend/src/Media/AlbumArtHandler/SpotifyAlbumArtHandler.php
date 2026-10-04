<?php

declare(strict_types=1);

namespace App\Media\AlbumArtHandler;

use App\Entity\Interfaces\SongInterface;
use App\Service\Spotify;

final class SpotifyAlbumArtHandler extends AbstractAlbumArtHandler
{
    public function __construct(
        private readonly Spotify $spotify,
    ) {
    }

    protected function getServiceName(): string
    {
        return 'Spotify';
    }

    protected function isSupported(): bool
    {
        return $this->spotify->isConfigured();
    }

    protected function getAlbumArt(SongInterface $song): ?string
    {
        return $this->spotify->findArtwork($song);
    }
}
