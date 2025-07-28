<template>
    <VueCookieAcceptDecline
        :disable-decline="true"
        :show-postpone-button="false"
        element-id="cookies"
        position="top"
        transition-name="slideFromTop"
        type="bar"
    >

        <!-- Optional -->
        <template #message>
            {{ $trans('cookie_notice') }}
        </template>

        <!-- Optional -->
        <template #acceptContent>OK</template>
    </VueCookieAcceptDecline>
    <div :class="{ dark: darkMode }">
        <div class="min-h-screen auth-background-layout flex items-center justify-center page dark:text-primary-300 p-4">
            <!-- Page Content -->
            <div class="w-full max-w-lg">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden hover-lift">
                    <!-- Slot Content -->
                    <Transition mode="out-in" appear>
                        <div :key="$page.url">
                            <slot name="header">
                                <AuthHeader v-if="!$page.props.hideUserInfo && user" class="mb-8"></AuthHeader>
                            </slot>
                            <slot :artist-info="artistInfo"></slot>
                        </div>
                    </Transition>
                    
                    <!-- Footer -->
                    <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
                        <div class="text-center text-sm text-gray-600 space-y-2">
                            <div>
                                <a :href="route('auth.register.view')" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                                    Create account
                                </a>
                                <span class="mx-3 text-gray-400">•</span>
                                <a :href="route('auth.forgot-password.view')" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                                    Forgot password?
                                </a>
                            </div>
                            <div class="space-x-4">
                                <a href="https://help.eurofurence.org/legal/imprint" target="_blank" class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
                                    Legal Notice
                                </a>
                                <span class="text-gray-400">•</span>
                                <a href="https://help.eurofurence.org/legal/privacy" target="_blank" class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
                                    Privacy Policy
                                </a>
                                <span class="text-gray-400">•</span>
                                <a href="https://help.eurofurence.org/contact/" target="_blank" class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
                                    Support
                                </a>
                            </div>
                            <div class="text-xs text-gray-500">
                                Artwork by 
                                <a :href="artistInfo.url" 
                                   class="hover:text-gray-700 transition-colors"
                                   target="_blank">
                                    {{ artistInfo.name }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import {usePage} from "@inertiajs/vue3";

const user = usePage().props.user;
const artistInfo = {
    url: 'https://www.furaffinity.net/user/chromamancer',
    name: 'Chromamancer'
};
</script>
<script>
import VueCookieAcceptDecline from 'vue-cookie-accept-decline'
import 'vue-cookie-accept-decline/dist/vue-cookie-accept-decline.css'
import AuthFooter from "../Components/Auth/AuthFooter.vue";
import AuthHeader from "../Components/Auth/AuthHeader.vue";
import {usePage} from "@inertiajs/vue3";

export default {
    components: {AuthHeader, AuthFooter, VueCookieAcceptDecline},
    data() {
        return {
            animated: true,
            darkMode: this.$cookies.isKey('darkMode'),
            navigation: {
                main: [
                    {
                        name: 'Create Account',
                        link: route('auth.register.view'),
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

.auth-background-layout {
    background-position: 35% center;
    background-repeat: no-repeat;
    background-size: cover;
    background-image: url('../../../assets/space_chromamancer.jpeg');
}

.page * {
    @apply transition-colors;
}

/* Hover states */
.hover-lift:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
}

</style>
