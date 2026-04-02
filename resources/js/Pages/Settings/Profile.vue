<template>
    <Head title="Edit Profile"></Head>
    <AvatarModal
        ref="avatarModal"
        :file="file"
        :url="previewUrl"
    ></AvatarModal>
    <form @submit.prevent="submitForm">
        <div>
            <div>

                <div class="mb-4" v-if="showEmailVerify || showEmailTooMany">
                    <BaseAlert
                        v-if="showEmailVerify"
                        message="We have sent you a verification email to confirm your change."
                        title="Please check your email."
                    ></BaseAlert>

                    <BaseAlert
                        v-if="showEmailTooMany"
                        message="Please try again in 15 minutes."
                        title="Too many requests"
                    ></BaseAlert>
                </div>

                <SettingsHeader>Profile</SettingsHeader>
                <SettingsSubHeader class="mb-4">
                    This information will be used across services.
                </SettingsSubHeader>
                <div class="space-y-4">

                    <div class="flex flex-col gap-2">
                        <label for="email">{{ $trans('email') }}</label>
                        <Input id="email"
                                   placeholder="me@example.org"
                                   type="email"
                                   autocomplete="email"
                                   @change="form.validate('email')"
                                   :class="{ 'border-destructive': form.invalid('email') }"
                                   v-model.trim.lazy="form.email"
                        />
                        <p v-if="form.invalid('email')" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="name">{{ $trans('name') }}</label>
                        <Input id="name"
                                   type="text"
                                   autocomplete="name"
                                   @change="form.validate('name')"
                                   :class="{ 'border-destructive': form.invalid('name') }"
                                   v-model.trim.lazy="form.name"
                                   aria-describedby="name-help"
                        />
                        <small id="name-help">This is not your badge name.</small>
                        <p v-if="form.invalid('name')" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                </div>

                <div class="mt-6 sm:mt-5 space-y-6 sm:space-y-5">
                    <div>
                        <label
                            class="block font-medium text-gray-700 dark:text-primary-200 mb-2 text-xs"
                            for="avatar"
                        >
                            Avatar
                        </label>
                        <div class="mt-1 sm:mt-0 sm:col-span-2">
                            <div class="flex items-center">
                                <div
                                    class="h-12 w-12 rounded-full overflow-hidden bg-gray-100"
                                >
                                    <AvatarImage
                                        class="w-full"
                                        :avatar="$page.props.user.avatar"
                                    />
                                </div>
                                <div
                                    class="relative ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    <label
                                        class="relative"
                                        for="avatar"
                                        type="button"
                                    >
                                        <span>Change</span>
                                        <span class="sr-only">
                                                user photo</span
                                        >
                                    </label>
                                    <input
                                        id="avatar"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer border-gray-300 rounded-md"
                                        name="avatar"
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg"
                                        @change="onFileChange($event)"
                                    />
                                </div>
                            </div>
                            <div
                                class="text-gray-600 dark:text-primary-200 text-xs"
                            >
                                {{ $trans('profile_avatar_notice') }}
                            </div>
                            <span
                                v-show="errors.image"
                                class="text-xs text-red-600"
                            >{{ errors.image }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex justify-end mt-5"
            >
                <Button
                    :disabled="form.processing"
                    type="submit"
                >{{ $trans('save') }}</Button>
            </div>

            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Preferences</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <label for="nsfw_content" class="font-medium text-gray-700 dark:text-gray-300">NSFW Content</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Allow display of age-restricted content</p>
                    </div>
                    <Switch
                        id="nsfw_content"
                        v-model="nsfwContent"
                        @update:model-value="(val) => togglePreference('nsfw_content', val)"
                    />
                </div>
            </div>
        </div>
    </form>
</template>

<script setup>
import {Head, useForm, usePage} from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue'
import SettingsSubHeader from '@/Components/Settings/SettingsSubHeader.vue'
import AvatarModal from '@/Profile/AvatarModal.vue'
import {computed, ref} from "vue";
import BaseAlert from "@/Components/BaseAlert.vue";
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch/index.js'
import { toast } from 'vue-sonner'

const props = defineProps({
    errors: Object,
    flash: Object,
})
const form = useForm('post', route('settings.update-profile.update'), {
    name: usePage().props.user.name,
    email: usePage().props.user.email,
});
const file = ref(null)
const previewUrl = ref(null)
const avatarModal = ref(null)

const nsfwContent = ref(usePage().props.user.preferences?.nsfw_content ?? false)

function togglePreference(key, value) {
    router.post(route('settings.preferences.update'), { key, value }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Preference saved'),
        onError: () => toast.error('Failed to save preference'),
    })
}

function onFileChange(e) {
    file.value = e.target.files[0]
    previewUrl.value = URL.createObjectURL(file.value)
    avatarModal.value.open = true
    URL.revokeObjectURL(file.value)
}

function submitForm() {
    form.post(
        route('settings.update-profile.update')
    )
}

const showEmailVerify = computed(() => {
    return props.flash.message === 'emailVerify'
})

const showEmailTooMany = computed(() => {
    return props.flash.message === 'emailTooMany'
})
</script>
<script>
import AccountLayout from "@/Layouts/AccountLayout.vue";

export default {
    layout: AccountLayout,
}
</script>
