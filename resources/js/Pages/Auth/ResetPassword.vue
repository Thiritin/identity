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
                <InputText id="email"
                           type="email"
                           autocomplete="email"
                           @change="form.validate('email')"
                           :invalid="form.invalid('email')"
                           v-model.trim.lazy="form.email"
                />
                <InlineMessage v-if="form.invalid('email')" severity="error">{{ form.errors.email }}
                </InlineMessage>
            </div>
            <PasswordInfoBox :password="form.password"/>
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
            <div class="flex flex-col gap-2">
                <label for="password_confirmation">{{ $trans('password_confirmation') }}</label>
                <InputText id="password_confirmation"
                           type="password"
                           autocomplete="password_confirmation"
                           @change="form.validate('password_confirmation')"
                           :invalid="form.invalid('password_confirmation')"
                           v-model.trim.lazy="form.password_confirmation"
                />
                <InlineMessage v-if="form.invalid('password_confirmation')"
                               severity="error">{{ form.errors.password_confirmation }}
                </InlineMessage>
            </div>
        </div>
        <div class="flex flex-col">
            <button
                :class="form.processing ? 'bg-primary-400' : 'bg-primary-500'"
                :disabled="form.processing"
                class="py-3 rounded-lg px-12 ml-auto text-white text-2xl mb-4 font-semibold focus:outline-none"
                type="submit"
            >
                {{ $trans('reset_password') }}
            </button>
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
import {Head, Link} from '@inertiajs/vue3'
import InputText from "primevue/inputtext";
import InlineMessage from "primevue/inlinemessage";
import Button from "primevue/button";
import {useForm} from 'laravel-precognition-vue-inertia';

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
