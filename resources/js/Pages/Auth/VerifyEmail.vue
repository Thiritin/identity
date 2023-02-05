<template>
    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('verify_subtitle')"
            :title="$trans('verify_title')"
            class="mb-10"
        />
        <div class="space-y-12">
            <div
                class="text-sm shadow-md p-2 border-l-[4px] border-primary-600"
            >
                {{ $trans('verify_helptext') }}
            </div>

            <div
                v-if="verificationLinkSent"
                class="mb-4 font-medium text-sm text-green-600"
            >
                {{ $trans('verify_text_sent_to_your_mail') }}
            </div>

            <form @submit.prevent="submit">
                <div class="mt-4 flex items-center justify-between">
                    <button
                        :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'"
                        :disabled="form.processing"
                        class="
                            py-3
                            rounded-lg
                            px-8
                            mr-auto
                            text-white text-sm
                            mb-4
                            font-semibold
                            focus:outline-none
                        "
                    >
                        {{ $trans('resend_verification_mail') }}
                    </button>

                    <inertia-link
                        :href="route('auth.logout')"
                        as="button"
                        class="
                            underline
                            text-sm text-gray-600
                            hover:text-gray-900
                        "
                    >{{ $trans('logout') }}
                    </inertia-link>
                </div>
            </form>
        </div>
    </auth-layout>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'

export default {
    components: {
        AuthLayout,
        Logo,
        LoginScreenWelcome,
    },

    props: {
        status: String,
    },

    data() {
        return {
            form: this.$inertia.form(),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('verification.send'))
        },
    },

    computed: {
        verificationLinkSent() {
            return this.status === 'verification-link-sent'
        },
    },
}
</script>
