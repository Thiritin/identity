<template>
    <settings-layout>
        <div>
            <SettingsHeader>{{ $trans('two_factor') }}</SettingsHeader>
            <SettingsSubHeader>Check back soon!</SettingsSubHeader>
        </div>
    </settings-layout>
</template>

<script>
import SettingsLayout from '@/Layouts/SettingsLayout'
import SettingsHeader from "@/Components/Settings/SettingsHeader";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader";

export default {
    name: 'TwoFactor',
    components: {SettingsLayout, SettingsHeader, SettingsSubHeader},
}
</script>

<style scoped>

</style>
