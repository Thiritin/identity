<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import {ChevronRightIcon} from "@heroicons/vue/24/outline/index.js";
import {defineAsyncComponent} from "vue";
import PageHeadline from "../../Components/Staff/PageHeadline.vue";
import {Head} from '@inertiajs/vue3'

const loadIconComponent = (name) => defineAsyncComponent(() => import(`../../Components/Icons/${name}.vue`))

const props = defineProps({
    apps: Array,
})
defineOptions({layout: AppLayout})
</script>

<template>
    <div>
        <Head title="Dashboard"></Head>
        <PageHeadline>Apps</PageHeadline>
        <div class="grid lg:grid-cols-2 gap-3">
            <a :href='app.url' v-for="app in apps"
               class="flex cursor-pointer drop-shadow duration-200 items-center bg-white hover:bg-gray-100 dark:bg-primary-600 hover:dark:bg-primary-500 rounded-lg shadow p-2 gap-3">
                <div>
                    <component :is="loadIconComponent(app.icon)"
                               class='mx-auto h-12 min-w-[72px] fill-current text-primary-600 dark:text-primary-300'/>
                </div>
                <div>
                    <div>
                        <h2 class='font-medium text-xl dark:text-primary-300'>{{ app.name }}</h2>
                    </div>
                    <div>
                        <p class='dark:text-primary-300'>{{ app.description }}</p>
                    </div>
                </div>
                <div class="ml-auto">
                    <ChevronRightIcon class="h-6 text-primary-600 dark:text-primary-400"></ChevronRightIcon>
                </div>
            </a>
        </div>
    </div>
</template>

<style scoped>

</style>
