<template>
    <app-layout>
        <template #header>
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
                <h1 class='text-3xl font-bold text-white'>{{ $trans('settings') }}</h1>
            </div>
        </template>
        <div
            class='bg-white rounded-lg shadow divide-y divide-gray-200 lg:grid lg:grid-cols-12 lg:divide-y-0 lg:divide-x'>
            <SettingsSidebarMenu :menu-items='settingsMenu'></SettingsSidebarMenu>
            <SettingsContent>
                <slot></slot>
            </SettingsContent>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import SettingsSidebarMenu from '@/Components/Settings/SettingsSidebarMenu.vue'
import SettingsContent from '@/Components/Settings/SettingsContent.vue'

export default {
    name: 'SettingsLayout',
    components: {SettingsContent, SettingsSidebarMenu, AppLayout},
    data() {
        return {
            settingsMenu: [
                {
                    name: 'profile',
                    route: 'profile',
                },
                {
                    name: 'update_your_password',
                    route: 'update-password',
                },
                {
                    name: 'two_factor',
                    route: 'two-factor',
                },
            ],
        }
    },
}
</script>

<style scoped>

</style>
