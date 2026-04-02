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
    hasBackupCodes: Boolean,
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
    if (selectedMethod.value === 'backup_code') return 'Backup Code';
    return selectedMethod.value === 'totp' ? 'TOTP' : 'Yubikey OTP';
})

const otherMethodName = computed(() => {
    return selectedMethod.value === 'totp' ? 'Yubikey OTP' : 'TOTP';
})

const otherMethodType = computed(() => {
    return selectedMethod.value === 'totp' ? 'yubikey' : 'totp';
})

const otherMethodAvailable = computed(() => {
    if (selectedMethod.value === 'backup_code') return false;
    return availableMethodTypes.value.includes(otherMethodType.value.toLowerCase());
})

const previousMethod = ref(null);

const submitForm = () => {
    form.post(props.submitFormUrl)
}

const toggleMethod = () => {
    selectedMethod.value = selectedMethod.value === 'totp' ? 'yubikey' : 'totp';
    form.method = selectedMethod.value;
    form.code = '';
}

const switchToBackupCode = () => {
    previousMethod.value = selectedMethod.value;
    selectedMethod.value = 'backup_code';
    form.method = 'backup_code';
    form.code = '';
}

const switchBackFromBackupCode = () => {
    selectedMethod.value = previousMethod.value || props.lastUsedMethod;
    form.method = selectedMethod.value;
    form.code = '';
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

                    <!-- Backup code input -->
                    <Input v-if="selectedMethod === 'backup_code'" id="code"
                               type="text"
                               placeholder="XXXX-XXXX"
                               autocomplete="one-time-code"
                               :class="{ 'border-destructive': form.invalid('code') }"
                               v-model.trim.lazy="form.code"
                    />
                    <!-- Yubikey input -->
                    <Input v-else-if="selectedMethod !== 'totp'" id="code"
                               type="text"
                               autocomplete="one-time-code"
                               :class="{ 'border-destructive': form.invalid('code') }"
                               v-model.trim.lazy="form.code"
                    />
                    <!-- TOTP input -->
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
                    <p v-if="$page.props.errors.throttle" class="text-sm text-destructive">{{ $page.props.errors.throttle }}</p>
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
                <div v-if="hasBackupCodes && selectedMethod !== 'backup_code'" @click="switchToBackupCode"
                     class="flex justify-end hover:underline text-sm cursor-pointer text-gray-500">
                    {{ $t('two_factor_backup_code_link') }}
                </div>
                <div v-if="selectedMethod === 'backup_code'" @click="switchBackFromBackupCode"
                     class="flex justify-end hover:underline text-sm cursor-pointer text-gray-500">
                    {{ $t('two_factor_back_to_method', { method: previousMethod === 'totp' ? 'TOTP' : 'Yubikey OTP' }) }}
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
