<template>
    <div>
        <VueCookieAcceptDecline
            :disableDecline="true"
            :showPostponeButton="false"
            elementId="cookies"
            position="top"
            transitionName="slideFromTop"
            type="bar"
        >

            <!-- Optional -->
            <template #message>
                {{ $trans('cookie_notice') }}
            </template>

            <!-- Optional -->
            <template #acceptContent>OK</template>
        </VueCookieAcceptDecline>
        <div class="min-h-screen bg-white flex">
            <!-- Logo -->
            <div class="hidden lg:block relative w-0 flex-auto">
                <div
                    class="absolute inset-0 object-right h-full w-full object-cover bg-primary-600 auth-background">
                    <div
                        class="absolute bottom-2 left-2 text-sm text-primary-200 bg-black px-2 py-1 rounded shadow">Image by
                        <a
                            class="hover:underline" href="https://twitter.com/ArtYeen">ArtYeen</a>
                    </div>
                </div>
            </div>
            <!-- Page Content -->
            <div class="flex-1 flex flex-col dark:bg-primary-900 items-center p-4 sm:px-6 lg:flex-none lg:px-20 xl:px-12">
                <!-- Spacer -->
                <div class="h-[25%]"></div>
                <!-- Slot Content -->
                <div class="flex-auto mx-auto w-full max-w-sm lg:w-96">
                    <transition name="page">
                        <div v-if="animated">
                            <slot></slot>
                        </div>
                    </transition>
                </div>
                <!-- Footer Content -->
                <div class="pt-8">
                    <nav aria-label="Footer" class="-mx-5 -my-2 flex flex-wrap justify-center">
                        <div v-for="item in navigation.main" :key="item.name" class="px-5 py-2">
                            <InertiaLink v-if="item.href == null" :href="item.link"
                                         :target="[item.newTab ? '_blank' : '_top']"
                                         class="text-base text-gray-500 hover:text-gray-900 dark:hover:text-gray-400"> {{ item.name }}
                            </InertiaLink>
                            <a v-else :href="item.href" :target="[item.newTab ? '_blank' : '_top']"
                               class="text-base text-gray-500 hover:text-gray-900 dark:hover:text-gray-400"> {{ item.name }} </a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import VueCookieAcceptDecline from "vue-cookie-accept-decline";
import 'vue-cookie-accept-decline/dist/vue-cookie-accept-decline.css';

export default {
    components: {VueCookieAcceptDecline},
    data() {
        return {
            animated: false,
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
    }
}
</script>
<style>

.auth-background {
    background-position: right;
    background-repeat: no-repeat;
    background-size: cover;
    background-image: url('../../assets/blackmagic_by_kur0.jpg');
}

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
