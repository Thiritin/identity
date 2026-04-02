<script setup>

import {computed, ref} from "vue";
import LoginScreenWelcome from "@/Auth/LoginScreenWelcome.vue";
import {Head, useForm} from "@inertiajs/vue3";
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/Components/ui/input-otp';

const props = defineProps({
    lastUsedMethod: String,
    twoFactors: Array,
    submitFormUrl: String,
})

const form = useForm('post', props.submitFormUrl, {
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
            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="flex flex-col gap-2">
                    <label for="code">{{ selectedMethodName }}</label>
                    <Input v-if="selectedMethodName !== 'TOTP'" id="code"
                               type="text"
                               autocomplete="one-time-code"
                               :class="{ 'border-destructive': form.invalid('code') }"
                               v-model.trim.lazy="form.code"
                    />
                    <InputOTP
                        v-else
                        v-model="form.code"
                        :maxlength="6"
                    >
                        <InputOTPGroup>
                            <InputOTPSlot v-for="i in 6" :key="i" :index="i - 1" />
                        </InputOTPGroup>
                    </InputOTP>

                    <p v-if="form.invalid('code')" class="text-sm text-destructive">{{ form.errors.code }}</p>
                </div>


                <div class="flex justify-end">
                    <Button
                        :disabled="form.processing"
                        type="submit"
                        class="block"
                    >{{ $t('login') }}</Button>
                </div>
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
