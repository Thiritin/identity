<script setup>

import FormInput from '@/Auth/Form/AuthFormInput.vue'
import {computed, ref} from "vue";
import LoginScreenWelcome from "@/Auth/LoginScreenWelcome.vue";
import {Head, useForm} from "@inertiajs/vue3";

const props = defineProps({
    lastUsedMethod: String,
    twoFactors: Array,
    submitFormUrl: String,
})

const form = useForm({
    code: '',
    method: props.lastUsedMethod
})

const availableMethodTypes = computed(() => {
    return props.twoFactors.map(twoFactor => twoFactor.type)
})

const selectedMethodName = computed(() => {
    return selectedMethod.value === 'totp' ? 'TOTP' : 'Yubikey OTP';
})

const otherMethodName = computed(() => {
    return selectedMethod.value === 'totp' ? 'Yubikey OTP' : 'TOTP';
})

const otherMethodType = computed(() => {
    return selectedMethod.value === 'totp' ? 'yubikey' : 'totp';
})

const otherMethodAvailable = computed(() => {
    return availableMethodTypes.value.includes(otherMethodType.value.toLowerCase());
})

const submitForm = () => {
    form.post(props.submitFormUrl)
}

const toggleMethod = () => {
    selectedMethod.value = selectedMethod.value === 'totp' ? 'yubikey' : 'totp';
    form.method = selectedMethod.value;
}

const selectedMethod = ref(props.lastUsedMethod);

</script>

<template>
    <Head title="Enter your Two Factor"></Head>
    <div>
        <LoginScreenWelcome title="Two Factor"
                            sub-title="One more step"></LoginScreenWelcome>
        <div class="mt-8">
            <form @submit.prevent="submitForm">
                <FormInput
                    v-model="form.code"
                    :error="form.errors.code"
                    id="code"
                    :label="selectedMethodName + ' Code'"
                    autocomplete="one-time-code"
                    class="mb-4"
                    type="text"
                ></FormInput>

                <button
                    :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'"
                    :disabled="form.processing"
                    class="py-3 block ml-auto rounded-lg px-12 text-white text-2xl mb-4 font-semibold focus:outline-none"
                    type="submit"
                >
                    {{ $trans('sign_in') }}
                </button>
                <div v-if="otherMethodAvailable" @click="toggleMethod"
                     class="flex justify-end hover:underline text-sm cursor-pointer">
                    Switch to {{ otherMethodName }}
                </div>
            </form>
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
