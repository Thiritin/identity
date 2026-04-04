<script setup>

import { computed, ref, watch, onMounted } from "vue";
import LoginScreenWelcome from "@/Auth/LoginScreenWelcome.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
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
const showMethodPicker = ref(false);
const securityKeyError = ref(null);

const selectedMethodName = computed(() => methodNames[selectedMethod.value] || selectedMethod.value)

const methodDescriptions = {
    totp: 'two_factor_totp_description',
    yubikey: 'two_factor_yubikey_description',
    backup_code: 'two_factor_backup_code_description',
}

const alternativeMethods = computed(() => {
    const methods = availableMethodTypes.value.filter(m => m !== selectedMethod.value)
    if (props.hasBackupCodes && selectedMethod.value !== 'backup_code') {
        methods.push('backup_code')
    }
    return methods
})

const hasAlternatives = computed(() => alternativeMethods.value.length > 0)

let autoSubmitted = false

watch(() => form.code, (value) => {
    if (value && value.length === 6 && /^\d{6}$/.test(value) && !form.processing && !autoSubmitted && selectedMethod.value === 'totp') {
        autoSubmitted = true
        submitForm()
    }
})

const submitForm = () => {
    autoSubmitted = false
    form.post(props.submitFormUrl)
}

const switchMethod = (method) => {
    selectedMethod.value = method
    form.method = method
    form.code = ''
    form.credential = ''
    securityKeyError.value = null
    showMethodPicker.value = false

    if (method === 'security_key') {
        triggerSecurityKey()
    }
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
    <Head :title="$t('two_factor_title')"></Head>
    <div>
        <LoginScreenWelcome :title="$t('two_factor_title')"
                            :sub-title="$t('two_factor_subtitle')"></LoginScreenWelcome>
        <div class="mt-8">
            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="flex flex-col items-center gap-2">
                    <label for="code" class="text-sm font-medium text-gray-700 dark:text-primary-200">{{ selectedMethodName }}</label>
                    <p v-if="methodDescriptions[selectedMethod]" class="text-xs text-gray-500 dark:text-primary-400 text-center">{{ $t(methodDescriptions[selectedMethod]) }}</p>

                    <!-- Backup code input -->
                    <Input v-if="selectedMethod === 'backup_code'" id="code"
                               type="text"
                               placeholder="XXXX-XXXX"
                               autocomplete="one-time-code"
                               class="text-center text-lg tracking-widest font-mono"
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
                    <Input v-else id="code"
                               type="text"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               maxlength="6"
                               placeholder="XXXXXX"
                               class="text-center text-2xl tracking-[0.5em] indent-[0.5em] font-mono"
                               :class="{ 'border-destructive': form.invalid('code') }"
                               v-model.trim="form.code"
                    />

                    <p v-if="form.invalid('code')" class="text-sm text-destructive">{{ form.errors.code }}</p>
                    <p v-if="$page.props.errors.throttle" class="text-sm text-destructive">{{ $page.props.errors.throttle }}</p>
                </div>


                <Button
                    :disabled="form.processing"
                    type="submit"
                    class="w-full"
                >{{ $t('login') }}</Button>

                <!-- Method picker -->
                <div v-if="hasAlternatives" class="text-center">
                    <button v-if="!showMethodPicker" type="button" @click="showMethodPicker = true"
                            class="text-sm text-gray-500 hover:underline cursor-pointer dark:text-primary-400">
                        {{ $t('two_factor_try_another_way') }}
                    </button>
                    <div v-else class="space-y-2">
                        <p class="text-xs text-gray-500 dark:text-primary-400">{{ $t('two_factor_choose_method') }}</p>
                        <div v-for="method in alternativeMethods" :key="method">
                            <button type="button" @click="switchMethod(method)"
                                    class="w-full rounded-md border border-gray-200 dark:border-primary-700 px-4 py-2.5 text-sm text-gray-700 dark:text-primary-200 hover:bg-gray-50 dark:hover:bg-primary-800 transition-colors cursor-pointer">
                                {{ methodNames[method] }}
                            </button>
                        </div>
                    </div>
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
