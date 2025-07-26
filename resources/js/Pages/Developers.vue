<template>
    <div>
        <div
            class="grid grid-cols-1 gap-4 items-start lg:grid-cols-3 lg:gap-8 mb-6"
        >
            <Card
                class="block bg-white dark:bg-primary-600 rounded-lg shadow py-8 px-6 text-center"
            >
                <KeySkeletonSolid
                    class="mx-auto h-24 fill-current text-primary-600 dark:text-primary-300"
                />
                <h2 class="font-medium text-xl pt-4 dark:text-primary-300">
                    Open Authentication Standard
                </h2>
                <p class="dark:text-primary-300">
                    We are using OpenID Connect and Oauth2 for Authentication.
                </p>
            </Card>

            <Card
                class="block bg-white dark:bg-primary-600 rounded-lg shadow py-8 px-6 text-center"
            >
                <CodeDuotone
                    class="mx-auto h-24 fill-current text-primary-600 dark:text-primary-300"
                />
                <h2 class="font-medium text-xl pt-4 dark:text-primary-300">
                    We love Open Source
                </h2>
                <p class="dark:text-primary-300">
                    We publish most of our code under the MIT License on our
                    GitHub free for everyone to use.
                </p>
            </Card>

            <Card
                class="block bg-white dark:bg-primary-600 rounded-lg shadow py-8 px-6 text-center"
            >
                <ServerDuotone
                    class="mx-auto h-24 fill-current text-primary-600 dark:text-primary-300"
                />
                <h2 class="font-medium text-xl pt-4 dark:text-primary-300">
                    Managed Servers
                </h2>
                <p class="dark:text-primary-300">
                    We run our own Kubernetes system. You don't need to worry
                    about deploying your project.
                </p>
            </Card>
        </div>
    </div>
    <div class="mb-8">
        <h3 class="font-semibold text-2xl">Our Projects</h3>
        <p class="mb-3">Below you can see a grasp overview of our projects.</p>
        <Card v-for="project in projects" class="mb-4">
            <div
                class="md:flex justify-start items-center px-3 md:px-6 py-2 gap-4"
            >
                <div class="flex justify-center items-start hidden md:block">
                    <Logo class="w-12"></Logo>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">{{ project.name }}</h3>
                    <p class="text-xs max-w-xl mb-3 md:mb-0">
                        {{ project.description }}
                    </p>
                </div>
                <div class="md:ml-auto flex gap-3 justify-between items-center">
                    <div
                        v-show="project.flags.includes('identity')"
                        class="bg-primary-500 leading-tight text-center px-2 py-1 rounded font-semibold text-xs text-white"
                    >
                        OAuth2/OpenID
                        <div class="text-[8px]">via Identity</div>
                    </div>
                    <a :href="project.url" target="_blank">
                        <Github
                            class="h-6 hover:text-gray-500 fill-current"
                        ></Github>
                    </a>
                </div>
            </div>
        </Card>
    </div>

    <div>
        <h3 class="font-semibold text-2xl">Want to add something?</h3>
        <p class="mb-3">
            We are open to new integrations and projects, get in touch with us!
        </p>
        <Card class="mb-4 p-4 text-center">
            <p class="text-lg">Write us an email to</p>
            <p class="text-xl font-semibold">identity@eurofurence.org</p>
            <p>
                Meanwhile feel free to checkout the
                <a
                    class="text-blue-700 hover:underline"
                    href="https://docs.identity.eurofurence.org/"
                    target="_blank"
                    >Identity Documentation</a
                >.
            </p>
        </Card>
    </div>
</template>

<script>
    import AppLayout from '@Shared/Layouts/AppLayout.vue'
    import Card from '@Shared/Components/Card.vue'
    import CogsDuotone from '@Shared/Components/Icons/CogsDuotone.vue'
    import KeySkeletonSolid from '@Shared/Components/Icons/KeySkeletonSolid.vue'
    import CodeDuotone from '@Shared/Components/Icons/CodeDuotone.vue'
    import ServerDuotone from '@Shared/Components/Icons/ServerDuotone.vue'
    import Github from '@Shared/Components/Icons/Github.vue'
    import Logo from '@Auth/Pages/Logo.vue'

    export default {
        name: 'Developers',
        components: {
            Logo,
            Github,
            ServerDuotone,
            CodeDuotone,
            KeySkeletonSolid,
            CogsDuotone,
            Card,
            AppLayout,
        },
        setup() {
            return {
                projects: [
                    {
                        name: 'Identity (2021)',
                        description:
                            'Resource Server for ORY Hydra (OpenID Connect Certified Software) with Laravel and Vue.JS. Offers basic directory services and a simple group based management system for managing departments.',
                        url: 'https://github.com/thiritin/identity',
                        flags: ['identity'],
                    },
                    {
                        name: 'Dealer Registration (2023)',
                        description:
                            'Web Application that allows users to apply for a table at the Eurofurence Dealers den, allows management of shared tables and assistants. Fully written with Laravel and Bootstrap 5.',
                        url: 'https://github.com/eurofurence/dealer-registration',
                        flags: ['identity'],
                    },
                    {
                        name: 'Registration (2023)',
                        description:
                            'Get your Eurofurence ticket! This system powers Eurofurence Ticket purchase. It supports different kinds of tickets, additional options like shirts and notes. Written in Go and React.',
                        url: 'https://github.com/eurofurence',
                        flags: ['identity'],
                    },
                    {
                        name: 'And more!',
                        description:
                            'Explore our GitHub to see all of our repositories.',
                        url: 'https://github.com/eurofurence',
                        flags: [],
                    },
                ],
            }
        },
    }
</script>

<style scoped></style>
