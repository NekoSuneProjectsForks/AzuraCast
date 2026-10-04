<?php

declare(strict_types=1);

namespace App\Media\AlbumArtHandler;

use App\Entity\Interfaces\SongInterface;
use App\Service\ITunes;

final class ITunesAlbumArtHandler extends AbstractAlbumArtHandler
{
    public function __construct(
        private readonly ITunes $iTunes,
    ) {
    }

    protected function getServiceName(): string
    {
        return 'iTunes';
    }

    protected function getAlbumArt(SongInterface $song): ?string
    {
        return $this->iTunes->findArtwork($song);
    }
}
