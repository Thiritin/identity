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
        <div class="bg-white flex page dark:text-primary-300 dark:bg-primary-900">
            <ArtistNotice url="https://www.furaffinity.net/user/chromamancer" name="Chromamancer"/>
            <!-- Page Content -->
            <div
                class="min-h-[calc(100dvh)]! min-h-screen
                mx-auto w-full max-w-md
                flex-1 flex flex-col items-center justify-center lg:flex-none
                px-6 sm:px-12
                pt-8 pb-8">
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
        </div>
    </div>
</template>
<script setup>
import {usePage} from "@inertiajs/vue3";

const user = usePage().props.user;
</script>
<script>
import VueCookieAcceptDecline from 'vue-cookie-accept-decline'
import 'vue-cookie-accept-decline/dist/vue-cookie-accept-decline.css'
import AuthFooter from "../Components/Auth/AuthFooter.vue";
import ArtistNotice from "../Components/Auth/ArtistNotice.vue";
import AuthHeader from "../Components/Auth/AuthHeader.vue";
import {usePage} from "@inertiajs/vue3";

export default {
    components: {AuthHeader, ArtistNotice, AuthFooter, VueCookieAcceptDecline},
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


</style>
