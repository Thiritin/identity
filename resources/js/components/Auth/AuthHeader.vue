<script setup>
import {Link, router, usePage} from '@inertiajs/vue3'
import {Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import AvatarImage from '../../pages/AvatarImage.vue'
import {computed} from 'vue'
import {ChevronDownIcon, ChevronLeftIcon} from "@heroicons/vue/24/outline"

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
        <Menu as="div">
            <div class="flex justify-between items-center gap-3">
                <div
                    v-if="hideNavigationButtons()"
                    @click="goBackOnePage"
                    class="flex items-center gap-1 cursor-pointer dark:text-primary-200 dark:hover:text-primary-400 text-primary-600 hover:text-primary-800 rounded">
                    <ChevronLeftIcon class="w-4 pt-1"></ChevronLeftIcon>
                    <div>
                        Back
                    </div>
                </div>
                <div v-else></div>
                <div class="flex items-center gap-3">
                    <div class="text-gray-800 dark:text-primary-200">{{ user.name }}</div>
                    <MenuButton>
                        <span class="sr-only">Open user menu</span>
                        <div class="flex items-center gap-2">
                            <AvatarImage
                                :avatar="$page.props.user.avatar"
                                class="h-10 w-10 shadow drop-shadow rounded-full"
                            />
                            <div>
                                <ChevronDownIcon
                                    class="fill text-primary-700 dark:text-primary-400 h-4"></ChevronDownIcon>
                            </div>
                        </div>
                    </MenuButton>
                </div>
            </div>
            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <MenuItems
                    class="z-50 origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg drop-shadow py-1 bg-white dark:bg-primary-600 ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                    <MenuItem
                        v-for="item in profileNavMenu"
                        :key="item"
                        v-slot="{ active }"
                    >
                        <Link
                            :class="[
                                active ? 'bg-primary-800' : '',
                                'block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300',
                            ]"
                            :href="item.route"
                        >{{ $trans(item.name) }}
                        </Link>
                    </MenuItem>
                    <MenuItem>
                        <a
                            class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                            href="#"
                            @click="logout"
                        >{{ $trans('logout') }}
                        </a>
                    </MenuItem>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>

<style scoped></style>
