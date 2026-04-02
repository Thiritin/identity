<template>
    <div :class="{ dark: darkMode }">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-950 dark:text-gray-300">
            <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <AvatarImage
                            class="h-14 w-14 rounded-full cursor-pointer"
                            :avatar="user.avatar"
                            @click="navigateTo('settings.profile')"
                        />
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ user.name }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            v-if="user.isStaff"
                            :href="route('staff.dashboard')"
                            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                            Staff Portal
                        </Link>
                        <ToogleDarkMode :dark-mode="darkMode" :toggle-dark-mode="toggleDarkMode" />
                        <a
                            :href="route('auth.logout')"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Tabs -->
                <nav class="mb-6 flex overflow-x-auto border-b border-gray-200 dark:border-gray-700" aria-label="Account navigation">
                    <Link
                        v-for="tab in tabs"
                        :key="tab.name"
                        :href="route(tab.route)"
                        class="flex-none whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="isActive(tab.route)
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    >
                        {{ tab.name }}
                    </Link>
                </nav>

                <!-- Content -->
                <main>
                    <slot />
                </main>

                <!-- Footer -->
                <footer class="mt-12 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm text-gray-500">
                    <a href="https://help.eurofurence.org/contact/" target="_blank" class="hover:text-gray-900 dark:hover:text-gray-400">Support</a>
                    <a href="https://help.eurofurence.org/legal/imprint" target="_blank" class="hover:text-gray-900 dark:hover:text-gray-400">Imprint</a>
                    <a href="https://help.eurofurence.org/legal/privacy" target="_blank" class="hover:text-gray-900 dark:hover:text-gray-400">Privacy</a>
                </footer>
            </div>
            <Toaster />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import ToogleDarkMode from '@/Components/ToogleDarkMode.vue'
import { Toaster } from '@/Components/ui/sonner'

const page = usePage()
const user = computed(() => page.props.user)

const tabs = [
    { name: 'Apps', route: 'dashboard' },
    { name: 'Profile', route: 'settings.profile' },
    { name: 'Security', route: 'settings.security' },
]

function isActive(routeName) {
    return route().current(routeName)
}

function navigateTo(routeName) {
    router.visit(route(routeName))
}

const darkMode = ref(document.cookie.includes('darkMode'))

function toggleDarkMode() {
    if (!darkMode.value) {
        document.cookie = 'darkMode=true; max-age=2147483647; path=/'
    } else {
        document.cookie = 'darkMode=; max-age=0; path=/'
    }
    darkMode.value = !darkMode.value
}
</script>
