<template>
    <div>
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog as="div" class="relative z-50 lg:hidden" @close="sidebarOpen = false">
                <TransitionChild as="template" enter="transition-opacity ease-linear duration-300"
                                 enter-from="opacity-0" enter-to="opacity-100"
                                 leave="transition-opacity ease-linear duration-300" leave-from="opacity-100"
                                 leave-to="opacity-0">
                    <div class="fixed inset-0 bg-gray-900/80"/>
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild as="template" enter="transition ease-in-out duration-300 transform"
                                     enter-from="-translate-x-full" enter-to="translate-x-0"
                                     leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
                                     leave-to="-translate-x-full">
                        <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                            <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0"
                                             enter-to="opacity-100" leave="ease-in-out duration-300"
                                             leave-from="opacity-100" leave-to="opacity-0">
                                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                                    <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                        <span class="sr-only">Close sidebar</span>
                                        <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true"/>
                                    </button>
                                </div>
                            </TransitionChild>
                            <!-- Sidebar component, swap this element with another sidebar if you like -->
                            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-primary-600 px-6 pb-2">
                                <div class="flex h-16 shrink-0 items-center gap-3">
                                    <img class="h-10 w-auto" src="../../assets/ef.svg"
                                         alt="Eurofurence"/>
                                    <div>
                                        <div class='font-medium font-sm text-primary-100'>StaffNet</div>
                                    </div>
                                </div>
                                <nav class="flex flex-1 flex-col">
                                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                        <li>
                                            <ul role="list" class="-mx-2 space-y-1">
                                                <li>
                                                    <StaffMainMenu :navigation="navigation"></StaffMainMenu>
                                                </li>
                                                <li>
                                                    <StaffTeamMenu :teams="teams"></StaffTeamMenu>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div
                class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 text-primary-100 bg-primary-600 px-6">
                <div class="flex h-16 shrink-0 items-center gap-3">
                    <img class="h-10 w-auto" src="../../assets/ef.svg"
                         alt="Eurofurence"/>
                    <div>
                        <div class='font-medium font-sm dark:text-primary-300'>StaffNet</div>
                    </div>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <StaffMainMenu :navigation="navigation"></StaffMainMenu>
                        </li>
                        <li>
                            <StaffTeamMenu :teams="teams"></StaffTeamMenu>
                        </li>
                        <li class="-mx-6 mt-auto">
                            <Menu as="div">
                                <MenuButton as="div">
                                    <a href="#"
                                       class="flex items-center gap-x-4 px-6 py-3 text-sm font-semibold leading-6 text-primary-100 duration-200 hover:bg-primary-700">
                                        <img
                                            v-if="$page.props.user.avatar"
                                            class="h-8 w-8 rounded-full bg-gray-50"
                                            :src="$page.props.user.avatar"
                                            :alt="$page.props.user.name"/>
                                        <span class="sr-only">Your profile</span>
                                        <span aria-hidden="true">{{ $page.props.user.name }}</span>
                                    </a>
                                </MenuButton>
                                <transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <MenuItems
                                        class="z-50 origin-bottom-right absolute bottom-16 left-4 w-48 rounded-md shadow-lg drop-shadow py-1 bg-white dark:bg-primary-600 ring-1 ring-black ring-opacity-5 focus:outline-none"
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
                                                :href="route('login.apps.logout',{app: 'staff'})"
                                            >{{ $trans('logout') }}
                                            </a>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="sticky top-0 z-40 flex items-center gap-x-6 bg-white px-4 py-4 shadow-sm sm:px-6 lg:hidden">
            <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <Bars3Icon class="h-6 w-6" aria-hidden="true"/>
            </button>
            <div class="flex-1 text-sm font-semibold leading-6 text-gray-900">Dashboard</div>
            <Menu>
                <MenuButton>
                    <a href="#">
                        <span class="sr-only">Your profile</span>
                        <img class="h-8 w-8 rounded-full bg-gray-50"
                             :src="$page.props.user.avatar"
                             :alt="$page.props.user.name"/>
                    </a>
                </MenuButton>
                <transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <MenuItems
                        class="z-50 origin-top-right absolute right-4 mt-48 w-48 rounded-md shadow-lg drop-shadow py-1 bg-white dark:bg-primary-600 ring-1 ring-black ring-opacity-5 focus:outline-none"
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

        <main class="py-10 lg:pl-72">
            <div class="px-4 sm:px-6 lg:px-8 max-w-screen-lg mx-auto">
                <div>
                    <BaseAlert title="This is a WIP Preview!"
                               message="All things here are subject to change or may not be functional."
                               class="mb-4"></BaseAlert>
                </div>
                <slot></slot>
            </div>
        </main>
    </div>
</template>

<script setup>
import {ref} from 'vue'
import {
    Dialog,
    DialogPanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot
} from '@headlessui/vue'
import {Bars3Icon, HomeIcon, UsersIcon, XMarkIcon,} from '@heroicons/vue/24/outline'
import {Link} from "@inertiajs/vue3";
import StaffMainMenu from "../Components/Staff/Menu/StaffMainMenu.vue";
import StaffTeamMenu from "../Components/Staff/Menu/StaffTeamMenu.vue";
import BaseAlert from "../Components/BaseAlert.vue";

const navigation = [
    {name: 'Dashboard', href: route('staff.dashboard'), icon: HomeIcon, current: true, soon: false},
    {name: 'Departments', href: route('staff.departments.index'), icon: UsersIcon, current: false, soon: true},
]


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

const teams = [
    {id: 1, name: 'Registration', href: '#', initial: 'R', current: false},
]

const sidebarOpen = ref(false)
</script>
