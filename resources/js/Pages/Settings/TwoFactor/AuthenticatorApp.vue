<script setup>
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";
import {Head, useForm} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {watch} from "vue";

const props = defineProps({
    secret: {
        type: String,
        required: false
    },
    qrCode: {
        type: String,
        required: false
    }
})

const enableForm = useForm('post', route('settings.two-factor.totp.store'), {
    code: '',
    secret: props.secret
})

const deleteForm = useForm('delete', route('settings.two-factor.totp.destroy'), {
    password: ''
})

function submitForm() {
    enableForm.submit()
}

function deactivateTotp() {
    deleteForm.submit()
}

// Ensure that form.code does not extend 6 characters

watch(enableForm, (value) => {
    let newValue = value.code;
    if (value.code.length > 6) {
        newValue = newValue.substring(0, 6)
    }
    // Only allow numbers
    if (!value.code.match(/^[0-9]*$/)) {
        newValue = newValue.substring(0, value.code.length - 1)
    }

    enableForm.code = newValue;
}, {immediate: true, deep: true, flush: 'post'})
</script>

<template>
    <Head title="Authenticator App Setup"></Head>
    <div v-if="secret">
        <SettingsHeader>Setup Two Factor</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">Please scan the QRCode below and enter the code provided by your authenticator app.
        </SettingsSubHeader>
        <form @submit.prevent="submitForm" class="space-y-6">
            <div>
                <img class="mx-auto bg-white" :src="qrCode" alt="QR Code">
                <small class="text-center w-full block">{{ secret }}</small>
            </div>
            <div class="flex flex-col gap-2">
                <label for="code">{{ $trans('code') }}</label>
                <Input id="code"
                       type="number"
                       @change="enableForm.validate('code')"
                       :class="{ 'border-destructive': enableForm.invalid('code') }"
                       v-model.trim.lazy="enableForm.code"
                />
                <p v-if="enableForm.invalid('code')" class="text-sm text-destructive">{{ enableForm.errors.code }}</p>
            </div>
            <div class="flex justify-end">
                <Button
                    :disabled="enableForm.processing"
                    type="submit"
                    class="block"
                >{{ $trans('submit') }}</Button>
            </div>
        </form>
    </div>
    <div v-else>
        <SettingsHeader>Disable Two Factor</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">To disable two factor authentication, please enter your password.
        </SettingsSubHeader>
        <form @submit.prevent="deactivateTotp" class="space-y-6">
            <div class="flex flex-col gap-2">
                <label for="password">{{ $trans('password') }}</label>
                <Input id="password"
                       type="password"
                       autocomplete="password"
                       @change="deleteForm.validate('password')"
                       :class="{ 'border-destructive': deleteForm.invalid('password') }"
                       v-model.trim.lazy="deleteForm.password"
                />
                <p v-if="deleteForm.invalid('password')" class="text-sm text-destructive">{{ deleteForm.errors.password }}</p>
            </div>
            <PrimaryButton @click="deactivateTotp" class="ml-auto block">Disable</PrimaryButton>
        </form>
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
