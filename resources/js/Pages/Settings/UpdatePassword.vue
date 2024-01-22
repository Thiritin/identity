<template>
    <div>
        <SettingsHeader>{{
                $trans('update_your_password')
            }}
        </SettingsHeader>
        <div v-if="success">
            <div
                class="bg-primary-200 px-4 py-2 rounded mt-4 font-semibold"
            >
                Your password has been updated.
            </div>
        </div>
        <div v-else>
            <div class="my-4">
                <div>
                    <label class="font-semibold text-xs" for="username">Current password</label>
                    <BaseInput
                        id="currentPassword"
                        v-model="form.current_password"
                        :error="errors.current_password"
                        autocomplete="password"
                        autofocus
                        name="currentPassword"
                        type="password"
                    ></BaseInput>
                </div>

                <PasswordInfoBox
                    :password="form.password"
                    class="sm:col-span-2 sm:col-start-2 mt-2"
                ></PasswordInfoBox>
            </div>

            <div class="mb-4">
                <label class="font-semibold text-xs" for="username">New password</label>
                <BaseInput
                    id="newPassword"
                    v-model="form.password"
                    :error="errors.password"
                    autocomplete="password"
                    name="newPassword"
                    type="password"
                ></BaseInput>
            </div>

            <div class="mb-4">
                <label class="font-semibold text-xs" for="username">Confirm new password</label>
                <BaseInput
                    id="confirmNewPassword"
                    v-model="form.password_confirmation"
                    :error="errors.password_confirmation"
                    autocomplete="password"
                    name="confirmNewPassword"
                    type="password"
                ></BaseInput>
            </div>

            <div
                class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-startsm:pt-5 pt-5"
            >
                <div
                    class="max-w-lg flex justify-end sm:col-start-2 sm:col-span-2"
                >
                    <PrimaryButton class="" @click="submitForm()"
                    >Change password
                    </PrimaryButton
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {useForm} from "@inertiajs/vue3";
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import BaseInput from "@/Components/BaseInput.vue";
import PasswordInfoBox from "@/Auth/PasswordInfoBox.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

defineProps({
    errors: Object,
    success: Boolean,
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
    destroy_sessions: false,
});

function submitForm() {
    form.post(route('settings.update-password.store'))
}
</script>
<script>

import AuthLayout from "@/Layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>

<style scoped></style>
