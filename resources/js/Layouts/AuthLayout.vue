<template>
    <div class="min-h-screen bg-white flex">
        <div class="hidden lg:block relative w-0 flex-auto">
            <img
                alt="EF26 Background Image"
                class="absolute inset-0 h-full w-full object-cover"
                src="https://www.eurofurence.org/EF26/assets/images/background_3K_l.jpg"
            />
        </div>
        <div
            class="
                flex-1 flex flex-col
                items-center
                p-4
                sm:px-6
                lg:flex-none lg:px-20
                xl:px-12
            "
        >
            <div class="h-[25%]"></div>
            <div class="flex-auto mx-auto w-full max-w-sm lg:w-96">
                <slot></slot>
            </div>
            <div class="pt-8">
                <nav
                    aria-label="Footer"
                    class="-mx-5 -my-2 flex flex-wrap justify-center"
                >
                    <div
                        v-for="item in navigation.main"
                        :key="item.name"
                        class="px-5 py-2"
                    >
                        <a
                            :href="item.href"
                            :target="[item.newTab ? '_blank' : '_top']"
                            class="text-base text-gray-500 hover:text-gray-900"
                        >
                            {{ item.name }}
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        data() {
            return {
                navigation: {
                    main: [
                        {
                            name: 'Create Account',
                            href: route('auth.register.view'),
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
    }
</script>
