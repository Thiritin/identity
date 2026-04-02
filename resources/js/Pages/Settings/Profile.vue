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
            <div class="relative group cursor-pointer shrink-0 -ml-2" @click="triggerAvatarUpload">
                <div class="h-16 w-16 rounded-full overflow-hidden ring-3 ring-white/50 dark:ring-white/15 shadow-lg">
                    <AvatarImage class="w-full h-full" :avatar="$page.props.user.avatar" />
                </div>
                <div class="absolute inset-0 rounded-full bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center">
                    <Camera class="h-5 w-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
                <input ref="avatarInput" class="hidden" type="file" accept="image/png,image/jpeg,image/jpg" @change="onFileChange($event)" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="group flex items-center gap-2">
                    <template v-if="!editingName">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">{{ form.name || 'Username' }}</h2>
                        <button type="button" class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-md hover:bg-black/10 dark:hover:bg-white/10 shrink-0" @click="startEditingName">
                            <Pencil class="h-3.5 w-3.5 text-gray-500 dark:text-gray-400" />
                        </button>
                    </template>
                    <template v-else>
                        <form @submit.prevent="submitName" class="flex items-center gap-2">
                            <Input ref="nameInput" type="text" v-model.trim="form.name" class="text-base font-semibold h-8 w-48" @keydown.escape="cancelEditingName" />
                            <Button size="sm" type="submit" :disabled="form.processing" class="h-8 w-8 p-0"><Check class="h-3.5 w-3.5" /></Button>
                            <Button size="sm" variant="ghost" type="button" @click="cancelEditingName" class="h-8 w-8 p-0"><X class="h-3.5 w-3.5" /></Button>
                        </form>
                    </template>
                </div>
                <p v-if="form.invalid('name')" class="text-sm text-destructive">{{ form.errors.name }}</p>
                <p v-else class="text-xs text-gray-700 dark:text-gray-200">{{ $t('member_since', { date: $page.props.user.memberSince }) }}</p>
                <span v-show="errors.image" class="text-xs text-destructive">{{ errors.image }}</span>
            </div>
        </div>
    </div>

    <!-- Staff Profile sections (staff only) -->
    <template v-if="$page.props.user.isStaff && staffForm">
        <form @submit.prevent="submitStaffProfile">

            <!-- Section: Personal Information -->
            <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10">
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_personal') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_description') }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="firstname" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_firstname') }}</label>
                                    <VisibilityPicker field="firstname" />
                                </div>
                                <Input id="firstname" v-model="staffForm.firstname" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.firstname" class="text-xs text-destructive mt-1">{{ staffForm.errors.firstname }}</p>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="lastname" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_lastname') }}</label>
                                    <VisibilityPicker field="lastname" />
                                </div>
                                <Input id="lastname" v-model="staffForm.lastname" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.lastname" class="text-xs text-destructive mt-1">{{ staffForm.errors.lastname }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="birthdate" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_birthdate') }}</label>
                                    <VisibilityPicker field="birthdate" />
                                </div>
                                <Input id="birthdate" type="date" v-model="staffForm.birthdate" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.birthdate" class="text-xs text-destructive mt-1">{{ staffForm.errors.birthdate }}</p>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="phone" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_phone') }}</label>
                                    <VisibilityPicker field="phone" />
                                </div>
                                <Input id="phone" type="tel" v-model="staffForm.phone" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.phone" class="text-xs text-destructive mt-1">{{ staffForm.errors.phone }}</p>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_telegram') }}</label>
                                <VisibilityPicker field="telegram" />
                            </div>
                            <Button type="button" variant="outline" class="w-full justify-start font-normal text-muted-foreground" disabled>
                                <MessageCircle class="h-4 w-4 mr-2 opacity-50" />
                                {{ staffProfile?.telegram_username ? '@' + staffProfile.telegram_username : $t('staff_profile_telegram_not_linked') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Eurofurence History -->
            <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_history') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_history_description') }}</p>
                    </div>
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_first_ef') }}</label>
                            <Select v-model="staffForm.first_eurofurence">
                                <SelectTrigger class="w-full bg-white dark:bg-primary-950">
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
                                <SelectTrigger class="w-full bg-white dark:bg-primary-950">
                                    <SelectValue :placeholder="$t('staff_profile_select_year')" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="year in staffYears" :key="year" :value="String(year)">{{ year }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="staffForm.errors.first_year_staff" class="text-xs text-destructive mt-1">{{ staffForm.errors.first_year_staff }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Credit & Languages -->
            <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_credit_languages') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_credit_description') }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        <div>
                            <label for="credit_as" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_credit_as_default') }}</label>
                            <Input id="credit_as" v-model="staffForm.credit_as" class="w-full bg-white dark:bg-primary-950" />
                            <p v-if="staffForm.errors.credit_as" class="text-xs text-destructive mt-1">{{ staffForm.errors.credit_as }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_spoken_languages') }}</label>
                            <div class="flex flex-wrap items-center gap-1.5 min-h-9 px-3 py-1.5 rounded-md border border-input bg-white dark:bg-primary-950 text-sm shadow-xs">
                                <span
                                    v-for="(lang, i) in staffForm.spoken_languages"
                                    :key="i"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary/10 text-primary px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ lang }}
                                    <button type="button" class="hover:text-destructive" @click="staffForm.spoken_languages.splice(i, 1)">
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                                <input
                                    class="flex-1 min-w-16 bg-transparent outline-none text-sm placeholder:text-muted-foreground"
                                    :placeholder="staffForm.spoken_languages.length === 0 ? 'en, de, fr...' : ''"
                                    @keydown.enter.prevent="addLanguage($event)"
                                    @keydown.,.prevent="addLanguage($event)"
                                    @keydown.backspace="removeLastLanguage($event)"
                                />
                            </div>
                            <p v-if="staffForm.errors.spoken_languages" class="text-xs text-destructive mt-1">{{ staffForm.errors.spoken_languages }}</p>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="staffForm.processing">{{ $t('save') }}</Button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Section: Per-Group Credit As -->
        <div v-if="groupMemberships && groupMemberships.length > 0" class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_group_credit') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_group_credit_description') }}</p>
                </div>
                <div class="md:col-span-2">
                    <form @submit.prevent="submitCreditAs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                        <th class="pb-2 font-medium">{{ $t('staff_profile_group_name') }}</th>
                                        <th class="pb-2 font-medium">{{ $t('staff_profile_group_credit_as') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(group, index) in groupMemberships" :key="group.id" class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-4">{{ group.name }}</td>
                                        <td class="py-2">
                                            <Input v-model="creditAsForm.groups[index].credit_as" :placeholder="staffForm?.credit_as || $t('staff_profile_credit_as')" class="h-8 w-full max-w-48 bg-white dark:bg-primary-950" />
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
            </div>
        </div>
    </template>

    <!-- Section: Preferences -->
    <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
        <div class="grid md:grid-cols-3 gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('preferences') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('preferences_description') }}</p>
            </div>
            <div class="md:col-span-2 space-y-4">
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
        </div>
    </div>
</template>

<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import AvatarModal from '@/Profile/AvatarModal.vue'
import { computed, h, nextTick, ref } from 'vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuLabel,
    DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { Camera, Pencil, Check, X, Globe, Users, Shield, Lock, MessageCircle } from 'lucide-vue-next'
import { trans } from 'laravel-vue-i18n'

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

function triggerAvatarUpload() { avatarInput.value.click() }

function onFileChange(e) {
    file.value = e.target.files[0]
    previewUrl.value = URL.createObjectURL(file.value)
    avatarModal.value.open = true
    URL.revokeObjectURL(file.value)
}

function startEditingName() {
    originalName.value = form.name
    editingName.value = true
    nextTick(() => { nameInput.value?.$el?.focus() })
}

function cancelEditingName() {
    form.name = originalName.value
    form.clearErrors('name')
    editingName.value = false
}

function submitName() {
    form.post(route('settings.update-profile.update'), {
        preserveScroll: true,
        onSuccess: () => { editingName.value = false; originalName.value = form.name },
    })
}

const nsfwContent = ref(page.props.user.preferences?.nsfw_content ?? false)

function togglePreference(key, value) {
    router.post(route('settings.preferences.update'), { key, value }, { preserveScroll: true })
}

const visibilityOptions = [
    { value: 'all_staff', label: 'staff_profile_visibility_all_staff', icon: Globe },
    { value: 'my_departments', label: 'staff_profile_visibility_my_departments', icon: Users },
    { value: 'leads_and_directors', label: 'staff_profile_visibility_leads_and_directors', icon: Shield },
    { value: 'directors_only', label: 'staff_profile_visibility_directors_only', icon: Lock },
]

function getVisibilityOption(field) {
    const value = staffForm.visibility[field] ?? 'all_staff'
    return visibilityOptions.find(o => o.value === value) ?? visibilityOptions[0]
}

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
    staffForm.post(route('settings.staff-profile.update'), { preserveScroll: true })
}

const creditAsForm = page.props.user.isStaff ? useForm({
    groups: (props.groupMemberships ?? []).map(g => ({ group_id: g.id, credit_as: g.credit_as ?? '' })),
}) : null

function submitCreditAs() {
    creditAsForm.post(route('settings.staff-profile.credit-as'), { preserveScroll: true })
}

function addLanguage(e) {
    const value = e.target.value.trim().toLowerCase()
    if (value && !staffForm.spoken_languages.includes(value)) {
        staffForm.spoken_languages.push(value)
    }
    e.target.value = ''
}

function removeLastLanguage(e) {
    if (e.target.value === '' && staffForm.spoken_languages.length > 0) {
        staffForm.spoken_languages.pop()
    }
}

function getVisibility(field) { return staffForm.visibility[field] ?? 'all_staff' }
function setVisibility(field, value) { staffForm.visibility[field] = value }

const staffYears = computed(() => {
    if (!props.eurofurenceEditions) return []
    return [...new Set(props.eurofurenceEditions.map(e => e.year))].sort((a, b) => a - b)
})

const VisibilityPicker = (pickerProps) => {
    const field = pickerProps.field
    const opt = getVisibilityOption(field)
    return h(DropdownMenu, null, {
        default: () => [
            h(DropdownMenuTrigger, { asChild: true }, {
                default: () => h('button', {
                    type: 'button',
                    title: `${trans('staff_profile_visibility')}: ${trans(opt.label)}`,
                    class: 'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors',
                }, [
                    h(opt.icon, { class: 'h-3 w-3' }),
                    h('span', { class: 'text-[10px] leading-none' }, trans(opt.label)),
                ]),
            }),
            h(DropdownMenuContent, { align: 'end', class: 'w-52' }, {
                default: () => [
                    h(DropdownMenuLabel, null, { default: () => trans('staff_profile_visibility') }),
                    h(DropdownMenuSeparator),
                    h(DropdownMenuRadioGroup, {
                        modelValue: getVisibility(field),
                        'onUpdate:modelValue': (v) => setVisibility(field, v),
                    }, {
                        default: () => visibilityOptions.map(o =>
                            h(DropdownMenuRadioItem, { key: o.value, value: o.value }, {
                                default: () => [h(o.icon, { class: 'h-4 w-4 mr-2 text-muted-foreground' }), trans(o.label)],
                            })
                        ),
                    }),
                ],
            }),
        ],
    })
}
VisibilityPicker.props = { field: String }
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
