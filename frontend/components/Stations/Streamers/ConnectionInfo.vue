<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_connection_info"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_connection_info"
                class="card-title"
            >
                {{ $gettext('Connection Information') }}
            </h2>
        </div>
        <div class="card-body">
            <h3 class="card-subtitle mt-0">
                {{ $gettext('Icecast Clients (Mixxx, BUTT, Rocket Broadcaster, etc.)') }}
            </h3>
            <p class="card-text">
                {{
                    $gettext('These applications connect directly to the Liquidsoap DJ/streamer port. This is different from the public listener port and different from the Web DJ WebSocket connection.')
                }}
            </p>
            <dl>
                <dt class="mb-1">
                    {{ $gettext('Server:') }}
                </dt>
                <dd>
                    <code>{{ connectionServerUrl }}</code>
                </dd>
                <dd v-if="connectionIp">
                    {{ $gettext('For a direct/LAN connection, you may connect to the server IP address:') }}
                    <code>{{ connectionIp }}</code>
                </dd>

                <dt class="mb-1">
                    {{ $gettext('Port:') }}
                </dt>
                <dd><code>{{ connectionStreamPort }}</code></dd>

                <dt class="mb-1">
                    {{ $gettext('Mount Name:') }}
                </dt>
                <dd><code>{{ connectionDjMountPoint }}</code></dd>

                <dt class="mb-1">
                    {{ $gettext('Connection Type:') }}
                </dt>
                <dd>
                    {{ $gettext('Use Icecast 2 / Icecast source mode. In Mixxx, set the Login field to your DJ username and Password to your DJ password.') }}
                </dd>

                <dt class="mb-1">
                    {{ $gettext('Username:') }}
                </dt>
                <dd><code>dj_username</code></dd>

                <dt class="mb-1">
                    {{ $gettext('Password:') }}
                </dt>
                <dd><code>dj_password</code></dd>
            </dl>

            <div class="alert alert-info mb-0">
                {{
                    $gettext('If the DJ port is not opened on your router/firewall, native Icecast source apps cannot use the server IP and DJ port from outside your network. With Cloudflare Tunnel, use a dedicated DJ-source hostname routed directly to this station DJ port, and configure the broadcasting app for that hostname/HTTPS endpoint if the app supports TLS. The normal AzuraCast website tunnel only proxies Web DJ; it does not automatically expose this raw DJ port.')
                }}
            </div>
        </div>
        <div class="card-body">
            <h3 class="card-subtitle mt-0">
                {{ $gettext('Shoutcast Clients') }}
            </h3>
            <dl>
                <dt class="mb-1">
                    {{ $gettext('Server:') }}
                </dt>
                <dd>
                    <code>{{ connectionServerUrl }}</code>
                </dd>
                <dd v-if="connectionIp">
                    {{ $gettext('You may need to connect directly via your IP address:') }}
                    <code>{{ connectionIp }}</code>
                </dd>

                <template v-if="connectionStreamPort !== null">
                    <dt class="mb-1">
                        {{ $gettext('Port:') }}
                    </dt>
                    <dd><code>{{ connectionStreamPort }}</code></dd>
                    <dd>
                        {{ $gettext('For some clients, use port:') }}
                        <code>{{ connectionStreamPort + 1 }}</code>
                    </dd>
                </template>

                <dt class="mb-1">
                    {{ $gettext('Password:') }}
                </dt>
                <dd>
                    <code>dj_username:dj_password</code>,
                    <code>dj_username,dj_password</code>
                    {{ $gettext('or') }}
                    <code>dj_username;dj_password</code>
                </dd>
                <dd>
                    {{ $gettext('Shoutcast source connections do not send a separate username, so enter the combined DJ username and password in the password field.') }}
                </dd>
            </dl>
        </div>
        <div class="card-body">
            <p class="card-text">
                {{ $gettext('Setup instructions for broadcasting software are available on the AzuraCast wiki.') }}
                <br>
                <a
                    href="/docs/user-guide/streaming-software/"
                    target="_blank"
                >
                    {{ $gettext('AzuraCast Wiki') }}
                </a>
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ApiStationsVueStreamersProps } from "~/entities/ApiInterfaces.ts";

defineProps<ApiStationsVueStreamersProps>();
</script>
