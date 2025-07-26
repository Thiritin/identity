<template>
    <Head title="Reset Password"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('forgot_password_reset_sub_title')"
        :title="$trans('forgot_password_reset_title')"
        class="mb-10"
    />
    <form v-if="!status" class="space-y-12" @submit.prevent="submit">
        <div class="space-y-6">
            <div
                class="text-sm shadow-md p-2 border-l-[4px] border-primary-600 dark:border-primary-300 dark:text-primary-300"
            >
                {{ $trans('forgot_password_helptext') }}
            </div>

            <div class="flex flex-col gap-2">
                <label for="email">{{ $trans('email') }}</label>
                <InputText id="email"
                           :invalid="errors?.email"
                           @change="form.validate('email')"
                           v-model.trim.lazy="form.email"
                />
                <InlineMessage v-if="form.invalid('email')" severity="error">{{ form.errors.email }}</InlineMessage>
            </div>
        </div>
        <div class="flex flex-row justify-between">
            <Link
                :href="route('auth.login.view')"
                class="text-gray-700 dark:text-primary-300"
            >
                {{ $trans('back_to_login') }}
            </Link>
            <Button
                :loading="form.processing"
                type="submit"
                :label="$trans('send_reset_mail')"
            />
        </div>
    </form>
    <div v-else>
        <div class="text-sm shadow-md p-2 border-l-[4px] border-primary-600">
            {{ status }}
        </div>
    </div>
</template>
<script setup>
import Logo from '@Auth/Pages/Logo.vue'
import LoginScreenWelcome from '@Auth/Pages/LoginScreenWelcome.vue'
import FormInput from '@Auth/Pages/Form/AuthFormInput.vue'
import AuthLayout from '@Shared/Layouts/AuthLayout.vue'
import {Head, Link} from '@inertiajs/vue3'
import InputText from "primevue/inputtext";
import InlineMessage from "primevue/inlinemessage";
import Button from "primevue/button";
import {useForm} from 'laravel-precognition-vue-inertia';

defineOptions({layout: AuthLayout});
const props = defineProps({
    status: String,
    errors: Object,
    canSeeLogin: Boolean,
});

const form = useForm('post', route('auth.forgot-password.store'), {
    email: null,
});

function submit() {
    form.submit();
}
</script>
