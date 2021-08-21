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
            <div class="
                    grid grid-cols-1
                    gap-4
                    items-start
                    lg:grid-cols-3 lg:gap-8
                ">
                <!-- Left column -->
                <div class="grid grid-cols-1 gap-4 lg:col-span-2">
                    <!-- Welcome panel -->
                    <section aria-labelledby="profile-overview-title">
                        <div class="rounded-lg bg-white overflow-hidden shadow">
                            <h2 id="profile-overview-title" class="sr-only">
                                Profile Overview </h2>
                            <div class="bg-white p-6">
                                <div class="
                                        sm:flex
                                        sm:items-center
                                        sm:justify-between
                                    ">
                                    <div class="sm:flex sm:space-x-5">
                                        <div class="flex-shrink-0">
                                            <img alt="" class="
                                                    mx-auto
                                                    h-20
                                                    w-20
                                                    rounded-full
                                                "/>
                                        </div>
                                        <div class="
                                                mt-4
                                                text-center
                                                sm:mt-0 sm:pt-1 sm:text-left
                                            ">
                                            <p class="
                                                    text-sm
                                                    font-medium
                                                    text-gray-600
                                                ">
                                                Welcome back, </p>
                                            <p class="
                                                    text-xl
                                                    font-bold
                                                    text-gray-900
                                                    sm:text-2xl
                                                ">
                                                {{ user.name }} </p>
                                            <p class="
                                                    text-sm
                                                    font-medium
                                                    text-gray-600
                                                ">
                                                {{ user.role }} </p>
                                        </div>
                                    </div>
                                    <div class="mt-5 flex justify-center sm:mt-0">
                                        <InertiaLink :href="route('profile')" class="
                                                flex
                                                justify-center
                                                items-center
                                                px-4
                                                py-2
                                                border border-gray-300
                                                shadow-sm
                                                text-sm
                                                font-medium
                                                rounded-md
                                                text-gray-700
                                                bg-white
                                                hover:bg-gray-50
                                            ">
                                            View profile
                                        </InertiaLink>
                                    </div>
                                </div>
                            </div>
                            <div class="
                                    border-t border-gray-200
                                    bg-gray-50
                                    grid grid-cols-1
                                    divide-y divide-gray-200
                                    sm:grid-cols-3 sm:divide-y-0 sm:divide-x
                                ">
                                <div v-for="stat in stats" :key="stat.label" class="
                                        px-6
                                        py-5
                                        text-sm
                                        font-medium
                                        text-center
                                    ">
                                    <span class="text-gray-900">{{
                                            stat.value
                                        }}</span> {{ ' ' }} <span class="text-gray-600">{{
                                        stat.label
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout'
import {computed} from 'vue'
import {usePage} from '@inertiajs/inertia-vue3'

export default {
    components: {
        AppLayout
    },
    setup() {
        const user = computed(() => usePage().props.value.user)
        return {user}
    },
}
</script>
