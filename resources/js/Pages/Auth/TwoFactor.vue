<script setup>

import { computed, ref, onMounted } from "vue";
import LoginScreenWelcome from "@/Auth/LoginScreenWelcome.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/Components/ui/input-otp';
import { startAuthentication } from '@simplewebauthn/browser';

const props = defineProps({
    lastUsedMethod: String,
    twoFactors: Array,
    hasBackupCodes: Boolean,
    submitFormUrl: String,
    securityKeyOptionsUrl: String,
})

const form = useForm('post', props.submitFormUrl, {
    code: '',
    credential: '',
    method: props.lastUsedMethod,
})

const availableMethodTypes = computed(() => {
    return props.twoFactors.map(twoFactor => twoFactor.type)
})

const methodNames = {
    totp: 'TOTP',
    yubikey: 'Yubikey OTP',
    security_key: 'Security Key',
    backup_code: 'Backup Code',
}

const selectedMethod = ref(props.lastUsedMethod);
const previousMethod = ref(null);
const securityKeyError = ref(null);

const selectedMethodName = computed(() => methodNames[selectedMethod.value] || selectedMethod.value)

const otherMethods = computed(() => {
    return availableMethodTypes.value.filter(m => m !== selectedMethod.value && m !== 'backup_codes')
})

const submitForm = () => {
    form.post(props.submitFormUrl)
}

const switchMethod = (method) => {
    selectedMethod.value = method
    form.method = method
    form.code = ''
    securityKeyError.value = null

    if (method === 'security_key') {
        triggerSecurityKey()
    }
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

async function triggerSecurityKey() {
    securityKeyError.value = null

    try {
        const optionsRes = await fetch(props.securityKeyOptionsUrl)
        const optionsJSON = await optionsRes.json()
        const result = await startAuthentication({ optionsJSON })

        form.credential = JSON.stringify(result)
        form.method = 'security_key'
        form.post(props.submitFormUrl)
    } catch (e) {
        if (e.name !== 'NotAllowedError') {
            securityKeyError.value = e.message
        }
    }
}

onMounted(() => {
    if (props.lastUsedMethod === 'security_key') {
        triggerSecurityKey()
    }
})

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
                    <!-- Security Key: auto-triggered -->
                    <div v-else-if="selectedMethod === 'security_key'" class="text-center py-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('security_key_tap_to_register') }}</p>
                        <Button type="button" variant="outline" class="mt-2" @click="triggerSecurityKey">
                            {{ $t('two_factor_switch_to_security_key') }}
                        </Button>
                        <p v-if="securityKeyError" class="text-sm text-destructive mt-2">{{ securityKeyError }}</p>
                        <p v-if="form.errors.credential" class="text-sm text-destructive mt-2">{{ form.errors.credential }}</p>
                    </div>
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
                <div v-for="method in otherMethods" :key="method"
                     @click="switchMethod(method)"
                     class="flex justify-end hover:underline text-sm cursor-pointer">
                    Switch to {{ methodNames[method] }}
                </div>
                <div v-if="hasBackupCodes && selectedMethod !== 'backup_code'" @click="switchToBackupCode"
                     class="flex justify-end hover:underline text-sm cursor-pointer text-gray-500">
                    {{ $t('two_factor_backup_code_link') }}
                </div>
                <div v-if="selectedMethod === 'backup_code'" @click="switchBackFromBackupCode"
                     class="flex justify-end hover:underline text-sm cursor-pointer text-gray-500">
                    {{ $t('two_factor_back_to_method', { method: methodNames[previousMethod] || 'TOTP' }) }}
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
