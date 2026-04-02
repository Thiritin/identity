<template>
    <Head title="Two Factor"></Head>
    <SettingsHeader>{{ $t('two_factor') }}</SettingsHeader>
    <SettingsSubHeader
        class="mb-4">{{ $t('two_factor_description') }}
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
                {{ $t('two_factor_active') }}
            </div>
        </ListItem>
    </div>
</template>

<script setup>
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'
import ListItem from "@/Components/ListItem.vue";
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
import AuthLayout from "@/Layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>
