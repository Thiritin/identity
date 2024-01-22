<script setup>
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import {ref, watch} from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

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

const code = ref('')

const form = useForm({
    code: '',
    secret: props.secret
})

const deleteForm = useForm({
    password: ''
})

function submitForm() {
    form.code = code.value;
    form.post(route('settings.two-factor.totp.store'))
}

function deactivateTotp() {
    deleteForm.delete(route('settings.two-factor.totp.destroy'))
}

// Ensure that form.code does not extend 6 characters
watch(code, (value) => {
    let newValue = value;
    if (value.length > 6) {
        newValue = newValue.substring(0, 6)
    }
    // Only allow numbers
    if (!value.match(/^[0-9]*$/)) {
        newValue = newValue.substring(0, value.length - 1)
    }

    code.value = newValue;
}, {immediate: true, deep: true, flush: 'post'})
</script>

<template>
    <div v-if="secret">
        <SettingsHeader>Setup Two Factor</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">Please scan the QRCode below and enter the code provided by your authenticator app.
        </SettingsSubHeader>
        <form @submit.prevent="submitForm">
            <div class="mb-4">
                <img class="mx-auto bg-white" :src="qrCode" alt="QR Code">
                <small class="text-center w-full block">{{ secret }}</small>
            </div>
            <div class="mb-4">
                <label class="font-semibold text-xs" for="code">Code</label>
                <BaseInput
                    v-model="code"
                    type="text"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    name="code"
                    :error="form.errors.code"
                ></BaseInput>
            </div>
            <PrimaryButton type="submit" class="ml-auto block">Submit</PrimaryButton>
        </form>
    </div>
    <div v-else>
        <SettingsHeader>Disable Two Factor</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">To disable two factor authentication, please enter your password.
        </SettingsSubHeader>
        <form @submit.prevent="deactivateTotp">
            <div class="mb-4">
                <label class="font-semibold text-xs" for="password">Current Password</label>
                <BaseInput
                    v-model="deleteForm.password"
                    type="password"
                    id="password"
                    name="code"
                    :error="deleteForm.errors.password"
                ></BaseInput>
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
