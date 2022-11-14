<template>
    <settings-layout>
        <div>
            <SettingsHeader>{{ $trans('two_factor') }}</SettingsHeader>
            <SettingsSubHeader>Check back soon!</SettingsSubHeader>
        </div>
    </settings-layout>
</template>

<script>
import SettingsLayout from '@/Layouts/SettingsLayout.vue'
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";

export default {
    name: 'TwoFactor',
    components: {SettingsLayout, SettingsHeader, SettingsSubHeader},
}
</script>

<style scoped>

</style>
