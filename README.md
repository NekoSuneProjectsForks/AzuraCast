# NekoSune AzuraCast Fork

[![Build, Test and Publish](https://github.com/NekoSuneProjectsForks/AzuraCast/actions/workflows/default.yml/badge.svg)](https://github.com/NekoSuneProjectsForks/AzuraCast/actions/workflows/default.yml)
[![AGPL-3.0 License](https://img.shields.io/github/license/NekoSuneProjectsForks/AzuraCast.svg)](LICENSE.md)
[![GHCR](https://img.shields.io/badge/GHCR-ghcr.io%2Fnekosuneprojectsforks%2Fazuracast-blue)](https://github.com/NekoSuneProjectsForks/AzuraCast/pkgs/container/azuracast)

> **Community-maintained modified fork of AzuraCast.**
>
> Maintained for the NekoSune Community with additional fixes, compatibility work, radio features and deployment improvements that are intentionally developed outside the official AzuraCast release stream.
>
> **Loved by the NekoSune Community. 💚**

This repository is based on the upstream [AzuraCast](https://github.com/AzuraCast/AzuraCast) project and remains licensed under the **GNU Affero General Public License v3.0**.

It is **not the official AzuraCast repository** and is not maintained by the official AzuraCast team. Upstream deserves full credit for the original project, architecture and the large majority of the codebase.

## Why This Fork Exists

The NekoSune fork exists to maintain a practical radio stack for our own stations and community while being able to ship fixes and features independently from upstream release decisions.

This fork especially focuses on:

- bugs and regressions encountered in real station deployments;
- Icecast2 and Shoutcast2 source/DJ compatibility;
- WebDJ reliability;
- AutoDJ and jingle behaviour;
- reverse proxy and Cloudflare Tunnel deployments;
- public Icecast/Shoutcast directory registration;
- richer media metadata and automatic album-art matching;
- API-first station/media/playlist management;
- Docker and self-hosting workflows.

Some fixes in this fork target issues or behaviour that members of the NekoSune Community report having remained broken, incomplete or unresolved in upstream deployments for **months or, in some cases, years**. That statement describes the experience and maintenance goals of this fork; it is not a claim that upstream maintainers are inactive or obligated to implement the same solutions.

## Fork Update Policy

The maintained release channel for this fork is:

```text
NekoSuneProjectsForks/AzuraCast
branch: modern
```

Fork installations check **this repository's `modern` branch directly on GitHub** for updates instead of using the official AzuraCast Central update service for release decisions.

This prevents a modified installation from being told to move back to an upstream image that does not contain the NekoSune fixes.

The admin update page reports whether the installed commit is behind this fork's `modern` branch.

## Docker Images

GitHub Actions builds the maintained Docker image for **linux/amd64** and **linux/arm64**.

Two image channels are maintained:

```text
ghcr.io/nekosuneprojectsforks/azuracast:main    # default/base fork channel
ghcr.io/nekosuneprojectsforks/azuracast:modern  # NekoSune modified feature channel
```

The changes documented in this README are developed on the **modern** channel. The repository's `main` branch remains the default/base branch.

The Docker workflow publishes branch-specific tags:

```text
ghcr.io/nekosuneprojectsforks/azuracast:main
ghcr.io/nekosuneprojectsforks/azuracast:latest
ghcr.io/nekosuneprojectsforks/azuracast:modern
ghcr.io/nekosuneprojectsforks/azuracast:sha-<commit>
```

The supplied Docker Compose installer and sample configuration use the maintained fork image by default.

The web updater is also fork-owned:

```text
ghcr.io/nekosuneprojectsforks/azuracast-updater:latest
```

It is built from the updater Dockerfile stored in this repository and remains compatible with AzuraCast's existing Watchtower-based web updater API.

### Pull Manually

```bash
docker pull ghcr.io/nekosuneprojectsforks/azuracast:modern
```

### Compose Override

You can explicitly pin the fork channel with:

```env
AZURACAST_VERSION=modern
```

Then update using your normal AzuraCast Docker update flow. The updater container will pull the image configured for the running AzuraCast service, which in this fork is the NekoSune GHCR image by default.

## Fork Features and Fixes

Current fork work includes additions such as:

- improved Icecast2/Shoutcast2 streamer DJ credential compatibility;
- combined Shoutcast DJ credential handling;
- more reliable WebDJ authentication, reconnect and recorder cleanup;
- traditional between-song jingles plus optional AutoDJ jingle overlays;
- timed and scheduled jingle overlays;
- automatic album artwork from local tags and external providers;
- optional Spotify Client Credentials album-art lookup;
- no-key iTunes Search artwork lookup;
- MusicBrainz/Cover Art Archive fallback;
- ISRC plus UPC/EAN/BARCODE metadata detection;
- media API upload directly into selected playlists;
- playlist listing for API-driven upload workflows;
- native Icecast YP directory listing controls;
- Shoutcast directory public host/port overrides;
- Cloudflare Tunnel/reverse-proxy-friendly directory registration without exposing private radio ports;
- a fork-specific updater that follows this repository's `modern` branch;
- GitHub Actions based multi-architecture GHCR image publishing.

Features will continue to evolve independently where doing so is useful for NekoSune Community deployments.

## Cloudflare Tunnel / Private Radio Ports

This fork contains additional support for installations where Icecast/Shoutcast radio ports remain private and listeners reach them through a Cloudflare Tunnel or reverse proxy.

For native radio directory registration, use a dedicated public radio hostname, for example:

```text
radio.example.com
```

with the tunnel forwarding directly to the private station frontend:

```text
radio.example.com -> http://127.0.0.1:8010
```

The directory can advertise the public hostname while the original radio port stays closed to the Internet.

## Original AzuraCast

AzuraCast is a full-featured, self-hosted web radio management suite distributed using Docker. For upstream documentation, architecture information and general AzuraCast usage, see:

- [Official AzuraCast repository](https://github.com/AzuraCast/AzuraCast)
- [Official documentation](https://www.azuracast.com/docs/)
- [System requirements](https://www.azuracast.com/docs/getting-started/requirements/)
- [Original installation documentation](https://www.azuracast.com/docs/getting-started/installation/)

When using this fork, remember that fork-specific behaviour can differ from the official documentation.

## Contributing to This Fork

Bug fixes, compatibility improvements and feature contributions suitable for the NekoSune fork are welcome through this repository.

Please make it clear whether a report applies to:

- this NekoSune-maintained fork;
- official/upstream AzuraCast;
- or both.

That distinction helps avoid confusing fork-specific changes with upstream behaviour.

## License and Attribution

This fork preserves the upstream project's **AGPL-3.0** licensing requirements.

AzuraCast and its upstream contributors retain credit for their original work. Fork-specific modifications are maintained separately under this repository.

See [LICENSE.md](LICENSE.md) for the full license.

---

**NekoSune AzuraCast Fork**  
Community-maintained radio infrastructure, fixes and features.  
**Loved by the NekoSune Community. 💚**
