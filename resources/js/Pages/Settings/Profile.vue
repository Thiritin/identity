<template>
    <settings-layout>
        <form>
            <div class='space-y-8 divide-y divide-gray-200 sm:space-y-5'>
                <div>
                    <SettingsHeader>Public Profile</SettingsHeader>
                    <SettingsSubHeader>This information will be used across services.</SettingsSubHeader>

                    <div class='mt-6 sm:mt-5 space-y-6 sm:space-y-5'>
                        <div
                            class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:pt-5'>
                            <label class='block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2' for='username'>
                                Username </label>
                            <div class='mt-1 sm:mt-0 sm:col-span-2'>
                                <div class='max-w-lg flex rounded-md shadow-sm'>
                                    <span
                                        class='inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm'>
                                      @
                                    </span>
                                    <input id='username' v-model='form.name' autocomplete='username'
                                           class='form-input flex-1 bg-gray-100 block w-full focus:ring-indigo-500 focus:border-indigo-500 min-w-0 rounded-none rounded-r-md sm:text-sm border-gray-300'
                                           name='username' type='text' disabled/>
                                </div>
                            </div>
                        </div>

                        <div
                            class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center sm:pt-5'>
                            <label class='block text-sm font-medium text-gray-700' for='avatar'> Avatar </label>
                            <div class='mt-1 sm:mt-0 sm:col-span-2'>
                                <div class='flex items-center'>
                                    <span class='h-12 w-12 rounded-full overflow-hidden bg-gray-100'>
                                      <AvatarImage/>
                                        </span>
                                    <div
                                        class='relative ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500'>
                                        <label class='relative' for='avatar' type='button'> <span>Change</span>
                                            <span
                                                class='sr-only'> user photo</span> </label> <input id='avatar'
                                                                                                   class='absolute inset-0 w-full h-full opacity-0 cursor-pointer border-gray-300 rounded-md'
                                                                                                   name='avatar'
                                                                                                   type='file'
                                                                                                   accept='image/png,image/jpeg,image/jpg'
                                                                                                   @change='onFileChange($event)'/>
                                    </div>
                                </div>
                                <span v-show='errors.image' class='text-xs text-red-600'>{{ errors.image }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class='pt-4 pb-5 space-y-6 sm:pt-6 sm:space-y-5'>
                    <div>
                        <SettingsHeader>Contact Information</SettingsHeader>
                        <SettingsSubHeader>How can we reach you?</SettingsSubHeader>
                        <BaseInput :label="$trans('email')" id='email' v-model='form.email' autocomplete='email'
                                   name='email'
                                   type="email"></BaseInput>
                    </div>
                </div>

                <div class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-startsm:pt-5 pt-5'>
                    <div class="max-w-lg flex justify-end sm:col-start-2 sm:col-span-2">
                        <InertiaLink :href="route('profile')">
                            <BaseButton type='button'>
                                Cancel
                            </BaseButton>
                        </InertiaLink>

                        <PrimaryButton class="ml-3">Save</PrimaryButton>
                    </div>
                </div>
            </div>
        </form>
    </settings-layout>
</template>

<script>
import {useForm} from '@inertiajs/inertia-vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage'
import SettingsLayout from '@/Layouts/SettingsLayout'
import SettingsHeader from '@/Components/Settings/SettingsHeader'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader'
import BaseInput from "@/Components/BaseInput";
import BaseButton from "@/Components/BaseButton";
import PrimaryButton from "@/Components/PrimaryButton";

export default {
    props: {
        errors: Object,
    },

    components: {
        PrimaryButton,
        BaseButton,
        SettingsSubHeader,
        SettingsHeader,
        SettingsLayout,
        AvatarImage,
        BaseInput
    },

    data() {
        return {
            previewUrl: null,
            file: null,
            form: useForm({
                name: this.$page.props.user.name,
                email: this.$page.props.user.email,
            }),
        }
    },

    methods: {
        onFileChange(e) {
            const file = e.target.files[0]
            this.file = file
            this.previewUrl = URL.createObjectURL(file)
            this.$refs.avatarmodal.open = true
            URL.revokeObjectURL(file)
        },
        submitForm() {
            this.form.post(route('settings.update-profile'), this.form)
        }
    },
}
</script>
