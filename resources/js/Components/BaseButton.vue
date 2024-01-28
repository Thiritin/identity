<script setup>
import {Link} from "@inertiajs/vue3";

const primaryButtonClasses = "text-primary-100 bg-primary-600 dark:bg-primary-700 dark:hover:bg-primary-800 hover:bg-primary-700";
const secondaryButtonClasses = "bg-white dark:text-primary-100 hover:bg-gray-50 border-gray-300 dark:bg-primary-700 dark:hover:bg-primary-800 dark:border-primary-900 border";
const generalButtonClasses = "py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2";
const generalFocusClasses = "focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500";

const props = defineProps({
    primary: {
        type: Boolean,
        default: false
    },
    secondary: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'button'
    },
    href: {
        type: String,
        default: ''
    }
})

const buttonClasses = props.primary ? primaryButtonClasses : secondaryButtonClasses;
const finalClasses = generalButtonClasses + ' ' + buttonClasses + ' ' + generalFocusClasses;
</script>
<template>
    <component
        :is="props.href ? Link : 'button'"
        :href="props.href"
        :disabled="props.disabled || props.loading"
        :type="props.type"
        :class="finalClasses"
    >
        <slot></slot>
    </component>
</template>
<script>
export default {
    name: 'BaseButton',
}
</script>
