<template>
    <Head title="Email Verified"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('verifysuccess_subtitle')"
        :title="$trans('verifysuccess_title')"
        class="mb-6"
    />
    <div class="space-y-8">
        <UserBox :user="$page.props.user"/>
        <div class="flex justify-end">
            <PrimaryButton class="ml-auto" :href="route('dashboard')">
                Continue to Dashboard
            </PrimaryButton>
        </div>
    </div>
</template>
<script setup>

import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import UserBox from '@/Pages/Auth/UserBox.vue'
import {Head, Link, useForm, usePage} from '@inertiajs/vue3'
import {computed} from "vue";
import PrimaryButton from "../../Components/PrimaryButton.vue";

defineProps({
    user: {
        type: Object,
        required: false
    }
})

defineOptions({layout: AuthLayout})
</script>
