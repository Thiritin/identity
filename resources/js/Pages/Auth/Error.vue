<template>
    <Head title="Error"></Head>
    <Logo></Logo>
    <LoginScreenWelcome :title="title" class="mb-10"/>
    <div>{{ description }}</div>
</template>

<script>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import {Head} from "@inertiajs/vue3";

export default {
    components: {
        Head,
        AuthLayout,
        Logo,
        LoginScreenWelcome,
    },
    layout: AuthLayout,

    props: {
        title: String,
        description: String,
    },

    methods: {
        submit() {
            this.form.post(this.route('verification.send'))
        },
    },
}
</script>
