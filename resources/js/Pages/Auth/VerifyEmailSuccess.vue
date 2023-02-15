<template>
    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('verifysuccess_subtitle')"
            :title="$trans('verifysuccess_title')"
            class="mb-6"
        />
        <div class="space-y-12">
            <div class="rounded border-primary-200 border px-6 py-6 flex justify-left items-center gap-4">
                <CircleUser class="fill-current text-primary-500 w-14"></CircleUser>
                <div>
                    <div class="font-bold">{{ user.name }}</div>
                    <div class="text-primary-800 font-light">{{ user.email }}</div>
                </div>
            </div>
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
import CircleUser from "@/Components/Icons/CircleUser.vue";

export default {
    components: {
        CircleUser,
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
