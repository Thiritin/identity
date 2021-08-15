<template>
    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('password_reset_sub_title')"
            :title="$trans('password_reset_title')"
            class="mb-10"
        />
        <form class="space-y-12" @submit.prevent="submit">
            <div class="space-y-6">
                <div class="space-y-2">
                    <label
                        class="block text-sm font-medium text-gray-700"
                        for="email"
                    >
                        {{ $trans('email') }}
                    </label>
                    <FormInput
                        id="email"
                        v-model.trim.lazy="form.email"
                        :class="{
                            'border-red-500 focus:border-red-500':
                                errors?.email != null,
                        }"
                        autocomplete="email"
                        placeholder="me@example.com"
                        readonly=""
                        type="email"
                    />
                    <span
                        v-if="errors.email"
                        class="w-full text-red-600 text-xs rounded"
                    >
                        {{ errors.email }}
                    </span>
                </div>
                <div class="space-y-2">
                    <label
                        class="block text-sm font-medium text-gray-700"
                        for="password"
                    >
                        {{ $trans('password') }}
                    </label>
                    <FormInput
                        id="password"
                        v-model.trim.lazy="form.password"
                        :class="{
                            'border-red-500 focus:border-red-500':
                                errors?.email != null,
                        }"
                        autocomplete="email"
                        placeholder=""
                        type="password"
                    />
                    <span
                        v-show="errors.password"
                        class="w-full text-red-600 text-xs rounded"
                    >
                        {{ errors.password }}
                    </span>
                </div>
                <div class="space-y-2">
                    <label
                        class="block text-sm font-medium text-gray-700"
                        for="password_confirmation"
                    >
                        {{ $trans('password_confirmation') }}
                    </label>
                    <FormInput
                        id="password_confirmation"
                        v-model.trim.lazy="form.password_confirmation"
                        :class="{
                            'border-red-500 focus:border-red-500':
                                errors?.email != null,
                        }"
                        autocomplete="email"
                        placeholder=""
                        type="password"
                    />
                    <span
                        v-show="errors.password_confirmation"
                        class="w-full text-red-600 text-xs rounded"
                    >
                        {{ errors.password_confirmation }}
                    </span>
                </div>
            </div>
            <div class="flex flex-col">
                <button
                    :class="
                        form.processing ? 'bg-primary-400' : 'bg-primary-500'
                    "
                    :disabled="form.processing"
                    class="
                        py-3
                        rounded-lg
                        px-12
                        ml-auto
                        text-white text-2xl
                        mb-4
                        font-semibold
                        focus:outline-none
                    "
                    type="submit"
                >
                    {{ $trans('reset_password') }}
                </button>
                <inertia-link
                    :href="route('auth.login.view')"
                    class="ml-auto text-gray-700"
                >
                    {{ $trans('back_to_login') }}
                </inertia-link>
            </div>
        </form>
    </auth-layout>
</template>

<script>
    import Logo from '@/Auth/Logo'
    import LoginScreenWelcome from '@/Auth/LoginScreenWelcome'
    import FormInput from '@/Auth/Form/FormInput'
    import ValidationErrors from '@/Jetstream/ValidationErrors'
    import AuthLayout from '@/Layouts/AuthLayout'

    export default {
        components: {
            AuthLayout,
            ValidationErrors,
            Logo,
            LoginScreenWelcome,
            FormInput,
        },

        props: {
            email: String,
            token: String,
            errors: Object,
        },

        data() {
            return {
                form: this.$inertia.form({
                    token: this.token,
                    email: this.email,
                    password: '',
                    password_confirmation: '',
                }),
            }
        },

        methods: {
            submit() {
                this.form.post(this.route('auth.password-reset.store'), {
                    onFinish: () =>
                        this.form.reset('password', 'password_confirmation'),
                })
            },
        },
    }
</script>
