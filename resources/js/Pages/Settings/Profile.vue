<template>
    <Head :title="$t('edit_profile')" />
    <AvatarModal ref="avatarModal" :file="file" :url="previewUrl" />

    <!-- Profile hero (glassy) -->
    <div class="relative overflow-hidden bg-white/40 dark:bg-white/5 backdrop-blur-xl rounded-t-xl px-6 py-5 sm:px-10">
        <!-- Staff/role gradient overlay -->
        <div v-if="roleBadge" :class="['absolute inset-0 bg-gradient-to-t to-transparent pointer-events-none', roleBadge.gradientClass]" />
        <!-- Role badge -->
        <span v-if="roleBadge" :class="['absolute top-3 right-3 text-white text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md shadow-sm', roleBadge.pillClass]">
            {{ $t(roleBadge.label) }}
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
                        <DevHashid :id="$page.props.user.id" />
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

    <!-- Your Departments (staff only, above staff profile form) -->
    <div v-if="$page.props.user.isStaff && $page.props.user.departments?.length > 0"
         class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
        <div class="grid md:grid-cols-3 gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('directory_your_departments') }}</h3>
            </div>
            <div class="md:col-span-2 space-y-2">
                <Link
                    v-for="dept in $page.props.user.departments"
                    :key="dept.hashid"
                    :href="route('directory.show', dept.slug)"
                    class="flex items-center justify-between px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                >
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ dept.name }}</span>
                        <span v-if="dept.title" class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ dept.title }}</span>
                    </div>
                    <Badge v-if="dept.level && dept.level !== 'member'" variant="secondary" class="text-xs capitalize">
                        {{ dept.level.replace(/_/g, ' ') }}
                    </Badge>
                </Link>
            </div>
        </div>
    </div>

    <!-- Staff Profile sections (staff only) -->
    <template v-if="$page.props.user.isStaff && staffForm">
        <StaffProfileConsentGate :consent="staffProfile.consent" @grant="grantStaffProfileConsent">
            <div v-if="staffProfile?.consent?.granted" class="px-6 py-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                {{ $t('staff_profile_consent_state_granted', {
                    date: new Date(staffProfile.consent.granted_at).toLocaleDateString(),
                    version: staffProfile.consent.version
                }) }}
                — <Link :href="route('settings.mydata')" class="underline">{{ $t('staff_profile_consent_state_heading') }}</Link>
            </div>

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
                                    <label for="pronouns" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('staff_profile_pronouns') }}</label>
                                    <VisibilityPicker field="pronouns" />
                                </div>
                                <Input id="pronouns" v-model="staffForm.pronouns" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.pronouns" class="text-xs text-destructive mt-1">{{ staffForm.errors.pronouns }}</p>
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
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="staffForm.processing">{{ $t('save') }}</Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Address -->
            <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_address') }}</h3>
                        <div class="mt-2">
                            <VisibilityPicker field="address" />
                        </div>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        <div>
                            <label for="address_line1" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_address_line1') }}</label>
                            <Input id="address_line1" v-model="staffForm.address_line1" class="w-full bg-white dark:bg-primary-950" />
                            <p v-if="staffForm.errors.address_line1" class="text-xs text-destructive mt-1">{{ staffForm.errors.address_line1 }}</p>
                        </div>
                        <div>
                            <label for="address_line2" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_address_line2') }}</label>
                            <Input id="address_line2" v-model="staffForm.address_line2" class="w-full bg-white dark:bg-primary-950" />
                            <p v-if="staffForm.errors.address_line2" class="text-xs text-destructive mt-1">{{ staffForm.errors.address_line2 }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="postal_code" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_postal_code') }}</label>
                                <Input id="postal_code" v-model="staffForm.postal_code" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.postal_code" class="text-xs text-destructive mt-1">{{ staffForm.errors.postal_code }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="city" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_city') }}</label>
                                <Input id="city" v-model="staffForm.city" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.city" class="text-xs text-destructive mt-1">{{ staffForm.errors.city }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="country" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_country') }}</label>
                            <Input id="country" v-model="staffForm.country" maxlength="2" class="w-full bg-white dark:bg-primary-950 uppercase" placeholder="DE" />
                            <p v-if="staffForm.errors.country" class="text-xs text-destructive mt-1">{{ staffForm.errors.country }}</p>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="staffForm.processing">{{ $t('save') }}</Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Emergency Contact -->
            <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_emergency_contact') }}</h3>
                        <div class="mt-2">
                            <VisibilityPicker field="emergency_contact" />
                        </div>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        <div>
                            <label for="emergency_contact_name" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_emergency_contact_name') }}</label>
                            <Input id="emergency_contact_name" v-model="staffForm.emergency_contact_name" class="w-full bg-white dark:bg-primary-950" />
                            <p v-if="staffForm.errors.emergency_contact_name" class="text-xs text-destructive mt-1">{{ staffForm.errors.emergency_contact_name }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="emergency_contact_phone" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_emergency_contact_phone') }}</label>
                                <Input id="emergency_contact_phone" type="tel" v-model="staffForm.emergency_contact_phone" class="w-full bg-white dark:bg-primary-950" />
                                <p v-if="staffForm.errors.emergency_contact_phone" class="text-xs text-destructive mt-1">{{ staffForm.errors.emergency_contact_phone }}</p>
                            </div>
                            <div>
                                <label for="emergency_contact_telegram" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_emergency_contact_telegram') }}</label>
                                <Input id="emergency_contact_telegram" v-model="staffForm.emergency_contact_telegram" class="w-full bg-white dark:bg-primary-950" placeholder="@handle" />
                                <p v-if="staffForm.errors.emergency_contact_telegram" class="text-xs text-destructive mt-1">{{ staffForm.errors.emergency_contact_telegram }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="staffForm.processing">{{ $t('save') }}</Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Kenntnisse (Skills) -->
            <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_skills') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_skills_description') }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_spoken_languages') }}</label>
                            <Popover v-model:open="languagePopoverOpen">
                                <PopoverTrigger as-child>
                                    <button type="button" class="flex w-full items-center justify-between rounded-md border border-input bg-white dark:bg-primary-950 px-3 py-2 text-sm shadow-xs hover:bg-accent hover:text-accent-foreground">
                                        <div v-if="staffForm.spoken_languages.length > 0" class="flex flex-wrap gap-1.5">
                                            <span
                                                v-for="(code, i) in staffForm.spoken_languages"
                                                :key="code"
                                                class="inline-flex items-center gap-1 rounded-md bg-primary/10 text-primary px-2 py-0.5 text-xs font-medium"
                                            >
                                                {{ languageMap[code] || code }}
                                                <button type="button" class="hover:text-destructive" @click.stop="staffForm.spoken_languages.splice(i, 1)">
                                                    <X class="h-3 w-3" />
                                                </button>
                                            </span>
                                        </div>
                                        <span v-else class="text-muted-foreground">{{ $t('staff_profile_search_language') }}</span>
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent class="w-[--reka-popover-trigger-width] p-0" align="start">
                                    <Command :multiple="true" v-model="staffForm.spoken_languages">
                                        <CommandInput :placeholder="$t('staff_profile_search_language')" />
                                        <CommandList class="max-h-48">
                                            <CommandEmpty>{{ $t('staff_profile_no_language_found') }}</CommandEmpty>
                                            <CommandGroup>
                                                <CommandItem v-for="lang in availableLanguages" :key="lang.code" :value="lang.code">
                                                    <Check class="h-4 w-4 mr-2" :class="staffForm.spoken_languages.includes(lang.code) ? 'opacity-100' : 'opacity-0'" />
                                                    {{ lang.name }}
                                                    <span class="ml-auto text-xs text-muted-foreground">{{ lang.code }}</span>
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="staffForm.errors.spoken_languages" class="text-xs text-destructive mt-1">{{ staffForm.errors.spoken_languages }}</p>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="staffForm.processing">{{ $t('save') }}</Button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Section: Convention Attendance -->
        <div v-if="$page.props.user.isStaff" class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('convention_attendance') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('convention_attendance_description') }}</p>

                </div>
                <div class="md:col-span-2">
                    <ConventionAttendanceEditor
                        :attendance="conventionAttendance"
                        :all-conventions="allConventions"
                        :endpoint="route('settings.staff-profile.conventions')"
                    />
                </div>
            </div>
        </div>

        <!-- Section: ConBook Credits -->
        <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
            <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('staff_profile_conbook_credits') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('staff_profile_conbook_credits_description') }}</p>
                </div>
                <div class="md:col-span-2 space-y-4">
                    <form @submit.prevent="submitCreditAs">
                        <div class="space-y-4">
                            <div>
                                <label for="credit_as" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('staff_profile_credit_as_default') }}</label>
                                <Input id="credit_as" v-model="creditAsForm.credit_as" class="w-full bg-white dark:bg-primary-950" />
                            </div>

                            <!-- Per-group override toggle -->
                            <div v-if="groupMemberships && groupMemberships.length > 0" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <Checkbox id="custom_per_group" v-model="showPerGroupCredits" />
                                    <label for="custom_per_group" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                        {{ $t('staff_profile_custom_per_group') }}
                                    </label>
                                </div>

                                <div v-if="showPerGroupCredits" class="overflow-x-auto">
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
                                                    <Input v-model="creditAsForm.groups[index].credit_as" :placeholder="creditAsForm.credit_as || $t('staff_profile_credit_as')" class="h-8 w-full max-w-48 bg-white dark:bg-primary-950" />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="creditAsForm.processing">{{ $t('save') }}</Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </StaffProfileConsentGate>
    </template>

    <!-- Section: Telegram -->
    <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
        <div class="grid md:grid-cols-3 md:items-center gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('telegram_connect_title') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('telegram_connect_description') }}</p>
            </div>
            <div class="md:col-span-2">
                <!-- Linked state -->
                <div v-if="telegramState.linked" class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <TelegramIcon class="h-4 w-4 text-[#26A5E4]" />
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $t('telegram_linked_as', { username: telegramState.username }) }}</span>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="disconnectTelegram">
                        {{ $t('telegram_disconnect') }}
                    </Button>
                </div>

                <!-- Linking in progress -->
                <div v-else-if="telegramState.linking" class="space-y-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('telegram_or_send_code') }}:</p>
                    <div class="flex items-center gap-3">
                        <code class="text-lg font-mono font-bold tracking-widest bg-gray-100 dark:bg-primary-950 px-4 py-2 rounded-md">{{ telegramState.code }}</code>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button type="button" as="a" :href="telegramState.deepLink" target="_blank" size="sm">
                            <TelegramIcon class="h-4 w-4 mr-1.5" />
                            {{ $t('telegram_open_bot') }}
                        </Button>
                        <Button type="button" variant="ghost" size="sm" @click="cancelLinking">
                            {{ $t('cancel') }}
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ $t('telegram_code_expires') }}</p>
                </div>

                <!-- Not linked -->
                <div v-else>
                    <Button type="button" variant="outline" @click="startTelegramLink">
                        <TelegramIcon class="h-4 w-4 mr-1.5" />
                        {{ $t('telegram_connect') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Preferences -->
    <div class="bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-6 sm:px-10 border-t border-gray-200/50 dark:border-primary-800/50">
        <div class="grid md:grid-cols-3 gap-6 md:gap-10">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('preferences') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('preferences_description') }}</p>
            </div>
            <div class="md:col-span-2 space-y-5">
                <!-- Language -->
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('preferences_language_label') }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $t('preferences_language_description') }}</p>
                    <Select :model-value="currentLocale" @update:model-value="(val) => savePreference('locale', val)">
                        <SelectTrigger class="w-full max-w-xs bg-white dark:bg-primary-950">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="lang in uiLanguages" :key="lang.code" :value="lang.code">
                                {{ lang.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Theme -->
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('preferences_theme_label') }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $t('preferences_theme_description') }}</p>
                    <div class="flex gap-2">
                        <Button
                            v-for="opt in themeOptions"
                            :key="opt.value"
                            type="button"
                            :variant="currentTheme === opt.value ? 'default' : 'outline'"
                            size="sm"
                            class="gap-1.5"
                            @click="savePreference('theme', opt.value)"
                        >
                            <component :is="opt.icon" class="h-4 w-4" />
                            {{ $t(opt.label) }}
                        </Button>
                    </div>
                </div>

                <!-- NSFW -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="nsfw_content" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('preferences_nsfw_label') }}</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('preferences_nsfw_description') }}</p>
                    </div>
                    <Switch
                        id="nsfw_content"
                        v-model="nsfwContent"
                        @update:model-value="(val) => savePreference('nsfw_content', val)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import AvatarModal from '@/Profile/AvatarModal.vue'
import { computed, h, nextTick, onUnmounted, ref } from 'vue'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuLabel,
    DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { Checkbox } from '@/Components/ui/checkbox'
import { Badge } from '@/Components/ui/badge'
import { Camera, Pencil, Check, X, Globe, Users, Shield, Lock, Sun, Moon, Monitor, ChevronsUpDown } from 'lucide-vue-next'
import DevHashid from '@/Components/DevHashid.vue'
import StaffProfileConsentGate from '@/Components/StaffProfileConsentGate.vue'

const TelegramIcon = (props, { attrs }) => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', ...attrs }, [
    h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })
])
import ConventionAttendanceEditor from '@/Components/ConventionAttendanceEditor.vue'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
    errors: Object,
    staffProfile: Object,
    groupMemberships: Array,
    conventionAttendance: Array,
    allConventions: Array,
    availableLanguages: Array,
    telegram: Object,
})

const page = usePage()

const roleBadge = computed(() => {
    const u = page.props.user
    if (!u) return null
    if (u.isDivisionDirector) return { label: 'role_division_director', pillClass: 'bg-red-600', gradientClass: 'from-red-600/25' }
    if (u.isDirector) return { label: 'role_director', pillClass: 'bg-red-600', gradientClass: 'from-red-600/25' }
    if (u.isTeamLead) return { label: 'role_staff_team_lead', pillClass: 'bg-amber-500', gradientClass: 'from-amber-500/25' }
    if (u.isStaff) return { label: 'staff', pillClass: 'bg-amber-500', gradientClass: 'from-amber-500/25' }
    return null
})

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

// Telegram linking
const telegramState = ref({
    linking: false,
    code: null,
    deepLink: null,
    linked: props.telegram?.linked ?? false,
    username: props.telegram?.username ?? null,
})

let pollInterval = null

async function startTelegramLink() {
    const response = await fetch(route('settings.telegram.generate-code'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json',
        },
    })
    const data = await response.json()
    telegramState.value.code = data.code
    telegramState.value.deepLink = data.deep_link
    telegramState.value.linking = true
    startPolling()
}

function startPolling() {
    stopPolling()
    pollInterval = setInterval(async () => {
        try {
            const response = await fetch(route('settings.telegram.status'), {
                headers: { 'Accept': 'application/json' },
            })
            const data = await response.json()
            if (data.linked) {
                telegramState.value.linked = true
                telegramState.value.username = data.telegram_username
                telegramState.value.linking = false
                stopPolling()
            }
        } catch (e) {
            // Silently retry on next interval
        }
    }, 3000)
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
    }
}

function cancelLinking() {
    telegramState.value.linking = false
    stopPolling()
}

function disconnectTelegram() {
    fetch(route('settings.telegram.disconnect'), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json',
        },
    }).then(() => {
        telegramState.value.linked = false
        telegramState.value.username = null
    })
}

onUnmounted(() => stopPolling())

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

function grantStaffProfileConsent() {
    router.post(route('settings.staff-profile.consent.grant'), {}, {
        preserveScroll: true,
    })
}

const nsfwContent = ref(page.props.user.preferences?.nsfw_content ?? false)
const currentLocale = ref(page.props.locale ?? 'en')
const currentTheme = ref(page.props.user.preferences?.theme ?? 'system')

const uiLanguages = [
    { code: 'en', name: 'English' },
    { code: 'de', name: 'Deutsch' },
    { code: 'fr', name: 'Français' },
]

const themeOptions = [
    { value: 'system', label: 'preferences_theme_system', icon: Monitor },
    { value: 'light', label: 'preferences_theme_light', icon: Sun },
    { value: 'dark', label: 'preferences_theme_dark', icon: Moon },
]

function savePreference(key, value) {
    if (key === 'locale') {
        currentLocale.value = value
    } else if (key === 'theme') {
        currentTheme.value = value
    }

    router.post(route('settings.preferences.update'), { key, value }, {
        preserveScroll: true,
        onSuccess: () => {
            if (key === 'locale') {
                window.location.reload()
            }
        },
    })
}

const visibilityOptions = [
    { value: 'all_staff', label: 'staff_profile_visibility_all_staff', icon: Globe },
    { value: 'my_departments', label: 'staff_profile_visibility_my_departments', icon: Users },
    { value: 'leads_and_directors', label: 'staff_profile_visibility_leads_and_directors', icon: Shield },
    { value: 'directors_only', label: 'staff_profile_visibility_directors_only', icon: Lock },
]

const visibilityDefaults = page.props.staffProfileVisibilityDefaults ?? {}

function getVisibilityOption(field) {
    const value = getVisibility(field)
    return visibilityOptions.find(o => o.value === value) ?? visibilityOptions[0]
}

const staffForm = page.props.user.isStaff ? useForm({
    firstname: props.staffProfile?.firstname ?? null,
    lastname: props.staffProfile?.lastname ?? null,
    pronouns: props.staffProfile?.pronouns ?? null,
    birthdate: props.staffProfile?.birthdate ?? null,
    phone: props.staffProfile?.phone ?? null,
    address_line1: props.staffProfile?.address_line1 ?? null,
    address_line2: props.staffProfile?.address_line2 ?? null,
    city: props.staffProfile?.city ?? null,
    postal_code: props.staffProfile?.postal_code ?? null,
    country: props.staffProfile?.country ?? null,
    emergency_contact_name: props.staffProfile?.emergency_contact_name ?? null,
    emergency_contact_phone: props.staffProfile?.emergency_contact_phone ?? null,
    emergency_contact_telegram: props.staffProfile?.emergency_contact_telegram ?? null,
    spoken_languages: props.staffProfile?.spoken_languages ?? [],
    visibility: props.staffProfile?.visibility ?? {},
}) : null

function submitStaffProfile() {
    staffForm.post(route('settings.staff-profile.update'), { preserveScroll: true })
}

const creditAsForm = page.props.user.isStaff ? useForm({
    credit_as: props.staffProfile?.credit_as ?? null,
    groups: (props.groupMemberships ?? []).map(g => ({ group_id: g.id, credit_as: g.credit_as ?? '' })),
}) : null

const showPerGroupCredits = ref(
    (props.groupMemberships ?? []).some(g => g.credit_as)
)

function submitCreditAs() {
    creditAsForm.post(route('settings.staff-profile.credit-as'), { preserveScroll: true })
}

const languageMap = Object.fromEntries((props.availableLanguages ?? []).map(l => [l.code, l.name]))
const languagePopoverOpen = ref(false)

function getVisibility(field) {
    return staffForm.visibility[field] ?? visibilityDefaults[field] ?? 'all_staff'
}
function setVisibility(field, value) { staffForm.visibility[field] = value }

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
