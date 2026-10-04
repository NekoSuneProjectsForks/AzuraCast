<template>
    <tab
        :label="$gettext('Advanced')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-multi-check
                id="edit_form_backend_options"
                class="col-md-12"
                :field="r$.backend_options.$self"
                :options="backendOptions"
                stacked
                :label="$gettext('Advanced Manual AutoDJ Scheduling Options')"
                :description="$gettext('Control how this playlist is handled by the AutoDJ software.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { computed } from "vue";
import Tab from "~/components/Common/Tab.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import { useStationsPlaylistsForm } from "~/components/Stations/Playlists/Form/form.ts";
import { PlaylistSources } from "~/entities/ApiInterfaces";
import { useFormTabClass } from "~/functions/useFormTabClass.ts";
import { useTranslate } from "~/vendor/gettext";

const { form, r$ } = storeToRefs(useStationsPlaylistsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.advancedTab));

const { $gettext } = useTranslate();

const backendOptions = computed(() => {
    if (form.value.source === PlaylistSources.Playlists) {
        return [
            {
                value: "merge",
                text: $gettext(
                    "Play the group's entire rotation as a single block.",
                ),
            },
        ];
    }

    const options = [
        {
            value: "interrupt",
            text: $gettext("Interrupt other songs to play at scheduled time."),
        },
        {
            value: "single_track",
            text: $gettext("Only play one track at scheduled time."),
        },
        {
            value: "merge",
            text: $gettext("Merge playlist to play as a single track."),
        },
    ];

    if (form.value.is_jingle) {
        options.push({
            value: "jingle_overlay",
            text: $gettext(
                "Overlay jingles on top of the currently playing AutoDJ music instead of replacing it. Best used with Once per X Minutes, Once per Hour, or a schedule.",
            ),
        });
    }

    return options;
});
</script>
