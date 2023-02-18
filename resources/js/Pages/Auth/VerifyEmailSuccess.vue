<template>
    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('verifysuccess_subtitle')"
            :title="$trans('verifysuccess_title')"
            class="mb-6"
        />
        <div class="space-y-8">
            <UserBox :user="user"/>
            <div class="flex items-center justify-between">
                <InertiaLink
                    :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'"
                    :disabled="form.processing"
                    :href="route('auth.oidc.login')"
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
                    {{ $trans('continue_to_login') }}
                </InertiaLink>
            </div>
        </div>
    </auth-layout>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import UserBox from "@/Pages/Auth/UserBox.vue";

export default {
    components: {
        UserBox,
        AuthLayout,
        Logo,
        LoginScreenWelcome,
    },

    props: {
        user: Object,
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
