<script setup>
import {Link, router, usePage} from '@inertiajs/vue3'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu'
import AvatarImage from '../../Pages/Profile/AvatarImage.vue'
import {computed} from 'vue'
import { ChevronDown, ChevronLeft } from 'lucide-vue-next'

const props = defineProps({
    logout: Function,
})

const user = computed(() => usePage().props.user)

const profileNavMenu = [
    {
        name: 'profile',
        route: route('settings.profile'),
    },
    {
        name: 'change_password',
        route: route('settings.update-password'),
    },
    {
        name: 'two_factor_auth',
        route: route('settings.two-factor'),
    },
]

const goBackOnePage = () => {
    window.history.length > 1 ? router.visit(window.history.go(-1)) : router.visit('/dashboard');
}

const hideNavigationButtons = () => {
    // check if current window.location.href contains /dashboard then hide
    return !window.location.href.includes('/dashboard')
}

function logout() {
    window.location.href = '/auth/logout'
}
</script>

<template>
    <div class="relative">
        <!-- Profile dropdown -->
        <DropdownMenu>
            <div class="flex justify-between items-center gap-3">
                <div
                    v-if="hideNavigationButtons()"
                    @click="goBackOnePage"
                    class="flex items-center gap-1 cursor-pointer dark:text-primary-200 dark:hover:text-primary-400 text-primary-600 hover:text-primary-800 rounded">
                    <ChevronLeft class="w-4 pt-1"></ChevronLeft>
                    <div>
                        Back
                    </div>
                </div>
                <div v-else></div>
                <div class="flex items-center gap-3">
                    <div class="text-gray-800 dark:text-primary-200">{{ user.name }}</div>
                    <DropdownMenuTrigger as-child>
                        <button type="button" class="flex items-center gap-2">
                            <span class="sr-only">Open user menu</span>
                            <AvatarImage
                                :avatar="$page.props.user.avatar"
                                class="h-10 w-10 shadow drop-shadow rounded-full"
                            />
                            <div>
                                <ChevronDown
                                    class="fill text-primary-700 dark:text-primary-400 h-4"></ChevronDown>
                            </div>
                        </button>
                    </DropdownMenuTrigger>
                </div>
            </div>
            <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuItem
                    v-for="item in profileNavMenu"
                    :key="item"
                    as-child
                >
                    <Link
                        class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                        :href="item.route"
                    >{{ $t(item.name) }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                    <a
                        class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                        href="#"
                        @click="logout"
                    >{{ $t('logout') }}
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>

<style scoped></style>
