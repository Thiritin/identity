<template>
    <Head title="Forgot Password"></Head>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('password_reset_sub_title')"
        :title="$trans('password_reset_title')"
        class="mb-10"
    />
    <form class="space-y-12" @submit.prevent="submit">
        <div class="space-y-6">
            <div class="flex flex-col gap-2">
                <label for="email">{{ $trans('email') }}</label>
                <Input id="email"
                           type="email"
                           autocomplete="email"
                           @change="form.validate('email')"
                           :class="{ 'border-destructive': form.invalid('email') }"
                           v-model.trim.lazy="form.email"
                />
                <p v-if="form.invalid('email')" class="text-sm text-destructive">{{ form.errors.email }}</p>
            </div>
            <PasswordInfoBox :password="form.password"/>
            <div class="flex flex-col gap-2">
                <label for="password">{{ $trans('password') }}</label>
                <Input id="password"
                           type="password"
                           autocomplete="password"
                           @change="form.validate('password')"
                           :class="{ 'border-destructive': form.invalid('password') }"
                           v-model.trim.lazy="form.password"
                />
                <p v-if="form.invalid('password')" class="text-sm text-destructive">{{ form.errors.password }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <label for="password_confirmation">{{ $trans('password_confirmation') }}</label>
                <Input id="password_confirmation"
                           type="password"
                           autocomplete="password_confirmation"
                           @change="form.validate('password_confirmation')"
                           :class="{ 'border-destructive': form.invalid('password_confirmation') }"
                           v-model.trim.lazy="form.password_confirmation"
                />
                <p v-if="form.invalid('password_confirmation')" class="text-sm text-destructive">{{ form.errors.password_confirmation }}</p>
            </div>
        </div>
        <div class="flex flex-col">
            <Button
                :disabled="form.processing"
                class="ml-auto mb-4"
                type="submit"
            >
                {{ $trans('reset_password') }}
            </Button>
            <Link
                :href="route('auth.login.view')"
                class="ml-auto text-gray-700"
            >
                {{ $trans('back_to_login') }}
            </Link>
        </div>
    </form>
</template>

<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import FormInput from '@/Auth/Form/AuthFormInput.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'
import {Head, Link, useForm} from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

defineOptions({layout: AuthLayout});
const props = defineProps({
    email: String,
    token: String,
});

const form = useForm('post', route('auth.password-reset.store'), {
    email: props.email,
    token: props.token,
    password: null,
    password_confirmation: null,
});

function submit() {
    form.submit({
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        }
    });
}

</script>
