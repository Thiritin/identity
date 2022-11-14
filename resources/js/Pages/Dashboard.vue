<template>
    <app-layout>
        <template #header>
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
                <h1 class='text-3xl font-bold text-white'>{{ $trans('dashboard') }}</h1>
            </div>
        </template>

        <div>
            <h1 class='sr-only'>Profile</h1>
            <!-- Main 3 column grid -->
            <div class='
                    grid grid-cols-1
                    gap-4
                    items-start
                    lg:grid-cols-3 lg:gap-8
                '>
                <a v-if='user.roles.includes("staffbooking")' href='https://reglive.eurofurence.org/20220129-2000-t4x9/authsrv/v1/auth?app_name=room-service'  class='block bg-white rounded-lg shadow py-8 text-center'>
                    <TicketDuotone class='mx-auto w-24 fill-current text-primary-600' />
                    <h2 class='font-medium text-xl pt-4'>Hotel Staff 2022</h2>
                    <p>Book your hotel room trough the IDP.</p>
                </a>
                <InertiaLink :href='route("settings.profile")'
                             class='block bg-white rounded-lg shadow py-8 text-center'>
                    <CogsDuotone class='mx-auto w-24 fill-current text-primary-600' />
                    <h2 class='font-medium text-xl pt-4'>Your Settings</h2>
                    <p>Configure your account.</p>
                </InertiaLink>
            </div>
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import { computed } from 'vue'
    import { usePage } from '@inertiajs/inertia-vue3'
    import CogsDuotone from '@/Components/Icons/CogsDuotone.vue'
    import TicketDuotone from '@/Components/Icons/TicketDuotone.vue'
    import WorkInProgressBanner from '@/Pages/Profile/WorkInProgressBanner.vue'

    export default {
        components: {
            WorkInProgressBanner,
            TicketDuotone,
            CogsDuotone,
            AppLayout,
        },
        setup() {
            const user = computed(() => usePage().props.value.user)
            return { user }
        },
    }
</script>
