<template>
    <div :class="{ dark: darkMode }">
        <div class="auth-background bg-primary-600 relative min-h-screen">
            <div class="relative z-10 flex min-h-screen justify-center px-4 py-10 sm:py-14">
                <div class="flex flex-col items-center w-full max-w-3xl">
                    <!-- Folder Tabs + Card -->
                    <div class="w-full">
                        <!-- Tabs row -->
                        <div class="flex px-2">
                            <Link
                                v-for="tab in tabs"
                                :key="tab.name"
                                :href="route(tab.route)"
                                class="relative px-6 py-3 text-center text-sm font-semibold transition-all rounded-t-xl -mb-px"
                                :class="isActive(tab.route)
                                    ? 'bg-white/95 text-primary-800 z-10 dark:bg-primary-900/95 dark:text-primary-100'
                                    : 'bg-black/20 text-white/80 hover:bg-black/30 hover:text-white backdrop-blur-sm'"
                            >
                                <component :is="tab.icon" class="mx-auto mb-1 h-5 w-5" />
                                {{ tab.name }}
                            </Link>
                            <!-- Spacer -->
                            <div class="flex-1" />
                            <!-- Right-side tabs -->
                            <Link
                                v-if="user.isStaff"
                                :href="route('staff.dashboard')"
                                class="relative px-6 py-3 text-center text-sm font-semibold transition-all rounded-t-xl -mb-px bg-black/20 text-white/80 hover:bg-black/30 hover:text-white backdrop-blur-sm"
                            >
                                <component :is="BriefcaseBusiness" class="mx-auto mb-1 h-5 w-5" />
                                Staff
                            </Link>
                            <a
                                :href="route('auth.logout')"
                                class="relative px-6 py-3 text-center text-sm font-semibold transition-all rounded-t-xl -mb-px bg-black/20 text-white/80 hover:bg-black/30 hover:text-white backdrop-blur-sm"
                            >
                                <component :is="LogOut" class="mx-auto mb-1 h-5 w-5" />
                                Logout
                            </a>
                        </div>

                        <!-- Content Card -->
                        <div class="w-full rounded-xl rounded-tl-none bg-white/95 px-6 py-8 shadow-2xl backdrop-blur-sm dark:bg-primary-900/95 dark:text-primary-300 sm:px-10 sm:py-10">
                            <slot />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 mt-5 rounded-full bg-black/30 px-5 py-2 text-sm text-white/80 backdrop-blur-sm">
                        <a href="https://help.eurofurence.org/contact/" target="_blank" class="hover:text-white transition-colors">Support</a>
                        <a href="https://help.eurofurence.org/legal/imprint" target="_blank" class="hover:text-white transition-colors">Imprint</a>
                        <a href="https://help.eurofurence.org/legal/privacy" target="_blank" class="hover:text-white transition-colors">Privacy</a>
                    </div>

                    <!-- Artist Notice -->
                    <div class="mt-2 text-sm text-white/80 bg-black/30 px-3 py-1 rounded-full backdrop-blur-sm">
                        Artwork by
                        <a class="hover:underline" href="https://www.furaffinity.net/user/jukajo">Jukajo</a>
                    </div>
                </div>
            </div>
            <Toaster />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { LayoutGrid, UserRound, ShieldCheck, LogOut, BriefcaseBusiness } from 'lucide-vue-next'
import { Toaster } from '@/Components/ui/sonner'

const page = usePage()
const user = computed(() => page.props.user)
const currentUrl = computed(() => page.url)

const tabs = [
    { name: 'Apps', route: 'dashboard', icon: LayoutGrid },
    { name: 'Profile', route: 'settings.profile', icon: UserRound },
    { name: 'Security', route: 'settings.security', icon: ShieldCheck },
]

function isActive(routeName) {
    currentUrl.value
    return route().current(routeName)
}

const darkMode = ref(window.matchMedia('(prefers-color-scheme: dark)').matches)

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    darkMode.value = e.matches
})
</script>

<style>
.auth-background {
    background-repeat: no-repeat;
    background-size: cover;
    background-image: url('../../assets/fantastic_furry_festival.jpg');
}
</style>
