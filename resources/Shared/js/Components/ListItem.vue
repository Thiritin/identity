<script setup>
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    hover: {
        type: Boolean,
        default: false
    }
})
// if props hover is set add hover classes
const hoverClasses = props.hover ? 'hover:bg-gray-100 cursor-pointer cursor-pointer hover:dark:bg-primary-700' : ''
const generalClasses = "px-2 py-3  items-start flex justify-between";
const finalClasses = generalClasses + ' ' + hoverClasses

</script>

<template>
    <component
        :is="$attrs.href ? Link : 'div'"
        :class="finalClasses">
        <slot></slot>
    </component>
</template>

<style scoped>

</style>
