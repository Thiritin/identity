<template>
    <Head title="Profile" />
    <AvatarModal ref="avatarModal" :file="file" :url="previewUrl" />

    <!-- Profile hero (glassy) -->
    <div class="relative overflow-hidden bg-white/40 dark:bg-white/5 backdrop-blur-xl rounded-t-xl px-6 py-5 sm:px-10">
        <!-- Staff gradient overlay -->
        <div v-if="$page.props.user.isStaff" class="absolute inset-0 bg-gradient-to-t from-amber-500/25 to-transparent pointer-events-none" />
        <!-- Staff badge -->
        <span v-if="$page.props.user.isStaff" class="absolute top-3 right-3 bg-amber-500 text-white text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md shadow-sm">
            Staff
        </span>
        <div class="relative flex items-center gap-4">
            <!-- Avatar (clickable, overlapping left) -->
            <div class="relative group cursor-pointer shrink-0 -ml-2" @click="triggerAvatarUpload">
                <div class="h-16 w-16 rounded-full overflow-hidden ring-3 ring-white/50 dark:ring-white/15 shadow-lg">
                    <AvatarImage class="w-full h-full" :avatar="$page.props.user.avatar" />
                </div>
                <div class="absolute inset-0 rounded-full bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center">
                    <Camera class="h-5 w-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
                <input
                    ref="avatarInput"
                    class="hidden"
                    type="file"
                    accept="image/png,image/jpeg,image/jpg"
                    @change="onFileChange($event)"
                />
            </div>

            <!-- Name + member since -->
            <div class="min-w-0 flex-1">
                <div class="group flex items-center gap-2">
                    <template v-if="!editingName">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ form.name || 'Username' }}
                        </h2>
                        <button
                            type="button"
                            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-md hover:bg-black/10 dark:hover:bg-white/10 shrink-0"
                            @click="startEditingName"
                        >
                            <Pencil class="h-3.5 w-3.5 text-gray-500 dark:text-gray-400" />
                        </button>
                    </template>
                    <template v-else>
                        <form @submit.prevent="submitName" class="flex items-center gap-2">
                            <Input
                                ref="nameInput"
                                type="text"
                                v-model.trim="form.name"
                                class="text-base font-semibold h-8 w-48"
                                @keydown.escape="cancelEditingName"
                            />
                            <Button size="sm" type="submit" :disabled="form.processing" class="h-8 w-8 p-0">
                                <Check class="h-3.5 w-3.5" />
                            </Button>
                            <Button size="sm" variant="ghost" type="button" @click="cancelEditingName" class="h-8 w-8 p-0">
                                <X class="h-3.5 w-3.5" />
                            </Button>
                        </form>
                    </template>
                </div>
                <p v-if="form.invalid('name')" class="text-sm text-destructive">{{ form.errors.name }}</p>
                <p v-else class="text-xs text-gray-700 dark:text-gray-200">
                    Member since {{ $page.props.user.memberSince }}
                </p>
                <span v-show="errors.image" class="text-xs text-destructive">{{ errors.image }}</span>
            </div>
        </div>
    </div>

    <!-- Preferences -->
    <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10">
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
import { nextTick, ref } from 'vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch/index.js'
import { Camera, Pencil, Check, X } from 'lucide-vue-next'

const props = defineProps({
    errors: Object,
})

const page = usePage()

const form = useForm('post', route('settings.update-profile.update'), {
    name: page.props.user.name,
})

const file = ref(null)
const previewUrl = ref(null)
const avatarModal = ref(null)
const avatarInput = ref(null)
const nameInput = ref(null)
const editingName = ref(false)
const originalName = ref(page.props.user.name)

function triggerAvatarUpload() {
    avatarInput.value.click()
}

function onFileChange(e) {
    file.value = e.target.files[0]
    previewUrl.value = URL.createObjectURL(file.value)
    avatarModal.value.open = true
    URL.revokeObjectURL(file.value)
}

function startEditingName() {
    originalName.value = form.name
    editingName.value = true
    nextTick(() => {
        nameInput.value?.$el?.focus()
    })
}

function cancelEditingName() {
    form.name = originalName.value
    form.clearErrors('name')
    editingName.value = false
}

function submitName() {
    form.post(route('settings.update-profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editingName.value = false
            originalName.value = form.name
        },
    })
}

const nsfwContent = ref(page.props.user.preferences?.nsfw_content ?? false)

function togglePreference(key, value) {
    router.post(route('settings.preferences.update'), { key, value }, {
        preserveScroll: true,
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
