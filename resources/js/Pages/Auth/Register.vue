<template>
    <Head title="Register"/>
    <Logo></Logo>
    <LoginScreenWelcome
        :sub-title="$trans('register_subtitle')"
        :title="$trans('register_title')"
        class="mb-10"
    />
    <form class="space-y-3" @submit.prevent="submit">
        <div class="flex flex-col gap-2">
            <label for="username">{{ $trans('username') }}</label>
            <Input id="username"
                type="text"
                @change="form.validate('username')"
                :class="{ 'border-destructive': form.invalid('username') }"
                v-model.trim.lazy="form.username"
            />
            <p v-if="form.invalid('username')" class="text-sm text-destructive">{{ form.errors.username }}</p>
        </div>
        <div class="flex flex-col gap-2">
            <label for="email">{{ $trans('email') }}</label>
            <Input id="email"
                :model-value="email"
                disabled
                class="bg-muted"
            />
        </div>
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
        <PasswordInfoBox
            :password="form.password"
        />
        <div class="flex flex-col gap-2">
            <label for="password_confirmation">{{ $trans('password_confirmation') }}</label>
            <Input id="password_confirmation"
                type="password"
                autocomplete="password"
                @change="form.validate('password_confirmation')"
                :class="{ 'border-destructive': form.invalid('password_confirmation') }"
                v-model.trim.lazy="form.password_confirmation"
            />
            <p v-if="form.invalid('password_confirmation')" class="text-sm text-destructive">{{ form.errors.password_confirmation }}</p>
        </div>
        <div class="flex flex-row justify-between pt-10">
            <Link
                :href="route('auth.login.view')"
                class="text-gray-700 dark:text-gray-300"
            >
                {{ $trans('register_back_to_login') }}
            </Link>
            <Button
                :disabled="form.processing"
                type="submit"
            >{{ $trans('register_button') }}</Button>
        </div>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'

defineOptions({
    layout: AuthLayout,
})

const props = defineProps({
    email: String,
    errors: Object,
})

const form = useForm('post', route('auth.register.store'), {
    email: props.email,
    username: null,
    password: null,
    password_confirmation: null,
})

function submit() {
    form.submit()
}
</script>
