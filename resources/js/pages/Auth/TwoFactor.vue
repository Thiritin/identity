<template>
    <Head title="Two Factor"></Head>
    <SettingsHeader>{{ $trans('two_factor') }}</SettingsHeader>
    <SettingsSubHeader
        class="mb-4">Enabling two factor authentication secures your account by requiring a 2nd form of authentication for accessing your account.
    </SettingsSubHeader>
    <div class="divide-y divide-gray-300">
        <ListItem
            :hover="true"
            v-for="auth in twoFactorMethods"
            :href="auth.url"
        >
            <div>
                <div class="font-semibold">{{ auth.name }}</div>
                <div class="text-sm">{{ auth.description }}</div>
            </div>
            <div v-if="auth.active"
                 class="dark:bg-primary-900 dark:text-primary-100 bg-primary-500 text-white px-2 py-1 rounded-lg font-semibold text-sm">
                Active
            </div>
        </ListItem>
    </div>
</template>

<script setup>
import SettingsHeader from '@Shared/components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@Shared/components/Settings/SettingsSubHeader.vue'
import ListItem from "@Shared/components/ListItem.vue";
import {Head} from "@inertiajs/vue3";

const props = defineProps({
    twoFactorMethods: {
        type: Array,
        required: true
    }
})
</script>

<style scoped></style>

<script>
import AuthLayout from "../../layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>
