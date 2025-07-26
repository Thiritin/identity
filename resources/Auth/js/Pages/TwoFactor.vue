<script setup>

import {computed, ref} from "vue";
import LoginScreenWelcome from "@/Auth/LoginScreenWelcome.vue";
import {Head} from "@inertiajs/vue3";
import {useForm} from 'laravel-precognition-vue-inertia'
import InputText from "primevue/inputtext";
import InlineMessage from "primevue/inlinemessage";
import Button from "primevue/button";
import InputOtp from 'primevue/inputotp'

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
                    <InputText v-if="selectedMethodName !== 'TOTP'" id="code"
                               type="text"
                               autocomplete="one-time-code"
                               :invalid="form.invalid('code')"
                               v-model.trim.lazy="form.code"
                    />
                    <InputOtp
                        v-else
                        autocomplete="one-time-code"
                        :length="6"
                        class="w-full flex grid-cols-6 justify-between"
                        :invalid="form.invalid('code')"
                        v-model.trim.lazy="form.code"
                    />

                    <InlineMessage v-if="form.invalid('code')" severity="error">{{ form.errors.code }}
                    </InlineMessage>
                </div>


                <div class="flex justify-end">
                    <Button
                        :loading="form.processing"
                        type="submit"
                        class="block"
                        :label="$trans('login')"
                    />
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
