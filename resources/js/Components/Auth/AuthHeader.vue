<script setup>
    import { Link } from '@inertiajs/vue3'
    import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
    import AvatarImage from '../../Pages/Profile/AvatarImage.vue'
</script>

<template>
    <div class="flex justify-end">
        <!-- Profile dropdown -->
        <Menu as="div">
            <div>
                <MenuButton
                    class="max-w-xs shadow-lg drop-shadow-lg bg-primary-800 rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-primary-800 focus:ring-white"
                >
                    <span class="sr-only">Open user menu</span>
                    <AvatarImage
                        :avatar="$page.props.user.avatar"
                        class="h-8 w-8 rounded-full"
                    />
                </MenuButton>
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
                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg drop-shadow py-1 bg-primary-50 dark:bg-primary-600 ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                    <MenuItem
                        v-for="item in profile"
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
