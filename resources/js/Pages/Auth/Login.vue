<template>
    <Head title="Login"></Head>
    <div class="text-center">
        <Logo class="mx-auto"></Logo>
        <LoginScreenWelcome
            :sub-title="$t('loginscreen_sign_in_to_continue')"
            :title="$t('loginscreen_welcome')"
            class="mb-10"
        />
    </div>
    <form class="space-y-6" @submit.prevent="submit">
        <Transition name="field-error">
            <p v-if="$page.props.errors.throttle" class="text-xs text-destructive text-center">{{ $page.props.errors.throttle }}</p>
        </Transition>
        <Transition name="field-error">
            <div v-if="sessionExpired" class="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-center space-y-2">
                <p class="text-sm text-destructive">{{ $t('session_expired') }}</p>
                <Button variant="outline" size="sm" @click="() => window.location.reload()">
                    <RefreshCw class="size-3.5" /> {{ $t('reload_page') }}
                </Button>
            </div>
        </Transition>
        <div class="space-y-4">
            <FormField
                id="email"
                :label="$t('email')"
                type="email"
                autocomplete="email"
                v-model="form.email"
                :error="form.errors.email"
            />
            <div class="flex flex-col gap-2">
                <label for="password" class="text-sm text-gray-600 dark:text-primary-300">{{ $t('password') }}</label>
                <Input id="password"
                    type="password"
                    autocomplete="current-password"
                    @change="form.validate('password')"
                    :class="{ 'border-destructive': form.invalid('password') || errors.nouser }"
                    v-model.trim.lazy="form.password"
                />
                <Transition name="field-error">
                    <p v-if="form.invalid('password')" class="text-xs text-destructive">{{ form.errors.password }}</p>
                </Transition>
                <Transition name="field-error">
                    <p v-if="errors.nouser" class="text-xs text-destructive">{{ $t('wrong_login_details_message') }}</p>
                </Transition>
                <Transition name="field-error">
                    <p v-if="errors.general" class="text-xs text-destructive">{{ errors.general }}</p>
                </Transition>
                <Link
                    :href="route('auth.forgot-password.view')"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    {{ $t('forgot_password_btn') }}
                </Link>
            </div>
        </div>
        <HoneypotFields :honeypot="form" />
        <div v-if="requiresPow" class="space-y-2">
            <AltchaWidget v-model="form.altcha" />
            <Transition name="field-error">
                <p v-if="form.errors.altcha" class="text-xs text-destructive text-center">{{ form.errors.altcha }}</p>
            </Transition>
        </div>
        <Button
            :disabled="form.processing || (requiresPow && !form.altcha)"
            type="submit"
            class="w-full"
        >{{ $t('sign_in') }} <ArrowRight class="size-4" /></Button>
        <template v-if="hasPasskeys">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-gray-200 dark:border-gray-700" />
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-white dark:bg-gray-900 px-2 text-gray-500">{{ $t('or') }}</span>
                </div>
            </div>
            <Button
                type="button"
                variant="outline"
                class="w-full"
                :disabled="passkeyLoading"
                @click="loginWithPasskey"
            >
                <Fingerprint class="size-4" />
                {{ $t('passkey_login_button') }}
            </Button>
            <Transition name="field-error">
                <p v-if="passkeyError" class="text-xs text-destructive text-center">{{ passkeyError }}</p>
            </Transition>
        </template>
        <Link
            :href="route('auth.register.view')"
            class="block text-center text-xs text-gray-500 hover:text-gray-700 dark:text-primary-400 dark:hover:text-primary-300"
        >
            {{ $t('choose_create_new_account') }}
        </Link>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import FormField from '@/Components/Auth/FormField.vue'
import HoneypotFields from '@/Components/Auth/HoneypotFields.vue'
import AltchaWidget from '@/Components/Auth/AltchaWidget.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { ArrowRight, RefreshCw, Fingerprint } from 'lucide-vue-next'
import { startAuthentication } from '@simplewebauthn/browser'
import { useHoneypot } from '@/Composables/useHoneypot'
import { ref, onMounted, onUnmounted } from 'vue'
import { trans } from 'laravel-vue-i18n'

defineOptions({
    layout: AuthLayout,
})

const props = defineProps({
    status: String,
    errors: Object,
    email: String,
    requiresPow: Boolean,
    hasPasskeys: Boolean,
})

const form = useForm('post', route('auth.login.password.submit'), {
    email: props.email,
    password: null,
    altcha: null,
    ...useHoneypot(),
})

const sessionExpired = ref(false)
const passkeyLoading = ref(false)
const passkeyError = ref(null)

let removeInvalidListener = null

onMounted(() => {
    removeInvalidListener = router.on('invalid', (event) => {
        if (event.detail.response.status === 419) {
            event.preventDefault()
            sessionExpired.value = true
        }
    })

    if (props.hasPasskeys) {
        loginWithPasskey()
    }
})

onUnmounted(() => {
    removeInvalidListener?.()
})

function submit() {
    form.submit()
}

async function loginWithPasskey() {
    passkeyLoading.value = true
    passkeyError.value = null

    try {
        const optionsRes = await fetch(route('auth.login.passkey.options'))
        const optionsJSON = await optionsRes.json()

        if (!optionsJSON.allowCredentials?.length) {
            passkeyError.value = trans('passkey_none_available')
            return
        }

        const result = await startAuthentication({ optionsJSON })

        router.post(route('auth.login.passkey.verify'), {
            credential: JSON.stringify(result),
        })
    } catch (e) {
        if (e.name !== 'NotAllowedError') {
            passkeyError.value = e.message
        }
    } finally {
        passkeyLoading.value = false
    }
}
</script>
<style scoped>
.field-error-enter-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.field-error-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.field-error-enter-from,
.field-error-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
