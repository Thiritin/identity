<template>
    <Head title="Dashboard"></Head>
    <div class="flex gap-3 flex-col">
        <a :href='app.url' v-for="app in apps"
           class="flex cursor-pointer drop-shadow items-center bg-gray-50 hover:bg-gray-100 dark:bg-primary-600 hover:dark:bg-primary-500 rounded-lg shadow p-2 gap-3">
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
</template>

<script setup>
import {computed, defineProps} from 'vue'
import {usePage, Head} from '@inertiajs/vue3'
import {ChevronRightIcon} from "@heroicons/vue/24/outline";

const props = defineProps({
    apps: Array,
})

const user = computed(() => usePage().props.user)
</script>
<script>
import AuthLayout from '../../../Shared/js/Layouts/AuthLayout.vue'
import {defineAsyncComponent} from "vue";

// dynamically load vue3 icon
const loadIconComponent = (name) => defineAsyncComponent(() => import(`../../../Shared/js/Components/Icons/${name}.vue`))

export default {
    layout: AuthLayout,
}
</script>
