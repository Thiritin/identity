<template>
    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome
            :title="title"
            class="mb-10"
        />
        <div>{{ description }}</div>
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
        title: String,
        description: String
    },

    methods: {
        submit() {
            this.form.post(this.route('verification.send'))
        },
    },
}
</script>
