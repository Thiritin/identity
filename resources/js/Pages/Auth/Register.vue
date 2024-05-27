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
            <InputText id="username"
                       type="text"
                       @change="form.validate('username')"
                       :invalid="form.invalid('username')"
                       v-model.trim.lazy="form.username"
            />
            <InlineMessage v-if="form.invalid('username')" seveGrity="error">{{ form.errors.username }}
            </InlineMessage>
        </div>
        <div class="flex flex-col gap-2">
            <label for="email">{{ $trans('email') }}</label>
            <InputText id="email"
                       placeholder="me@example.org"
                       type="email"
                       autocomplete="email"
                       @change="form.validate('email')"
                       :invalid="form.invalid('email')"
                       v-model.trim.lazy="form.email"
            />
            <InlineMessage v-if="form.invalid('email')" severity="error">{{ form.errors.email }}
            </InlineMessage>
        </div>
        <div class="flex flex-col gap-2">
            <label for="password">{{ $trans('password') }}</label>
            <InputText id="password"
                       type="password"
                       autocomplete="password"
                       @change="form.validate('password')"
                       :invalid="form.invalid('password')"
                       v-model.trim.lazy="form.password"
            />
            <InlineMessage v-if="form.invalid('password')" severity="error">{{ form.errors.password }}
            </InlineMessage>
        </div>
        <PasswordInfoBox
            :password="form.password"
        />
        <div class="flex flex-col gap-2">
            <label for="password_confirmation">{{ $trans('password_confirmation') }}</label>
            <InputText id="password_confirmation"
                       type="password"
                       autocomplete="password"
                       @change="form.validate('password_confirmation')"
                       :invalid="form.invalid('password_confirmation')"
                       v-model.trim.lazy="form.password_confirmation"
            />
            <InlineMessage v-if="form.invalid('password_confirmation')"
                           severity="error">{{ form.errors.password_confirmation }}
            </InlineMessage>
        </div>
        <div class="flex flex-row justify-between pt-10">
            <Link
                :href="route('auth.login.view')"
                class="text-gray-700 dark:text-gray-300"
            >
                {{ $trans('register_back_to_login') }}
            </Link>
            <Button
                :loading="form.processing"
                type="submit"
                :label="$trans('register_button')"
            />
        </div>
    </form>
</template>
<script setup>
import Logo from '@/Auth/Logo.vue'
import LoginScreenWelcome from '@/Auth/LoginScreenWelcome.vue'
import FormInput from '@/Auth/Form/AuthFormInput.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import {Head, Link} from '@inertiajs/vue3'
import PasswordInfoBox from '@/Auth/PasswordInfoBox.vue'
import {useForm} from 'laravel-precognition-vue-inertia'
import InputText from "primevue/inputtext";
import InlineMessage from "primevue/inlinemessage";
import Button from "primevue/button";

// Convert vue2 to vue3
defineOptions({
    layout: AuthLayout
})

const props = defineProps({
    errors: Object
})

const form = useForm('post', route('auth.register.store'), {
    email: null,
    username: null,
    password: null,
    password_confirmation: null,
})

function submit() {
    form.submit()
}
</script>
