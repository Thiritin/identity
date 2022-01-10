<template>
    <SettingsLayout>
        <div>
            <SettingsHeader>{{ $trans('update_your_password') }}</SettingsHeader>
            <SettingsSubHeader>{{ $trans('update_your_password') }}</SettingsSubHeader>
            <BaseInput label="Current password" id='currentPassword' v-model='form.currentPassword' autocomplete='password' name='currentPassword'
                       type="password"></BaseInput>
            <BaseInput label="New password" id='newPassword' v-model='form.newPassword' autocomplete='password' name='newPassword'
                       type="password"></BaseInput>
            <BaseInput label="Confirm new password" id='confirmNewPassword' v-model='form.confirmNewPassword' autocomplete='password' name='confirmNewPassword'
                       type="password"></BaseInput>
            <div class="flex justify-end">
                <PrimaryButton class="">Change password</PrimaryButton>
            </div>
        </div>
    </SettingsLayout>
</template>

<script>
import SettingsLayout from "../../Layouts/SettingsLayout";
import SettingsHeader from "@/Components/Settings/SettingsHeader";
import BaseInput from "@/Components/BaseInput";
import PrimaryButton from "@/Components/PrimaryButton";

export default {
    name: 'UpdatePassword',
    components: {SettingsHeader, SettingsLayout, BaseInput, PrimaryButton},
    data() {
        return {
            form: {
                currentPassword: "",
                newPassword: "",
                confirmNewPassword: ""
            }
        }
    }

}
</script>

<style scoped>

</style>
