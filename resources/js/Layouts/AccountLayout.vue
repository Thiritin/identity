<template>
    <div :class="{ dark: darkMode }">
        <!-- Desktop: background + folder tabs + card -->
        <div class="hidden md:block auth-background bg-primary-600 relative min-h-screen">
            <div class="relative z-10 flex min-h-screen justify-center px-4 py-10 lg:py-14">
                <div class="flex flex-col items-center w-full max-w-3xl">
                    <!-- Folder Tabs + Card -->
                    <div class="w-full">
                        <div class="flex px-2">
                            <component
                                v-for="tab in tabs"
                                :key="tab.name"
                                :is="tab.external ? 'a' : Link"
                                :href="tab.href"
                                class="relative px-6 py-3 text-center text-sm font-semibold transition-all rounded-t-xl -mb-px"
                                :class="tab.active
                                    ? 'bg-white/95 text-primary-800 z-10 dark:bg-primary-900/95 dark:text-primary-100'
                                    : 'bg-black/20 text-white/80 hover:bg-black/30 hover:text-white backdrop-blur-sm'"
                            >
                                <component :is="tab.icon" class="mx-auto mb-1 h-5 w-5" />
                                {{ tab.name }}
                            </component>
                            <div class="flex-1" />
                            <template v-for="tab in rightTabs" :key="tab.name">
                                <component
                                    :is="tab.external ? 'a' : Link"
                                    :href="tab.href"
                                    class="relative px-6 py-3 text-center text-sm font-semibold transition-all rounded-t-xl -mb-px bg-black/20 text-white/80 hover:bg-black/30 hover:text-white backdrop-blur-sm"
                                >
                                    <component :is="tab.icon" class="mx-auto mb-1 h-5 w-5" />
                                    {{ tab.name }}
                                </component>
                            </template>
                        </div>
                        <div class="w-full rounded-xl rounded-b-none shadow-2xl"
                             :class="isProfile
                                 ? 'bg-transparent'
                                 : 'bg-white/95 backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 px-6 py-8 sm:px-10 sm:py-10'"
                        >
                            <slot />
                        </div>
                    </div>

                    <!-- Footer: artwork left, legal right -->
                    <div class="w-full flex items-center justify-between bg-black/40 backdrop-blur-sm rounded-b-xl px-4 py-2 text-xs text-white/70">
                        <div>
                            {{ $t('footer_artwork_by') }}
                            <a class="hover:underline" href="https://www.furaffinity.net/user/jukajo">Jukajo</a>
                        </div>
                        <nav class="flex flex-wrap gap-x-4 gap-y-1">
                            <a v-for="link in footerLinks" :key="link.name" :href="link.href" target="_blank" class="hover:text-white transition-colors">{{ link.name }}</a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile: clean layout + bottom nav -->
        <div class="md:hidden min-h-screen pb-16 bg-white dark:bg-gray-950 dark:text-gray-300">
            <!-- Mobile content -->
            <div class="px-4 py-6">
                <slot />
            </div>

            <!-- Mobile footer -->
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 px-4 pb-6 text-xs text-gray-400">
                <a v-for="link in footerLinks" :key="link.name" :href="link.href" target="_blank" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">{{ link.name }}</a>
            </div>

            <!-- Bottom Navigation -->
            <nav class="fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 safe-bottom">
                <div class="flex justify-around">
                    <component
                        v-for="tab in tabs"
                        :key="tab.name"
                        :is="tab.external ? 'a' : Link"
                        :href="tab.href"
                        class="flex-1 flex flex-col items-center gap-1 py-2 text-xs font-medium transition-colors"
                        :class="tab.active
                            ? 'text-primary-600 dark:text-primary-400'
                            : 'text-gray-400 dark:text-gray-500'"
                    >
                        <component :is="tab.icon" class="h-5 w-5" />
                        {{ tab.name }}
                    </component>
                    <a
                        :href="route('auth.logout')"
                        class="flex-1 flex flex-col items-center gap-1 py-2 text-xs font-medium text-gray-400 dark:text-gray-500 transition-colors"
                    >
                        <LogOut class="h-5 w-5" />
                        {{ $t('tab_logout') }}
                    </a>
                </div>
            </nav>
        </div>
        <!-- Sudo mode: confirm password modal -->
        <Dialog :open="passwordConfirmRequired">
            <DialogContent :show-close-button="false" @interact-outside.prevent @escape-key-down.prevent>
                <DialogHeader>
                    <DialogTitle>{{ $t('security_confirm_password_title') }}</DialogTitle>
                    <DialogDescription>{{ $t('security_confirm_password_subtitle') }}</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitConfirmPassword">
                    <div class="flex flex-col gap-2">
                        <Input
                            type="password"
                            v-model="confirmForm.password"
                            :placeholder="$t('password')"
                            autocomplete="current-password"
                            :class="{ 'border-destructive': confirmForm.errors.password }"
                        />
                        <p v-if="confirmForm.errors.password" class="text-sm text-destructive">
                            {{ confirmForm.errors.password }}
                        </p>
                    </div>
                    <DialogFooter class="mt-4">
                        <Button type="submit" :disabled="confirmForm.processing">
                            {{ $t('security_confirm_password_submit') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <Toaster rich-colors position="top-center" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, useForm } from '@inertiajs/vue3'
import { LayoutGrid, UserRound, ShieldCheck, LogOut, BriefcaseBusiness, BookUser, Settings } from 'lucide-vue-next'
import { Toaster } from '@/Components/ui/sonner'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Input } from '@/Components/ui/input'
import { Button } from '@/Components/ui/button'
import { trans } from 'laravel-vue-i18n'

const page = usePage()
const user = computed(() => page.props.user)
const currentUrl = computed(() => page.url)

function isActive(routeName) {
    currentUrl.value
    return route().current(routeName)
}

const isProfile = computed(() => isActive('settings.profile'))

const tabs = computed(() => {
    const items = [
        { name: trans('tab_apps'), route: 'dashboard', href: route('dashboard'), icon: LayoutGrid, active: isActive('dashboard') },
        { name: trans('tab_profile'), route: 'settings.profile', href: route('settings.profile'), icon: UserRound, active: isActive('settings.profile') },
        { name: trans('tab_security'), route: 'settings.security', href: route('settings.security'), icon: ShieldCheck, active: isActive('settings.security') || isActive('settings.security.*') },
    ]
    if (user.value.isStaff) {
        items.push({ name: trans('tab_directory'), route: 'staff.dashboard', href: route('staff.dashboard'), icon: BookUser, active: false })
    }
    if (user.value.isAdmin) {
        items.push({ name: trans('tab_admin'), route: null, href: '/admin', icon: Settings, active: false, external: true })
    }
    return items
})

const rightTabs = computed(() => [
    { name: trans('tab_logout'), href: route('auth.logout'), icon: LogOut, external: true },
])

const footerLinks = [
    { name: trans('footer_support_link'), href: 'https://help.eurofurence.org/contact/' },
    { name: trans('footer_imprint'), href: 'https://help.eurofurence.org/legal/imprint' },
    { name: trans('footer_privacy'), href: 'https://help.eurofurence.org/legal/privacy' },
]

const darkMode = ref(window.matchMedia('(prefers-color-scheme: dark)').matches)

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    darkMode.value = e.matches
})

const passwordConfirmRequired = computed(() => page.props.passwordConfirmRequired === true)

const confirmForm = useForm('post', route('settings.security.confirm-password'), {
    password: '',
})

function submitConfirmPassword() {
    confirmForm.submit({
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    })
}
</script>

<style>
.auth-background {
    background-repeat: no-repeat;
    background-size: cover;
    background-image: url('../../assets/fantastic_furry_festival.jpg');
}

.safe-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
