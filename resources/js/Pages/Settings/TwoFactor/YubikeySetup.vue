<script setup>
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";
import {Head, useForm} from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {ref} from "vue";
import ListItem from "@/Components/ListItem.vue";
import {XIcon} from "@heroicons/vue/solid";
import BaseButton from "@/Components/BaseButton.vue";

const disableKeyId = ref(null)
const showCreateField = ref(false)

const props = defineProps({
    keys: {
        type: Array,
        default: []
    },
})

const enableForm = useForm({
    code: '',
    name: '',
})

const disableForm = useForm({
    keyId: '',
    password: '',
})

function submitForm() {
    form.post(route('settings.two-factor.yubikey.store'))
}

function submitDisableForm() {
    disableForm.keyId = disableKeyId.value;
    disableForm.delete(route('settings.two-factor.yubikey.destroy'))
}
</script>

<template>
    <Head title="Yubikey Setup"></Head>
    <div v-if="props.keys.length === 0 || showCreateField === true">
        <SettingsHeader>Setup Yubikey Authenticator</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">Please tap your Yubikey to add it as a second factor.
        </SettingsSubHeader>
        <form @submit.prevent="enableForm.post(route('settings.two-factor.yubikey.store'))">
            <div class="mb-4">
                <label class="font-semibold text-xs" for="code">Yubikey OTP</label>
                <BaseInput
                    v-model="enableForm.code"
                    type="text"
                    name="code"
                    :error="enableForm.errors.code"
                ></BaseInput>
            </div>
            <div class="mb-4">
                <label class="font-semibold text-xs" for="name">Name / Identifier</label>
                <BaseInput
                    v-model="enableForm.name"
                    placeholder="Work, Home, etc."
                    type="text"
                    name="name"
                    :error="enableForm.errors.name"
                ></BaseInput>
            </div>
            <PrimaryButton type="submit" class="ml-auto block">Submit</PrimaryButton>
        </form>
    </div>
    <div v-else-if="disableKeyId !== null">
        <SettingsHeader>Disable Two Factor</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">To disable two factor authentication, please enter your password.
        </SettingsSubHeader>
        <form @submit.prevent="submitDisableForm">
            <div class="mb-4">
                <label class="font-semibold text-xs" for="password">Current Password</label>
                <BaseInput
                    v-model="disableForm.password"
                    type="password"
                    id="password"
                    name="password"
                    :error="disableForm.errors.password"
                ></BaseInput>
            </div>
            <div class="flex justify-end gap-3">
                <BaseButton @click="disableKeyId = null" secondary>Cancel</BaseButton>
                <BaseButton primary>Submit</BaseButton>
            </div>
        </form>
    </div>
    <div v-else>
        <SettingsHeader>Manage your Yubikeys</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">Here you can manage your Yubikeys. You can add new ones, or remove existing ones.
        </SettingsSubHeader>
        <div class="divide-y divide-gray-300 mb-4">
            <ListItem v-for="key in keys" class="items-center">
                <div>
                    <div class="font-semibold">{{ key.name }}</div>
                    <div class="text-sm">{{ key.last_used_at }}</div>
                </div>
                <div>
                    <XIcon class="w-8 p-2 hover:bg-gray-200 rounded cursor-pointer"
                           @click="disableKeyId = key.id"></XIcon>
                </div>
            </ListItem>
        </div>
        <div class="flex justify-end">
            <BaseButton @click="showCreateField = true" primary>Add New</BaseButton>
        </div>
    </div>
</template>

<style scoped>

</style>

<script>
import AuthLayout from "@/Layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>
