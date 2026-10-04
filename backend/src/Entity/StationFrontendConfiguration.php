<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Strings;
use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "StationFrontendConfiguration", type: "object")]
final class StationFrontendConfiguration extends AbstractArrayEntity
{
    #[OA\Property]
    public ?string $custom_config = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public string $source_pw;

    #[OA\Property]
    public string $admin_pw;

    #[OA\Property]
    public string $relay_pw;

    #[OA\Property]
    public string $streamer_pw;

    public function ensurePasswordsAreSet(): void
    {
        $autoAssignPasswords = [
            'source_pw',
            'admin_pw',
            'relay_pw',
            'streamer_pw',
        ];

        foreach ($autoAssignPasswords as $autoAssignPassword) {
            if (empty($this->$autoAssignPassword)) {
                $this->$autoAssignPassword = Strings::generatePassword();
            }
        }
    }

    #[OA\Property]
    public ?int $port = null {
        set (int|string|null $value) => Types::intOrNull($value);
    }

    #[OA\Property]
    public ?int $max_listeners = null {
        set (int|string|null $value) => Types::intOrNull($value);
    }

    #[OA\Property]
    public ?string $banned_ips = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $banned_user_agents = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property(
        items: new OA\Items(type: 'string'),
    )]
    public ?array $banned_countries = null;

    #[OA\Property]
    public ?string $allowed_ips = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $sc_license_id = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $sc_user_id = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property(
        description: 'Publish this station to the native Icecast/Shoutcast directory when supported.'
    )]
    public bool $enable_public_directory = false {
        set (bool|int|string|null $value) => Types::bool($value);
    }

    #[OA\Property(
        description: 'Public root URL advertised to radio directories. Intended for NAT, reverse proxies and Cloudflare Tunnel. Use a dedicated hostname that proxies directly to this station frontend.'
    )]
    public ?string $public_directory_url = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property(
        description: 'Icecast YP directory endpoint. Defaults to the Xiph directory.'
    )]
    public ?string $icecast_yp_url = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property(
        description: 'Technical contact e-mail advertised to Icecast YP directories.'
    )]
    public ?string $directory_admin_email = null {
        set => Types::stringOrNull($value, true);
    }

    /**
     * @inheritDoc
     */
    public static function merge(
        ?array $sourceData,
        array|AbstractArrayEntity|null $newData
    ): array|null {
        $arrayEntity = new self((array)$sourceData);
        if ($newData !== null) {
            $arrayEntity->fromArray($newData);
        }

        // Generate defaults if not set.
        $arrayEntity->ensurePasswordsAreSet();

        return $arrayEntity->toArray(true);
    }
}
