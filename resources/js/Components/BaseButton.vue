<script setup>
import {Link} from "@inertiajs/vue3";
import {computed} from "vue";

const primaryButtonClasses = "text-primary-100 bg-primary-600 dark:bg-primary-700 dark:hover:bg-primary-800 hover:bg-primary-700";
const secondaryButtonClasses = "bg-white dark:text-primary-100 hover:bg-gray-50 border-gray-300 dark:bg-primary-700 dark:hover:bg-primary-800 dark:border-primary-900 border";
const infoButtonClasses = "text-primary-100 bg-blue-700 dark:bg-primary-500 dark:hover:bg-primary-600 hover:bg-blue-800";
const generalButtonClasses = "border border-transparent shadow-sm text-sm duration-200 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2";
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
    info: {
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
    },
    small: {
        type: Boolean,
        default: false
    }
})

const buttonClasses = computed(() => {
    if (props.primary) {
        return primaryButtonClasses;
    } else if (props.secondary) {
        return secondaryButtonClasses;
    } else if (props.info) {
        return infoButtonClasses;
    } else {
        return '';
    }
})

const finalClasses = generalButtonClasses + ' ' + buttonClasses.value + ' ' + generalFocusClasses + ' ' + (props.small ? 'px-2.5 py-1.5 text-xs' : 'px-4 py-2 text-sm');
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
