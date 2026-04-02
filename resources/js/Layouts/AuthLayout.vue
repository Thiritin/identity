<template>
    <div :class="{ dark: darkMode }">
        <div class="bg-primary-600 auth-background relative min-h-screen dark:text-primary-300">
            <!-- Centered Content -->
            <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-8">
                <div class="flex flex-col items-center gap-4">
                    <div class="w-full max-w-md rounded-xl bg-white/90 p-8 shadow-2xl backdrop-blur-sm dark:bg-primary-900/90 sm:p-12">
                        <!-- Slot Content -->
                        <div class="flex-1 w-full flex flex-col justify-center">
                            <Transition mode="out-in" appear>
                                <div :key="$page.url">
                                    <slot name="header">
                                        <AuthHeader v-if="!$page.props.hideUserInfo && user" class="mb-8"></AuthHeader>
                                    </slot>
                                    <slot></slot>
                                </div>
                            </Transition>
                        </div>
                        <!-- Footer Content -->
                        <AuthFooter
                            class="pt-8"
                            :navigation="navigation"
                            :dark-mode="darkMode"
                            :toggle-dark-mode="toggleDarkMode"
                        />
                    </div>
                    <!-- Artist Notice -->
                    <div class="text-sm text-primary-200">
                        Artwork by
                        <a class="hover:underline" href="https://www.furaffinity.net/user/jukajo">Jukajo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import {usePage} from "@inertiajs/vue3";

const user = usePage().props.user;
</script>
<script>
import AuthFooter from "../Components/Auth/AuthFooter.vue";
import AuthHeader from "../Components/Auth/AuthHeader.vue";
import {usePage} from "@inertiajs/vue3";

export default {
    components: {AuthHeader, AuthFooter},
    data() {
        return {
            animated: true,
            darkMode: this.$cookies.isKey('darkMode'),
            navigation: {
                main: [
                    {
                        name: 'Create Account',
                        link: route('auth.login.view'),
                        newTab: false,
                        visible: () => usePage().props.user === null,
                    },

                    {
                        name: 'Support',
                        href: 'https://help.eurofurence.org/contact/',
                        newTab: true,
                        visible: () => true,
                    },

                    {
                        name: 'Imprint',
                        href: 'https://help.eurofurence.org/legal/imprint',
                        newTab: true,
                        visible: () => true,
                    },

                    {
                        name: 'Privacy',
                        href: 'https://help.eurofurence.org/legal/privacy',
                        newTab: true,
                        visible: () => true,
                    },
                ],
            },
        }
    },
    mounted() {
        this.animated = true;
        this.dark = 'dark:text-primary-300 dark:bg-primary-900';
    },
    methods: {
        toggleDarkMode() {
            if (this.darkMode === false) {
                this.$cookies.set('darkMode', 'true', 2147483647);
            }

            if (this.darkMode === true) {
                this.$cookies.remove('darkMode');
            }

            this.darkMode = !this.darkMode;
        },
    }
}
</script>
<style>

.v-enter-active,
.v-leave-active {
    transition: opacity 0.2s ease-in-out;
}

.v-enter-from,
.v-leave-to {
    opacity: 0;
}

.auth-background {
    background-repeat: no-repeat;
    background-size: cover;
    background-image: url('../../assets/fantastic_furry_festival.jpg');
}

.page * {
    @apply transition-colors;
}

</style>
