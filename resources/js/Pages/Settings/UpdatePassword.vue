<template>
    <Head title="Change Password"></Head>
    <div>
        <SettingsHeader>{{
                $trans('update_your_password')
            }}
        </SettingsHeader>
        <form @submit.prevent="submitForm">
            <div v-if="success">
                <div
                    class="bg-primary-200 px-4 py-2 rounded mt-4 font-semibold"
                >
                    Your password has been updated.
                </div>
            </div>
            <div v-else class="space-y-6">
                <div class="flex flex-col gap-2 mt-4">
                    <label for="current_password">{{ $trans('current_password') }}</label>
                    <InputText id="current_password"
                               type="password"
                               autocomplete="password"
                               autofocus
                               @change="form.validate('current_password')"
                               :invalid="form.invalid('current_password')"
                               v-model.trim.lazy="form.current_password"
                    />
                    <InlineMessage v-if="form.invalid('current_password')"
                                   severity="error">{{ form.errors.current_password }}
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
                    class="sm:col-span-2 sm:col-start-2 mt-2 mb-2"
                ></PasswordInfoBox>

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


                <div class="flex justify-end">
                    <Button
                        :loading="form.processing"
                        type="submit"
                        class="block"
                        :label="$trans('submit')"
                    />
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import {Head} from "@inertiajs/vue3";
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import BaseInput from "@/Components/BaseInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PasswordInfoBox from "../../Auth/PasswordInfoBox.vue";
import {useForm} from 'laravel-precognition-vue-inertia'
import InputText from "primevue/inputtext";
import InlineMessage from "primevue/inlinemessage";
import Button from "primevue/button";

defineProps({
    errors: Object,
    success: Boolean,
});

const form = useForm('post', route('settings.update-password.store'), {
    current_password: '',
    password: '',
    password_confirmation: '',
    destroy_sessions: false,
});

function submitForm() {
    form.submit();
}
</script>
<script>

import AuthLayout from "@/Layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>

<style scoped></style>
