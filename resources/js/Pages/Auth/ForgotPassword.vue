<template>
    <auth-layout>
        <Logo></Logo>
        <LoginScreenWelcome
            :sub-title="$trans('forgot_password_reset_sub_title')"
            :title="$trans('forgot_password_reset_title')"
            class="mb-10"
        />
        <form class="space-y-12" @submit.prevent="submit" v-if="!status">
            <div class="space-y-6">
                <div class="text-sm shadow-md p-2 border-l-[4px] border-primary-600">
                    {{ $trans("forgot_password_helptext") }}
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700" for="email">
                        {{ $trans("email") }}
                    </label>
                    <FormInput
                        id="email"
                        v-model.trim.lazy="form.email"
                        :class="{ 'border-red-500 focus:border-red-500': errors?.email != null }"
                        autocomplete="email"
                        placeholder="me@example.com"
                        type="email"
                    />
                    <span v-show="errors.email" class="w-full text-red-600 text-xs rounded">
						{{ errors.email }}
					</span>
                </div>
            </div>
            <div class="flex flex-col">
                <button
                    :class="
						form.processing || status !== undefined
							? 'bg-primary-400'
							: 'bg-primary-500'
					"
                    :disabled="form.processing || status !== undefined"
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
                    {{ $trans("send_reset_mail") }}
                </button>
                <a :href="route('auth.login.view')" class="ml-auto text-gray-700">
                    {{ $trans("back_to_login") }}
                </a>
            </div>
        </form>
        <div v-else>
            <div class="text-sm shadow-md p-2 border-l-[4px] border-primary-600">
                {{ status }}
            </div>
        </div>
    </auth-layout>
</template>
<script>
    import Logo from "@/Auth/Logo";
    import LoginScreenWelcome from "@/Auth/LoginScreenWelcome";
    import FormInput from "@/Auth/Form/AuthFormInput";
    import AuthLayout from "@/Layouts/AuthLayout";

    export default {
        components: { AuthLayout, Logo, LoginScreenWelcome, FormInput },
        props: { status: String, errors: Object, canSeeLogin: Boolean },
        data() {
            return { form: this.$inertia.form({ email: null }), show: true };
        },
        methods: {
            submit() {
                this.form.post(this.route("auth.forgot-password.store"));
            }
        }
    };
</script>
