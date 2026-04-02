<template>
    <Head :title="$t('edit_profile')" />
    <AvatarModal ref="avatarModal" :file="file" :url="previewUrl" />

    <!-- Profile hero (glassy) -->
    <div class="relative overflow-hidden bg-white/40 dark:bg-white/5 backdrop-blur-xl rounded-t-xl px-6 py-5 sm:px-10">
        <!-- Staff gradient overlay -->
        <div v-if="$page.props.user.isStaff" class="absolute inset-0 bg-gradient-to-t from-amber-500/25 to-transparent pointer-events-none" />
        <!-- Staff badge -->
        <span v-if="$page.props.user.isStaff" class="absolute top-3 right-3 bg-amber-500 text-white text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md shadow-sm">
            {{ $t('staff') }}
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
                    {{ $t('member_since', { date: $page.props.user.memberSince }) }}
                </p>
                <span v-show="errors.image" class="text-xs text-destructive">{{ errors.image }}</span>
            </div>
        </div>
    </div>

    <!-- Preferences -->
    <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $t('preferences') }}</h3>
        <div class="flex items-center justify-between">
            <div>
                <label for="nsfw_content" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('preferences_nsfw_label') }}</label>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('preferences_nsfw_description') }}</p>
            </div>
            <Switch
                id="nsfw_content"
                v-model="nsfwContent"
                @update:model-value="(val) => togglePreference('nsfw_content', val)"
            />
        </div>
    </div>

    <!-- Staff Profile (staff only) -->
    <template v-if="$page.props.user.isStaff && staffForm">
        <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ $t('staff_profile') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">{{ $t('staff_profile_personal') }}</p>

            <form @submit.prevent="submitStaffProfile" class="space-y-5">
                <!-- Firstname / Lastname -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="firstname" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_firstname') }}</label>
                            <Select :model-value="getVisibility('firstname')" @update:model-value="v => setVisibility('firstname', v)">
                                <SelectTrigger class="h-6 w-auto text-xs gap-1 px-2">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opt in visibilityOptions" :key="opt.value" :value="opt.value">{{ $t(opt.label) }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Input id="firstname" v-model="staffForm.firstname" class="w-full" />
                        <p v-if="staffForm.errors.firstname" class="text-xs text-destructive mt-1">{{ staffForm.errors.firstname }}</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="lastname" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_lastname') }}</label>
                            <Select :model-value="getVisibility('lastname')" @update:model-value="v => setVisibility('lastname', v)">
                                <SelectTrigger class="h-6 w-auto text-xs gap-1 px-2">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opt in visibilityOptions" :key="opt.value" :value="opt.value">{{ $t(opt.label) }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Input id="lastname" v-model="staffForm.lastname" class="w-full" />
                        <p v-if="staffForm.errors.lastname" class="text-xs text-destructive mt-1">{{ staffForm.errors.lastname }}</p>
                    </div>
                </div>

                <!-- Birthdate -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="birthdate" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_birthdate') }}</label>
                        <Select :model-value="getVisibility('birthdate')" @update:model-value="v => setVisibility('birthdate', v)">
                            <SelectTrigger class="h-6 w-auto text-xs gap-1 px-2">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in visibilityOptions" :key="opt.value" :value="opt.value">{{ $t(opt.label) }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Input id="birthdate" type="date" v-model="staffForm.birthdate" class="w-full" />
                    <p v-if="staffForm.errors.birthdate" class="text-xs text-destructive mt-1">{{ staffForm.errors.birthdate }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="phone" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_phone') }}</label>
                        <Select :model-value="getVisibility('phone')" @update:model-value="v => setVisibility('phone', v)">
                            <SelectTrigger class="h-6 w-auto text-xs gap-1 px-2">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in visibilityOptions" :key="opt.value" :value="opt.value">{{ $t(opt.label) }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Input id="phone" type="tel" v-model="staffForm.phone" class="w-full" />
                    <p v-if="staffForm.errors.phone" class="text-xs text-destructive mt-1">{{ staffForm.errors.phone }}</p>
                </div>

                <!-- Telegram (read-only) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_telegram') }}</label>
                        <Select :model-value="getVisibility('telegram')" @update:model-value="v => setVisibility('telegram', v)">
                            <SelectTrigger class="h-6 w-auto text-xs gap-1 px-2">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in visibilityOptions" :key="opt.value" :value="opt.value">{{ $t(opt.label) }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex items-center h-9 px-3 rounded-md border border-input bg-muted text-sm text-muted-foreground">
                        {{ staffProfile?.telegram_username ? '@' + staffProfile.telegram_username : $t('staff_profile_telegram_not_linked') }}
                    </div>
                </div>

                <!-- Eurofurence History -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $t('staff_profile_history') }}</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_first_ef') }}</label>
                            <Select v-model="staffForm.first_eurofurence">
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="$t('staff_profile_select_ef')" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="edition in eurofurenceEditions" :key="edition.number" :value="String(edition.number)">
                                        Eurofurence {{ edition.number }} ({{ edition.year }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="staffForm.errors.first_eurofurence" class="text-xs text-destructive mt-1">{{ staffForm.errors.first_eurofurence }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_first_year_staff') }}</label>
                            <Select v-model="staffForm.first_year_staff">
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="$t('staff_profile_select_year')" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="year in staffYears" :key="year" :value="String(year)">
                                        {{ year }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="staffForm.errors.first_year_staff" class="text-xs text-destructive mt-1">{{ staffForm.errors.first_year_staff }}</p>
                        </div>
                    </div>
                </div>

                <!-- Credit & Languages -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $t('staff_profile_credit_languages') }}</h4>
                    <div class="space-y-4">
                        <div>
                            <label for="credit_as" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_credit_as_default') }}</label>
                            <Input id="credit_as" v-model="staffForm.credit_as" class="w-full" />
                            <p v-if="staffForm.errors.credit_as" class="text-xs text-destructive mt-1">{{ staffForm.errors.credit_as }}</p>
                        </div>
                        <div>
                            <label for="spoken_languages" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_spoken_languages') }}</label>
                            <Input id="spoken_languages" :model-value="staffForm.spoken_languages.join(', ')" @change="e => staffForm.spoken_languages = e.target.value.split(',').map(s => s.trim()).filter(Boolean)" class="w-full" placeholder="en, de, fr" />
                            <p v-if="staffForm.errors.spoken_languages" class="text-xs text-destructive mt-1">{{ staffForm.errors.spoken_languages }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="staffForm.processing">{{ $t('save') }}</Button>
                </div>
            </form>
        </div>

        <!-- Per-Group Credit As -->
        <div v-if="groupMemberships && groupMemberships.length > 0" class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $t('staff_profile_group_credit') }}</h3>

            <form @submit.prevent="submitCreditAs">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-2 font-medium">{{ $t('staff_profile_group_name') }}</th>
                                <th class="pb-2 font-medium">{{ $t('staff_profile_group_role') }}</th>
                                <th class="pb-2 font-medium">{{ $t('staff_profile_group_credit_as') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(group, index) in groupMemberships" :key="group.id" class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-4">{{ group.name }}</td>
                                <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ group.title || group.level }}</td>
                                <td class="py-2">
                                    <Input
                                        v-model="creditAsForm.groups[index].credit_as"
                                        :placeholder="staffForm?.credit_as || $t('staff_profile_credit_as')"
                                        class="h-8 w-full max-w-48"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-4">
                    <Button type="submit" :disabled="creditAsForm.processing">{{ $t('save') }}</Button>
                </div>
            </form>
        </div>
    </template>
</template>

<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import AvatarModal from '@/Profile/AvatarModal.vue'
import { computed, nextTick, ref } from 'vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { Camera, Pencil, Check, X } from 'lucide-vue-next'

const props = defineProps({
    errors: Object,
    staffProfile: Object,
    groupMemberships: Array,
    eurofurenceEditions: Array,
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

const visibilityOptions = [
    { value: 'all_staff', label: 'staff_profile_visibility_all_staff' },
    { value: 'my_departments', label: 'staff_profile_visibility_my_departments' },
    { value: 'leads_and_directors', label: 'staff_profile_visibility_leads_and_directors' },
    { value: 'directors_only', label: 'staff_profile_visibility_directors_only' },
]

const staffForm = page.props.user.isStaff ? useForm({
    firstname: props.staffProfile?.firstname ?? null,
    lastname: props.staffProfile?.lastname ?? null,
    birthdate: props.staffProfile?.birthdate ?? null,
    phone: props.staffProfile?.phone ?? null,
    spoken_languages: props.staffProfile?.spoken_languages ?? [],
    credit_as: props.staffProfile?.credit_as ?? null,
    first_eurofurence: props.staffProfile?.first_eurofurence?.toString() ?? null,
    first_year_staff: props.staffProfile?.first_year_staff?.toString() ?? null,
    visibility: props.staffProfile?.visibility ?? {},
}) : null

function submitStaffProfile() {
    staffForm.post(route('settings.staff-profile.update'), {
        preserveScroll: true,
    })
}

const creditAsForm = page.props.user.isStaff ? useForm({
    groups: (props.groupMemberships ?? []).map(g => ({
        group_id: g.id,
        credit_as: g.credit_as ?? '',
    })),
}) : null

function submitCreditAs() {
    creditAsForm.post(route('settings.staff-profile.credit-as'), {
        preserveScroll: true,
    })
}

function getVisibility(field) {
    return staffForm.visibility[field] ?? 'all_staff'
}

function setVisibility(field, value) {
    staffForm.visibility[field] = value
}

const staffYears = computed(() => {
    if (!props.eurofurenceEditions) return []
    return [...new Set(props.eurofurenceEditions.map(e => e.year))].sort((a, b) => a - b)
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
