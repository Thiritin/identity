<template>
    <div class='page' :class='{dark: darkMode}'>
        <div class='min-h-screen bg-gray-100 dark:bg-primary-800'>
            <div class='bg-primary-600 dark:bg-primary-900 pb-32'>
                <Disclosure v-slot='{ open }' as='nav'>
                    <div class='max-w-7xl mx-auto sm:px-6 lg:px-8'>
                        <div class='pt-2'>
                            <div class='
                                flex
                                items-center
                                justify-between
                                h-16
                                px-4
                                sm:px-0
                            '>
                                <div class='flex items-center'>
                                    <div class='flex-shrink-0'>
                                        <Logo class='w-14'></Logo>
                                    </div>
                                    <div class='text-white font-bold ml-2'>
                                        EF Identity
                                    </div>
                                    <div class='hidden md:block'>
                                        <div class='
                                            ml-10
                                            flex
                                            items-baseline
                                            space-x-4
                                        '>
                                            <template v-for='(
                                                item, itemIdx
                                            ) in navigation' :key='item'>
                                                <template v-if='item.route === $page.url'>
                                                    <Link class='
                                                        bg-primary-800
                                                        text-white
                                                        px-3
                                                        py-2
                                                        rounded-md
                                                        text-sm
                                                        font-medium
                                                    ' href='#'>{{ $trans(item.name) }}
                                                    </Link>
                                                </template>
                                                <Link v-else-if='item.inertia' :href='item.route' class='
                                                    text-primary-300
                                                    hover:bg-primary-700
                                                    hover:text-white
                                                    px-3
                                                    py-2
                                                    rounded-md
                                                    text-sm
                                                    font-medium
                                                '>{{ $trans(item.name) }}
                                                </Link>
                                                <a v-else :href='item.route' class='
                                                    text-primary-300
                                                    hover:bg-primary-700
                                                    hover:text-white
                                                    px-3
                                                    py-2
                                                    rounded-md
                                                    text-sm
                                                    font-medium
                                                '>{{ $trans(item.name) }}
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class='hidden md:block'>
                                    <div class='ml-4 flex items-center md:ml-6'>
                                        <!-- Profile dropdown -->
                                        <Menu as='div' class='ml-3 relative'>
                                            <div>
                                                <MenuButton class='
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
                                                '>
                                                    <span class='sr-only'>Open user menu</span>
                                                    <AvatarImage :avatar="$page.props.user.avatar"
                                                                 class='h-8 w-8 rounded-full'/>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class='transition ease-out duration-100'
                                                        enter-from-class='transform opacity-0 scale-95'
                                                        enter-to-class='transform opacity-100 scale-100'
                                                        leave-active-class='transition ease-in duration-75'
                                                        leave-from-class='transform opacity-100 scale-100'
                                                        leave-to-class='transform opacity-0 scale-95'>
                                                <MenuItems class='
                                                    origin-top-right
                                                    absolute
                                                    right-0
                                                    mt-2
                                                    w-48
                                                    rounded-md
                                                    shadow-lg
                                                    py-1
                                                    bg-primary-100
                                                    dark:bg-primary-600
                                                    ring-1
                                                    ring-black
                                                    ring-opacity-5
                                                    focus:outline-none
                                                '>
                                                    <MenuItem v-for='item in profile' :key='item' v-slot='{ active }'>
                                                        <Link :class="[
                                                            active
                                                                ? 'bg-primary-800'
                                                                : '',
                                                            'block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300',
                                                        ]" :href='item.route'>{{ $trans(item.name) }}
                                                        </Link>
                                                    </MenuItem>
                                                    <MenuItem>
                                                        <a class='block px-4 py-2 text-sm text-primary-700 hover:bg-primary-200 dark:hover:bg-primary-700 dark:text-primary-300'
                                                           href='#' @click='logout'>{{
                                                                $trans('logout')
                                                            }} </a>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <div class='-mr-2 flex md:hidden'>
                                    <!-- Mobile menu button -->
                                    <DisclosureButton class='
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
                                    '>
                                        <span class='sr-only'>Open main menu</span>
                                        <MenuIcon v-if='!open' aria-hidden='true' class='block h-6 w-6'/>
                                        <XIcon v-else aria-hidden='true' class='block h-6 w-6'/>
                                    </DisclosureButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DisclosurePanel class='border-b border-primary-700 md:hidden'>
                        <div class='px-2 py-3 space-y-1 sm:px-3'>
                            <template v-for='(item, itemIdx) in navigation' :key='item'>
                                <template v-if='item.route === $page.url'>
                                    <!-- Current: "bg-primary-900 text-white", Default: "text-primary-300 hover:bg-primary-700 hover:text-white" -->
                                    <a class='
                                        bg-primary-900
                                        text-white
                                        block
                                        px-3
                                        py-2
                                        rounded-md
                                        text-base
                                        font-medium
                                    ' href='#'>{{ $trans(item.name) }}</a>
                                </template>
                                <a v-else :href='item.route' class='
                                    text-primary-300
                                    hover:bg-primary-700 hover:text-white
                                    block
                                    px-3
                                    py-2
                                    rounded-md
                                    text-base
                                    font-medium
                                '>{{ $trans(item.name) }}</a>
                            </template>
                        </div>
                        <div class='pt-4 pb-3 border-t border-primary-700'>
                            <div class='flex items-center px-5'>
                                <div class='flex-shrink-0'>
                                    <AvatarImage class='h-10 w-10 rounded-full'/>
                                </div>
                                <div class='ml-3'>
                                    <div class='
                                        text-base
                                        font-medium
                                        leading-none
                                        text-white
                                    '>
                                        {{ user.name }}
                                    </div>
                                    <div class='
                                        text-sm
                                        font-medium
                                        leading-none
                                        text-primary-400
                                    '>
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>
                            <div class='mt-3 px-2 space-y-1'>
                                <Link v-for='item in profile' :key='item' :href='item.route' class='
                                    block
                                    px-3
                                    py-2
                                    rounded-md
                                    text-base
                                    font-medium
                                    text-primary-400
                                    hover:text-white hover:bg-primary-700
                                '>{{ $trans(item.name) }}
                                </Link>
                                <a class='block
                                    px-3
                                    py-2
                                    rounded-md
                                    text-base
                                    font-medium
                                    text-primary-400
                                    hover:text-white hover:bg-primary-700' href='#' @click='logout'>{{
                                        $trans('logout')
                                    }} </a>
                            </div>
                        </div>
                    </DisclosurePanel>
                </Disclosure>
                <header class='pt-8 pb-6'>
                    <slot name='header'>
                        <div v-if='title' class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
                            <h1 v-if='title' class='text-3xl font-bold text-white'>{{ title }}</h1>
                            <p v-if='description' class='text-primary-200'>{{ description }}</p>
                        </div>
                    </slot>
                </header>
            </div>

            <main class='-mt-32'>
                <div class='max-w-7xl min-h-[8rem] mx-auto pb-12 px-4 sm:px-6 lg:px-8'>
                    <transition name='page'>
                        <div v-if='animated' :key='$page.url'>
                            <slot></slot>
                        </div>
                    </transition>
                </div>
            </main>

            <footer>
                <div class='mt-7 flex flex-col md:flex-row items-center justify-center gap-1 md:gap-7'>
                    <div v-for='item in footer.nav' :key='item.name'>
                        <Link v-if='item.href === null' :href='item.link'
                              :target="[item.newTab ? '_blank' : '_top']"
                              class='text-base text-gray-500 hover:text-gray-900 dark:hover:text-gray-400'>
                            {{ item.name }}
                        </Link>
                        <a v-else :href='item.href' :target="[item.newTab ? '_blank' : '_top']"
                           class='text-base text-gray-500 hover:text-gray-900 dark:hover:text-gray-400qq'>
                            {{ item.name }} </a>
                    </div>
                </div>
                <span class='flex justify-center mt-3'>
                    <toogleDarkMode :dark-mode='darkMode' :toggle-dark-mode='toggleDarkMode'/>
                </span>
            </footer>
        </div>
    </div>
</template>

<script>
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import {BellIcon, MenuIcon, XIcon} from '@heroicons/vue/outline'
import Logo from '@/Auth/Logo.vue'
import {Link, usePage} from '@inertiajs/vue3'
import {computed} from 'vue'
import AvatarImage from '@/Pages/Profile/AvatarImage.vue'
import ToogleDarkMode from '@/Layouts/ToogleDarkMode.vue'


const profile = [
    {
        name: 'profile',
        route: route('settings.profile'),
    },
]

var navigation = [
    {
        name: 'dashboard',
        route: route('dashboard'),
        inertia: true,
    },
    {
        name: 'groups',
        route: route('groups.index'),
        inertia: true,
    },
    {
        name: 'developers',
        route: route('developers'),
        inertia: true,
    },
    {
        name: 'admin',
        route: '/admin',
        inertia: false,
    },
]

export default {
    components: {
        ToogleDarkMode,
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
        Link,
    },

    props: {
        title: String,
        description: String,
    },

    data() {
        return {
            animated: false,
            darkMode: this.$cookies.isKey('darkMode'),
            footer: {
                nav: [

                    {
                        name: 'Support',
                        href: 'https://help.eurofurence.org/contact/',
                        newTab: true,
                    },

                    {
                        name: 'Imprint',
                        href: 'https://help.eurofurence.org/legal/imprint',
                        newTab: true,
                    },

                    {
                        name: 'Privacy',
                        href: 'https://help.eurofurence.org/legal/privacy',
                        newTab: true,
                    },

                    {
                        name: 'We are looking for help!',
                        href: 'https://www.eurofurence.org/EF27/jobs/',
                        newTab: true,
                    },
                ],
            },
        }
    },

    setup() {
        const user = computed(() => usePage().props.user)
        navigation = navigation.filter(function (value) {
            return (value.name === 'admin' && user.value.isAdmin === true) || value.name !== 'admin'
        })
        return {
            navigation,
            user,
            profile,
        }
    },

    mounted() {
        this.animated = true
    },

    methods: {
        logout() {
            window.location.href = '/oauth2/sessions/logout'
        },
        toggleDarkMode() {
            if (this.darkMode === false) {
                this.$cookies.set('darkMode', 'true', 2147483647)
            }

            if (this.darkMode === true) {
                this.$cookies.remove('darkMode')
            }

            this.darkMode = !this.darkMode
        },
    },
}
</script>

<style>
.page * {
    @apply transition-colors ease-in
}
</style>
