<template>
    <div>
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
        <div class="min-h-screen bg-white flex page" :class="{ dark: darkMode }">
            <!-- Logo -->
            <ArtistNotice url="https://rudzik.art" name="Rudzik"/>
            <!-- Page Content -->
            <div
                class="flex-1 flex flex-col dark:text-primary-300 dark:bg-primary-900 items-center p-4 sm:px-6 lg:flex-none lg:px-20 xl:px-12">
                <!-- Spacer -->
                <div class="h-[25%]"></div>
                <!-- Slot Content -->
                <div class="flex-auto mx-auto w-full max-w-sm lg:w-96">
                    <transition name="page">
                        <div v-if="animated">
                            <AuthHeader></AuthHeader>
                            <slot></slot>
                        </div>
                    </transition>
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
<script>
import VueCookieAcceptDecline from 'vue-cookie-accept-decline'
import 'vue-cookie-accept-decline/dist/vue-cookie-accept-decline.css'
import AuthFooter from "../Components/Auth/AuthFooter.vue";
import ArtistNotice from "../Components/Auth/ArtistNotice.vue";
import AuthHeader from "../Components/Auth/AuthHeader.vue";

export default {
    components: {AuthHeader, ArtistNotice, AuthFooter, VueCookieAcceptDecline},
    data() {
        return {
            animated: false,
            darkMode: this.$cookies.isKey('darkMode'),
            navigation: {
                main: [
                    {
                        name: 'Create Account',
                        link: route('auth.register.view'),
                        newTab: false,
                    },

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
                ],
            },
        }
    },
    mounted() {
        this.animated = true;
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

.page * {
    @apply transition-colors
}

</style>
