import { ref, shallowRef } from "vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";
import { useTranslate } from "~/vendor/gettext";

export interface WebcasterProps {
    baseUri: string;
}

export interface WebcasterMetadata {
    title: string;
    artist: string;
}

export const [useProvideWebcaster, useInjectWebcaster] =
    createRequiredInjectionState((props: WebcasterProps) => {
        const { baseUri } = props;

        const { notifySuccess, notifyError } = useNotify();
        const { $gettext } = useTranslate();

        const metadata = shallowRef<WebcasterMetadata | null>(null);
        const isConnected = ref(false);
        const isConnecting = ref(false);

        let socket: WebSocket | null = null;
        let connectAttempt = 0;
        let successTimer: ReturnType<typeof setTimeout> | null = null;

        const clearSuccessTimer = () => {
            if (successTimer !== null) {
                clearTimeout(successTimer);
                successTimer = null;
            }
        };

        const closeSocket = () => {
            clearSuccessTimer();

            if (socket !== null) {
                socket.onopen = null;
                socket.onerror = null;
                socket.onclose = null;

                if (
                    socket.readyState === WebSocket.OPEN ||
                    socket.readyState === WebSocket.CONNECTING
                ) {
                    socket.close();
                }

                socket = null;
            }

            isConnected.value = false;
            isConnecting.value = false;
        };

        const sendMetadata = (data: WebcasterMetadata) => {
            metadata.value = data;

            if (isConnected.value && socket?.readyState === WebSocket.OPEN) {
                socket.send(
                    JSON.stringify({
                        type: "metadata",
                        data,
                    }),
                );
            }
        };

        const connect = (
            mediaRecorder: MediaRecorder,
            username: string | null = null,
            password: string | null = null,
        ): Promise<void> => {
            closeSocket();

            const attempt = ++connectAttempt;
            isConnecting.value = true;

            return new Promise((resolve, reject) => {
                const activeSocket = new WebSocket(baseUri, "webcast");
                socket = activeSocket;

                const cleanUsername = username?.trim() || null;
                const cleanPassword = password || null;

                const hello: {
                    [key: string]: any;
                } = {
                    mime: mediaRecorder.mimeType,
                };

                if (cleanUsername !== null) {
                    hello.user = cleanUsername;
                }
                if (cleanPassword !== null) {
                    hello.password = cleanPassword;
                }

                let settled = false;

                mediaRecorder.ondataavailable = async (e: BlobEvent) => {
                    if (
                        attempt !== connectAttempt ||
                        !isConnected.value ||
                        activeSocket.readyState !== WebSocket.OPEN
                    ) {
                        return;
                    }

                    const data = await e.data.arrayBuffer();

                    if (
                        attempt === connectAttempt &&
                        activeSocket.readyState === WebSocket.OPEN
                    ) {
                        activeSocket.send(data);
                    }
                };

                mediaRecorder.onstop = () => {
                    if (attempt === connectAttempt) {
                        closeSocket();
                    }
                };

                const rejectConnection = (message: string) => {
                    if (settled || attempt !== connectAttempt) {
                        return;
                    }

                    settled = true;
                    clearSuccessTimer();
                    isConnected.value = false;
                    isConnecting.value = false;
                    notifyError(message);
                    reject(new Error(message));
                };

                activeSocket.onopen = () => {
                    if (attempt !== connectAttempt) {
                        activeSocket.close();
                        return;
                    }

                    activeSocket.send(
                        JSON.stringify({
                            type: "hello",
                            data: hello,
                        }),
                    );

                    // Liquidsoap does not send a positive auth acknowledgement.
                    // Invalid credentials close the socket shortly after the hello
                    // frame, so only mark WebDJ as connected after the socket has
                    // remained open long enough for authentication to complete.
                    successTimer = setTimeout(() => {
                        successTimer = null;

                        if (
                            attempt !== connectAttempt ||
                            activeSocket.readyState !== WebSocket.OPEN
                        ) {
                            rejectConnection(
                                $gettext(
                                    "Web DJ could not authenticate with the server.",
                                ),
                            );
                            return;
                        }

                        settled = true;
                        isConnecting.value = false;
                        isConnected.value = true;

                        notifySuccess($gettext("Web DJ connected!"));

                        if (metadata.value !== null) {
                            activeSocket.send(
                                JSON.stringify({
                                    type: "metadata",
                                    data: metadata.value,
                                }),
                            );
                        }

                        resolve();
                    }, 1500);
                };

                activeSocket.onerror = () => {
                    rejectConnection(
                        $gettext(
                            "An error occurred while connecting Web DJ to the server.",
                        ),
                    );
                };

                activeSocket.onclose = () => {
                    if (attempt !== connectAttempt) {
                        return;
                    }

                    const wasConnecting = isConnecting.value;

                    clearSuccessTimer();
                    isConnected.value = false;
                    isConnecting.value = false;

                    if (wasConnecting) {
                        rejectConnection(
                            $gettext(
                                "Web DJ connection was rejected. Check the DJ username, password and station streamer settings.",
                            ),
                        );
                    }
                };
            });
        };

        return {
            isConnected,
            isConnecting,
            connect,
            closeSocket,
            metadata,
            sendMetadata,
        };
    });
