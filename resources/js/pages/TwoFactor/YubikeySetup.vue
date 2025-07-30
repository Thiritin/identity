<script setup>
import SettingsHeader from "@Shared/components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@Shared/components/Settings/SettingsSubHeader.vue";
import {Head} from "@inertiajs/vue3";
import {ref} from "vue";
import ListItem from "@Shared/components/ListItem.vue";
import BaseButton from "../../components/BaseButton.vue";
import InputText from "@Shared/components/volt/InputText.vue";
import InlineMessage from "@Shared/components/volt/Message.vue";
import {useForm} from 'laravel-precognition-vue-inertia'
import Button from "@Shared/components/volt/Button.vue";

const disableKeyId = ref(null)
const showCreateField = ref(false)

const props = defineProps({
    keys: {
        type: Array,
        default: []
    },
})

const enableForm = useForm('post', route('settings.two-factor.yubikey.store'), {
    code: '',
    name: '',
})

const disableForm = useForm('delete', route('settings.two-factor.yubikey.destroy'), {
    keyId: '',
    password: '',
})

function submitForm() {
    enableForm.submit({
        onSuccess: () => {
            form.reset()
        }
    })
}

function submitDisableForm() {
    disableForm.keyId = disableKeyId.value;
    disableForm.submit()
}
</script>

<template>
    <Head title="Yubikey Setup"></Head>
    <div v-if="props.keys.length === 0 || showCreateField === true">
        <SettingsHeader>Setup Yubikey Authenticator</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">Please tap your Yubikey to add it as a second factor.
        </SettingsSubHeader>
        <form @submit.prevent="submitForm()" class="space-y-4">
            <div class="flex flex-col gap-2">
                <label for="code">{{ $trans('yubikey_otp') }}</label>
                <InputText id="code"
                           type="text"
                           @keydown.enter.capture="null"
                           autocomplete="code"
                           @change="enableForm.validate('code')"
                           :invalid="enableForm.invalid('code')"
                           v-model.trim.lazy="enableForm.code"
                />
                <InlineMessage v-if="enableForm.invalid('code')" severity="error">{{ enableForm.errors.code }}
                </InlineMessage>
            </div>
            <div class="flex flex-col gap-2">
                <label for="name">Name / Identifier</label>
                <InputText id="name"
                           type="text"
                           placeholder="Work, Home, etc."
                           autocomplete="name"
                           @change="enableForm.validate('name')"
                           :invalid="enableForm.invalid('name')"
                           v-model.trim.lazy="enableForm.name"
                />
                <InlineMessage v-if="enableForm.invalid('name')" severity="error">{{ enableForm.errors.name }}
                </InlineMessage>
            </div>

            <div class="flex justify-end">
                <Button
                    :loading="enableForm.processing"
                    type="submit"
                    class="block"
                    :label="$trans('submit')"
                />
            </div>
        </form>
    </div>
    <div v-else-if="disableKeyId !== null">
        <SettingsHeader>Disable Two Factor</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">To disable two factor authentication, please enter your password.
        </SettingsSubHeader>
        <form @submit.prevent="submitDisableForm" class="space-y-6">
            <div class="flex flex-col gap-2">
                <label for="password">Current Password</label>
                <InputText id="password"
                           type="password"
                           autocomplete="password"
                           @change="disableForm.validate('password')"
                           :invalid="disableForm.invalid('password')"
                           v-model.trim.lazy="disableForm.password"
                />
                <InlineMessage v-if="disableForm.invalid('password')" severity="error">{{ disableForm.errors.password }}
                </InlineMessage>
            </div>
            <div class="flex justify-end gap-3">
                <Button severity="secondary" size="small" @click="disableKeyId = null" secondary>Cancel</Button>
                <Button size="small" primary>Submit</Button>
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
                    <Button link @click="disableKeyId = key.id" size="small" icon="pi pi-trash"/>
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
import AuthLayout from "../../layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>
