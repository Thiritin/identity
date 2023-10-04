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
                <a v-for="app in apps" :href='app.url'
                   class='block bg-white dark:bg-primary-600 rounded-lg shadow py-8 text-center'>
                    <component :is="app.icon" class='mx-auto w-24 fill-current text-primary-600 dark:text-primary-300'/>
                    <h2 class='font-medium text-xl pt-4 dark:text-primary-300'>{{ app.name }}</h2>
                    <p class='dark:text-primary-300'>{{ app.description }}</p>
                </a>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import CogsDuotone from '@/Components/Icons/CogsDuotone.vue'
import TicketDuotone from '@/Components/Icons/TicketDuotone.vue'
import WorkInProgressBanner from '@/Pages/Profile/WorkInProgressBanner.vue'

export default {
    props: {
        apps: Array
    },
    components: {
        WorkInProgressBanner,
        TicketDuotone,
        CogsDuotone,
        AppLayout,
    },
    setup() {
        const user = computed(() => usePage().props.value.user)
        return {user}
    },
}
</script>
