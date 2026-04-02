<template>
    <Head title="Edit Profile" />
    <AvatarModal ref="avatarModal" :file="file" :url="previewUrl" />

    <div class="mb-4" v-if="showEmailVerify || showEmailTooMany">
        <BaseAlert
            v-if="showEmailVerify"
            message="We have sent you a verification email to confirm your change."
            title="Please check your email."
        />
        <BaseAlert
            v-if="showEmailTooMany"
            message="Please try again in 15 minutes."
            title="Too many requests"
        />
    </div>

    <form @submit.prevent="submitForm">
        <div class="space-y-5">
            <!-- Avatar row -->
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 shrink-0 rounded-full overflow-hidden bg-gray-100">
                    <AvatarImage class="w-full h-full" :avatar="$page.props.user.avatar" />
                </div>
                <div>
                    <div class="relative">
                        <label for="avatar" class="inline-flex cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Change avatar
                        </label>
                        <input
                            id="avatar"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            name="avatar"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg"
                            @change="onFileChange($event)"
                        />
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('profile_avatar_notice') }}</p>
                    <span v-show="errors.image" class="text-xs text-destructive">{{ errors.image }}</span>
                </div>
            </div>

            <!-- Email -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                <label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300 sm:w-32 sm:shrink-0">{{ $t('email') }}</label>
                <div class="flex-1">
                    <Input id="email"
                        placeholder="me@example.org"
                        type="email"
                        autocomplete="email"
                        @change="form.validate('email')"
                        :class="{ 'border-destructive': form.invalid('email') }"
                        v-model.trim.lazy="form.email"
                    />
                    <p v-if="form.invalid('email')" class="mt-1 text-sm text-destructive">{{ form.errors.email }}</p>
                </div>
            </div>

            <!-- Username -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                <label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300 sm:w-32 sm:shrink-0">Username</label>
                <div class="flex-1">
                    <Input id="name"
                        type="text"
                        autocomplete="name"
                        @change="form.validate('name')"
                        :class="{ 'border-destructive': form.invalid('name') }"
                        v-model.trim.lazy="form.name"
                        aria-describedby="name-help"
                    />
                    <small id="name-help" class="mt-1 block text-xs text-gray-500 dark:text-gray-400">This is not your badge name.</small>
                    <p v-if="form.invalid('name')" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</p>
                </div>
            </div>

            <div class="flex justify-end">
                <Button :disabled="form.processing" type="submit">{{ $t('save') }}</Button>
            </div>
        </div>
    </form>

    <!-- Preferences -->
    <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Preferences</h3>
        <div class="flex items-center justify-between">
            <div>
                <label for="nsfw_content" class="text-sm font-medium text-gray-700 dark:text-gray-300">NSFW Content</label>
                <p class="text-xs text-gray-500 dark:text-gray-400">Allow display of age-restricted content</p>
            </div>
            <Switch
                id="nsfw_content"
                v-model="nsfwContent"
                @update:model-value="(val) => togglePreference('nsfw_content', val)"
            />
        </div>
    </div>
</template>

<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import AvatarModal from '@/Profile/AvatarModal.vue'
import { computed, ref } from 'vue'
import BaseAlert from '@/Components/BaseAlert.vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch/index.js'

const props = defineProps({
    errors: Object,
    flash: Object,
})

const form = useForm('post', route('settings.update-profile.update'), {
    name: usePage().props.user.name,
    email: usePage().props.user.email,
})

const file = ref(null)
const previewUrl = ref(null)
const avatarModal = ref(null)

const nsfwContent = ref(usePage().props.user.preferences?.nsfw_content ?? false)

function togglePreference(key, value) {
    router.post(route('settings.preferences.update'), { key, value }, {
        preserveScroll: true,
    })
}

function onFileChange(e) {
    file.value = e.target.files[0]
    previewUrl.value = URL.createObjectURL(file.value)
    avatarModal.value.open = true
    URL.revokeObjectURL(file.value)
}

function submitForm() {
    form.post(route('settings.update-profile.update'))
}

const showEmailVerify = computed(() => props.flash.message === 'emailVerify')
const showEmailTooMany = computed(() => props.flash.message === 'emailTooMany')
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
