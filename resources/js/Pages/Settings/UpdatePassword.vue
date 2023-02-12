<template>
    <SettingsLayout>
        <div>
            <SettingsHeader>{{ $trans('update_your_password') }}</SettingsHeader>
            <SettingsSubHeader>{{ $trans('update_your_password') }}</SettingsSubHeader>
            <div v-if="success">
                <div class="bg-primary-200 px-4 py-2 rounded mt-4 font-semibold">
                    Your password has been updated.
                </div>
            </div>
            <div v-else>
                <BaseInput id='currentPassword' v-model='form.current_password' :error="errors.current_password" autocomplete='password'
                           autofocus label="Current password"
                           name='currentPassword' type="password"></BaseInput>
                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-end">
                    <PasswordInfoBox class="sm:col-span-2 sm:col-start-2 mt-2"></PasswordInfoBox>
                </div>
                <BaseInput id='newPassword' v-model='form.password' :error="errors.password" autocomplete='password'
                           label="New password"
                           name='newPassword' type="password"></BaseInput>
                <BaseInput id='confirmNewPassword' v-model='form.password_confirmation' :error="errors.password_confirmation"
                           autocomplete='password' label="Confirm new password"
                           name='confirmNewPassword' type="password"></BaseInput>
                <div class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-startsm:pt-5 pt-5'>
                    <div class="max-w-lg flex justify-end sm:col-start-2 sm:col-span-2">
                        <PrimaryButton class="" @click="submitForm()">Change password</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </SettingsLayout>
</template>

<script>
import SettingsLayout from "@/Layouts/SettingsLayout.vue";
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import BaseInput from "@/Components/BaseInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";
import PasswordInfoBox from "@/Auth/PasswordInfoBox.vue";
import BaseCheckbox from "@/Components/BaseCheckbox.vue";

export default {
    name: 'UpdatePassword',
    components: {
        BaseCheckbox,
        PasswordInfoBox, SettingsSubHeader, SettingsHeader, SettingsLayout, BaseInput, PrimaryButton
    },
    props: {
        errors: Object,
        success: Boolean
    },
    data() {
        return {
            form: this.$inertia.form({
                current_password: "",
                password: "",
                password_confirmation: "",
                destroy_sessions: false
            })
        }
    },
    methods: {
        submitForm() {
            this.form.post(route('settings.update-password.store'), this.form)
        }
    }

}
</script>

<style scoped>

</style>
