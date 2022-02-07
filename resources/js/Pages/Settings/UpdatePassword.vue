<template>
    <SettingsLayout>
        <div>
            <SettingsHeader>{{ $trans('update_your_password') }}</SettingsHeader>
            <SettingsSubHeader>{{ $trans('update_your_password') }}</SettingsSubHeader>
            <BaseInput label="Current password" id='currentPassword' v-model='form.current_password'
                       autocomplete='password' name='currentPassword'
                       type="password" :error="errors.current_password"></BaseInput>
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-end">
                <PasswordInfoBox class="sm:col-span-2 sm:col-start-2"></PasswordInfoBox>
            </div>
            <BaseInput label="New password" id='newPassword' v-model='form.password' autocomplete='password'
                       name='newPassword'
                       type="password" :error="errors.password"></BaseInput>
            <BaseInput label="Confirm new password" id='confirmNewPassword' v-model='form.password_confirmation'
                       autocomplete='password' name='confirmNewPassword'
                       type="password" :error="errors.password_confirmation"></BaseInput>
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-end">
                <BaseCheckbox class="sm:col-span-2 sm:col-start-2" v-model="form.destroy_sessions"
                              label="Destroy all existing sessions."></BaseCheckbox>
            </div>
            <div class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-startsm:pt-5 pt-5'>
                <div class="max-w-lg flex justify-end sm:col-start-2 sm:col-span-2">
                    <PrimaryButton class="" @click="submitForm()">Change password</PrimaryButton>
                </div>
            </div>
        </div>
    </SettingsLayout>
</template>

<script>
import SettingsLayout from "../../Layouts/SettingsLayout";
import SettingsHeader from "@/Components/Settings/SettingsHeader";
import BaseInput from "@/Components/BaseInput";
import PrimaryButton from "@/Components/PrimaryButton";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader";
import PasswordInfoBox from "@/Auth/PasswordInfoBox";
import BaseCheckbox from "@/Components/BaseCheckbox";

export default {
    name: 'UpdatePassword',
    components: {
        BaseCheckbox,
        PasswordInfoBox, SettingsSubHeader, SettingsHeader, SettingsLayout, BaseInput, PrimaryButton
    },
    props: {
        errors: Object
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
