<template>
    <app-layout>
        <template #header>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-white">{{ $trans('dashboard') }}</h1>
            </div>
        </template>

        <div>
            <h1 class="sr-only">Profile</h1>
            <!-- Main 3 column grid -->
            <WorkInProgressBanner class="mb-8" ></WorkInProgressBanner>
            <div class="
                    grid grid-cols-1
                    gap-4
                    items-start
                    lg:grid-cols-3 lg:gap-8
                ">
                    <div class="bg-white rounded-lg shadow py-8 text-center">
                        <TicketDuotone class="mx-auto w-24 fill-current text-primary-600"/>
                        <h2 class="font-medium text-xl pt-4">Regsystem</h2>
                        <p>Manage your Eurofurence Registration</p>
                    </div>
                    <div class="bg-white rounded-lg shadow py-8 text-center">
                        <CogsDuotone class="mx-auto w-24 fill-current text-primary-600"/>
                        <h2 class="font-medium text-xl pt-4">Your Settings</h2>
                        <p>Configure your account.</p>
                    </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout'
import {computed} from 'vue'
import {usePage} from '@inertiajs/inertia-vue3'
import CogsDuotone from "../Components/Icons/CogsDuotone";
import TicketDuotone from "../Components/Icons/TicketDuotone";
import WorkInProgressBanner from "./Profile/WorkInProgressBanner";

export default {
    components: {
        WorkInProgressBanner,
        TicketDuotone,
        CogsDuotone,
        AppLayout
    },
    setup() {
        const user = computed(() => usePage().props.value.user)
        return {user}
    },
}
</script>
