<template>
    <div>
        <Sheet v-model:open="sidebarOpen">
            <SheetContent side="left" class="w-72 p-0 lg:hidden">
                <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-primary-600 px-6 pb-2 h-full">
                    <div class="flex h-16 shrink-0 items-center gap-3">
                        <img class="h-10 w-auto" src="../../assets/ef.svg"
                             alt="Eurofurence"/>
                        <div>
                            <div class='font-medium font-sm text-primary-100'>Staff Dashboard</div>
                        </div>
                    </div>
                    <nav class="flex flex-1 flex-col">
                        <ul role="list" class="flex flex-1 flex-col gap-y-7">
                            <li>
                                <ul role="list" class="-mx-2 space-y-1">
                                    <li>
                                        <StaffMainMenu :navigation="navigation"></StaffMainMenu>
                                    </li>
                                    <li v-if="teams.length">
                                        <StaffTeamMenu :teams="teams"></StaffTeamMenu>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div
                class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 text-primary-100 bg-primary-600 px-6">
                <div class="flex h-16 shrink-0 items-center gap-3">
                    <img class="h-10 w-auto" src="../../assets/ef.svg"
                         alt="Eurofurence"/>
                    <div>
                        <div class='font-medium font-sm dark:text-primary-300'>Staff Dashboard</div>
                    </div>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <StaffMainMenu :navigation="navigation"></StaffMainMenu>
                        </li>
                        <li>
                            <StaffTeamMenu v-if="teams.length" :teams="teams"></StaffTeamMenu>
                        </li>
                        <li class="-mx-6 mt-auto">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
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
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" side="top" class="w-48">
                                    <DropdownMenuItem
                                        v-for="item in profileNavMenu"
                                        :key="item"
                                        as-child
                                    >
                                        <Link
                                            class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                                            :href="item.route"
                                        >{{ $trans(item.name) }}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <a
                                            class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                                            :href="route('login.apps.logout',{app: 'staff'})"
                                        >{{ $trans('logout') }}
                                        </a>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="sticky top-0 z-40 flex items-center gap-x-6 bg-white px-4 py-4 shadow-sm sm:px-6 lg:hidden">
            <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <MenuIcon class="h-6 w-6" aria-hidden="true"/>
            </button>
            <div class="flex-1 text-sm font-semibold leading-6 text-gray-900">Dashboard</div>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <a href="#">
                        <span class="sr-only">Your profile</span>
                        <img class="h-8 w-8 rounded-full bg-gray-50"
                             :src="$page.props.user.avatar"
                             :alt="$page.props.user.name"/>
                    </a>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem
                        v-for="item in profileNavMenu"
                        :key="item"
                        as-child
                    >
                        <Link
                            class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                            :href="item.route"
                        >{{ $trans(item.name) }}
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <a
                            class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300"
                            :href="route('auth.logout')"
                        >{{ $trans('logout') }}
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <main class="lg:pl-72">
            <slot></slot>
            <Toaster />
        </main>
    </div>
</template>

<script setup>
import {computed, reactive, ref} from 'vue'
import { Menu as MenuIcon, Home, Users, X } from 'lucide-vue-next'
import { Sheet, SheetContent } from '@/Components/ui/sheet'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu'
import {Link, usePage} from "@inertiajs/vue3";
import StaffMainMenu from "../Components/Staff/Menu/StaffMainMenu.vue";
import StaffTeamMenu from "../Components/Staff/Menu/StaffTeamMenu.vue";
import { Toaster } from '@/Components/ui/sonner'

function updateNavigation() {
    navigation.forEach(item => {
        item.current = route().current(item.href)
    })

}

let navigationSource = reactive([
    {
        name: 'Dashboard',
        href: route('staff.dashboard'),
        icon: Home,
        currentEval: () => route().current('staff.dashboard')
    },
    {
        name: 'Departments',
        href: route('staff.groups.index'),
        icon: Users,
        currentEval: () => route().current('staff.groups.index')
    },
]);

const navigation = computed(() => {
    // set current based on currentEval
    // Current page
    let page = usePage().url;
    return navigationSource.map(item => {
        item.current = item.currentEval()
        return item
    })
})

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

const teams = computed(() => {
    const departments = usePage().props.user.departments;
    const page = usePage();
    return departments.map(department => {
        return {
            name: department.name,
            href: route('staff.groups.show', {group: department.hashid}),
            current: page.url.startsWith('/staff/groups/' + department.hashid),
            initial: department.name.split(' ').map(word => word.charAt(0)).join('')
        }
    })
})

const sidebarOpen = ref(false)
</script>
