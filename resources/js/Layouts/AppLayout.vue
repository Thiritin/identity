<template>
    <div class="min-h-screen bg-gray-100">
        <div class="bg-primary-600 pb-32">
            <Disclosure v-slot="{ open }" as="nav" class="bg-primary-600">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="border-b border-primary-700">
                        <div class="
                                flex
                                items-center
                                justify-between
                                h-16
                                px-4
                                sm:px-0
                            ">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <Logo class="w-14"></Logo>
                                </div>
                                <div class="text-white font-bold ml-2">
                                    EF Identity
                                </div>
                                <div class="hidden md:block">
                                    <div class="
                                            ml-10
                                            flex
                                            items-baseline
                                            space-x-4
                                        ">
                                        <template v-for="(
                                                item, itemIdx
                                            ) in navigation" :key="item">
                                            <template v-if="item.route === $page.url">
                                                <InertiaLink class="
                                                        bg-primary-800
                                                        text-white
                                                        px-3
                                                        py-2
                                                        rounded-md
                                                        text-sm
                                                        font-medium
                                                    " href="#">{{ $trans(item.name) }}
                                                </InertiaLink>
                                            </template>
                                            <InertiaLink v-else :href="item.route" class="
                                                    text-primary-300
                                                    hover:bg-primary-700
                                                    hover:text-white
                                                    px-3
                                                    py-2
                                                    rounded-md
                                                    text-sm
                                                    font-medium
                                                ">{{ $trans(item.name) }}
                                            </InertiaLink>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden md:block">
                                <div class="ml-4 flex items-center md:ml-6">
                                    <!-- Profile dropdown -->
                                    <Menu as="div" class="ml-3 relative">
                                        <div>
                                            <MenuButton class="
                                                    max-w-xs
                                                    bg-primary-800
                                                    rounded-full
                                                    flex
                                                    items-center
                                                    text-sm
                                                    focus:outline-none
                                                    focus:ring-2
                                                    focus:ring-offset-2
                                                    focus:ring-offset-primary-800
                                                    focus:ring-white
                                                ">
                                                <span class="sr-only">Open user menu</span>
                                                <AvatarImage class="h-8 w-8 rounded-full"/>
                                            </MenuButton>
                                        </div>
                                        <transition enter-active-class="transition ease-out duration-100"
                                                    enter-from-class="transform opacity-0 scale-95"
                                                    enter-to-class="transform opacity-100 scale-100"
                                                    leave-active-class="transition ease-in duration-75"
                                                    leave-from-class="transform opacity-100 scale-100"
                                                    leave-to-class="transform opacity-0 scale-95">
                                            <MenuItems class="
                                                    origin-top-right
                                                    absolute
                                                    right-0
                                                    mt-2
                                                    w-48
                                                    rounded-md
                                                    shadow-lg
                                                    py-1
                                                    bg-white
                                                    ring-1
                                                    ring-black
                                                    ring-opacity-5
                                                    focus:outline-none
                                                ">
                                                <MenuItem v-for="item in profile" :key="item" v-slot="{ active }">
                                                    <InertiaLink :class="[
                                                            active
                                                                ? 'bg-primary-100'
                                                                : '',
                                                            'block px-4 py-2 text-sm text-primary-700',
                                                        ]" :href="route(item.route)">{{ $trans(item.name) }}
                                                    </InertiaLink>
                                                </MenuItem>
                                                <MenuItem>
                                                    <a class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-100"
                                                       href="#" @click="logout">{{
                                                            $trans('logout')
                                                        }} </a>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                            </div>
                            <div class="-mr-2 flex md:hidden">
                                <!-- Mobile menu button -->
                                <DisclosureButton class="
                                        bg-primary-800
                                        inline-flex
                                        items-center
                                        justify-center
                                        p-2
                                        rounded-md
                                        text-primary-400
                                        hover:text-white hover:bg-primary-700
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-offset-2
                                        focus:ring-offset-primary-800
                                        focus:ring-white
                                    ">
                                    <span class="sr-only">Open main menu</span>
                                    <MenuIcon v-if="!open" aria-hidden="true" class="block h-6 w-6"/>
                                    <XIcon v-else aria-hidden="true" class="block h-6 w-6"/>
                                </DisclosureButton>
                            </div>
                        </div>
                    </div>
                </div>

                <DisclosurePanel class="border-b border-primary-700 md:hidden">
                    <div class="px-2 py-3 space-y-1 sm:px-3">
                        <template v-for="(item, itemIdx) in navigation" :key="item">
                            <template v-if="item.route === $page.url">
                                <!-- Current: "bg-primary-900 text-white", Default: "text-primary-300 hover:bg-primary-700 hover:text-white" -->
                                <a class="
                                        bg-primary-900
                                        text-white
                                        block
                                        px-3
                                        py-2
                                        rounded-md
                                        text-base
                                        font-medium
                                    " href="#">{{ $trans(item.name) }}</a>
                            </template>
                            <a v-else class="
                                    text-primary-300
                                    hover:bg-primary-700 hover:text-white
                                    block
                                    px-3
                                    py-2
                                    rounded-md
                                    text-base
                                    font-medium
                                " href="#">{{ $trans(item.name) }}</a>
                        </template>
                    </div>
                    <div class="pt-4 pb-3 border-t border-primary-700">
                        <div class="flex items-center px-5">
                            <div class="flex-shrink-0">
                                <AvatarImage class="h-10 w-10 rounded-full"/>
                            </div>
                            <div class="ml-3">
                                <div class="
                                        text-base
                                        font-medium
                                        leading-none
                                        text-white
                                    ">
                                    {{ user.name }}
                                </div>
                                <div class="
                                        text-sm
                                        font-medium
                                        leading-none
                                        text-primary-400
                                    ">
                                    {{ user.email }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 px-2 space-y-1">
                            <InertiaLink v-for="item in profile" :key="item" :href="route(item.route)" class="
                                    block
                                    px-3
                                    py-2
                                    rounded-md
                                    text-base
                                    font-medium
                                    text-primary-400
                                    hover:text-white hover:bg-primary-700
                                ">{{ $trans(item.name) }}
                            </InertiaLink>
                            <a class="block
                                    px-3
                                    py-2
                                    rounded-md
                                    text-base
                                    font-medium
                                    text-primary-400
                                    hover:text-white hover:bg-primary-700" href="#" @click="logout">{{
                                    $trans('logout')
                                }} </a>
                        </div>
                    </div>
                </DisclosurePanel>
            </Disclosure>
            <header class="py-10">
                <slot name="header">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h1 class="text-3xl font-bold text-white">Identity</h1>
                    </div>
                </slot>
            </header>
        </div>

        <main class="-mt-32">
            <div class="max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">
                <transition name="page">
                    <div v-if="animated">
                        <slot></slot>
                    </div>
                </transition>
            </div>
        </main>
    </div>
</template>

<script>
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems,} from '@headlessui/vue'
import {BellIcon, MenuIcon, XIcon} from '@heroicons/vue/outline'
import Logo from '@/Auth/Logo'
import {usePage} from '@inertiajs/inertia-vue3'
import {computed} from 'vue'
import AvatarImage from "@/Pages/Profile/AvatarImage";

const navigation = [
    {
        name: 'dashboard',
        route: '/dashboard',
    },
]
const profile = [
    {
        name: 'profile',
        route: 'profile',
    },
    {
        name: 'security',
        route: 'security',
    },

]

export default {
    components: {
        AvatarImage,
        Logo,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        BellIcon,
        MenuIcon,
        XIcon,
    },

    data() {
        return {
            animated: false
        }
    },

    setup() {
        const user = computed(() => usePage().props.value.user)
        return {
            user,
            navigation,
            profile,
        }
    },

    mounted() {
        this.animated = true;
    },

    methods: {
        logout() {
            this.$inertia.post(route('auth.logout'))
        }
    },
}
</script>
<style>
.page-enter-active {
    transition: opacity .1s ease-in;
    opacity: 0;
}

.page-enter {
    opacity: 0;
}

.page-enter-to {
    opacity: 1;
}
</style>
