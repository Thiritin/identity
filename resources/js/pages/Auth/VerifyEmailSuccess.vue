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
            <Button as="a" :href="route('dashboard')" class="ml-auto">
                Continue to Dashboard
            </Button>
        </div>
    </div>
</template>
<script setup>

import Logo from '@/components/Logo.vue'
import LoginScreenWelcome from '../LoginScreenWelcome.vue'
import AuthLayout from '@Shared/layouts/AuthLayout.vue'
import UserBox from '../UserBox.vue'
import {Head, Link, useForm, usePage} from '@inertiajs/vue3'
import {computed} from "vue";
import { Button } from '@/components/ui/button';

defineProps({
    user: {
        type: Object,
        required: false
    }
})

defineOptions({layout: AuthLayout})
</script>
