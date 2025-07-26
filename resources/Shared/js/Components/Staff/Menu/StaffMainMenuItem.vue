<script setup>
import {defineProps} from 'vue'
import {Link} from '@inertiajs/vue3'

const props = defineProps({
    item: Object,
})
</script>

<template>
    <li>
        <Link :href="item.href"
              :class="[item.current ? 'bg-gray-50 text-primary-600' : 'text-primary-200 hover:bg-primary-700 duration-200', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
            <component :is="item.icon"
                       :class="[item.current ? 'text-primary-600' : 'text-primary-300', 'h-6 w-6 shrink-0']"
                       aria-hidden="true"/>
            {{ item.name }}
        </Link>
    </li>
</template>

<style scoped>

</style>
