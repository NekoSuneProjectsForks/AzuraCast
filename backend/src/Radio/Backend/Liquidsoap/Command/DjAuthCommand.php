<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationStreamerRepository;
use App\Entity\Station;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\Types;
use InvalidArgumentException;
use RuntimeException;

final class DjAuthCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
        private readonly Scheduler $scheduler,
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): array {
        if (!$station->enable_streamers) {
            throw new RuntimeException('Streamers are disabled on this station.');
        }

        [$user, $pass] = $this->getCredentials($payload);

        // Allow connections using the exact broadcast source password.
        if ('source' === $user) {
            $sourcePw = $station->frontend_config->source_pw;

            if (!empty($sourcePw) && strcmp($sourcePw, $pass) === 0) {
                return [
                    'allow' => true,
                    'username' => $user,
                ];
            }
        }

        $streamer = $this->streamerRepo->getStreamer($station, $user);

        if (null === $streamer) {
            $inactiveStreamer = $this->streamerRepo->getStreamer($station, $user, false);

            $this->logger->warning(
                null === $inactiveStreamer
                    ? 'DJ authentication rejected: streamer username was not found.'
                    : 'DJ authentication rejected: streamer account is inactive.',
                [
                    'username' => $user,
                ]
            );

            return [
                'allow' => false,
            ];
        }

        if (!$streamer->authenticate($pass)) {
            $this->logger->warning(
                'DJ authentication rejected: password did not match.',
                [
                    'username' => $streamer->streamer_username,
                ]
            );

            return [
                'allow' => false,
                'username' => $streamer->streamer_username,
                'display_name' => $streamer->display_name,
            ];
        }

        if (!$this->scheduler->canStreamerStreamNow($streamer)) {
            $this->logger->warning(
                'DJ authentication rejected: streamer is outside the allowed schedule.',
                [
                    'username' => $streamer->streamer_username,
                ]
            );

            return [
                'allow' => false,
                'username' => $streamer->streamer_username,
                'display_name' => $streamer->display_name,
            ];
        }

        $this->logger->info(
            'DJ authentication accepted.',
            [
                'username' => $streamer->streamer_username,
            ]
        );

        return [
            'allow' => true,
            'username' => $streamer->streamer_username,
            'display_name' => $streamer->display_name,
        ];
    }

    /**
     * @return array{string, string}
     */
    private function getCredentials(array $payload = []): array
    {
        // Liquidsoap currently sends `user` and `password`, but accept common
        // aliases as well so streamer authentication remains compatible with
        // source clients/proxies that normalize these field names differently.
        $user = Types::stringOrNull(
            $payload['user'] ?? $payload['username'] ?? $payload['login'] ?? null,
            true
        );
        $pass = Types::stringOrNull(
            $payload['password'] ?? $payload['pass'] ?? null,
            true
        );

        if (null === $pass) {
            throw new InvalidArgumentException('No credentials provided!');
        }

        // Shoutcast/ICY source connections do not carry a username. In that
        // case Liquidsoap supplies the configured/default username (normally
        // "source") and DJ software sends "username:password" in the password
        // field. Support the common separators used by broadcast clients.
        if (null === $user || 'source' === strtolower($user)) {
            foreach ([':', ',', ';'] as $separator) {
                if (!str_contains($pass, $separator)) {
                    continue;
                }

                [$parsedUser, $parsedPass] = explode($separator, $pass, 2);
                $parsedUser = trim($parsedUser);
                $parsedPass = trim($parsedPass);

                if ('' !== $parsedUser && '' !== $parsedPass) {
                    return [$parsedUser, $parsedPass];
                }
            }
        }

        if (null === $user) {
            throw new InvalidArgumentException('No credentials provided!');
        }

        return [$user, $pass];
    }
}
