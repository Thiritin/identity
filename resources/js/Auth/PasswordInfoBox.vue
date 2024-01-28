<template>
    <div class='dark:text-primary-100'>
        <span
            class="block mb-2 text-xs">{{ $trans('password_requirements_title') }}</span>
        <ul class="text-xs list-disc ml-4">
            <li :class="{'text-red-600 dark:text-red-500 font-semibold': !correctLength}">{{ $trans('password_requirement_1') }}</li>
            <li :class="{'text-red-600 dark:text-red-500 font-semibold': !correctLowerUpper}">{{ $trans('password_requirement_2') }}</li>
            <li :class="{'text-red-600 dark:text-red-500 font-semibold': !correctNumber}">{{ $trans('password_requirement_3') }}</li>
        </ul>
    </div>
</template>
<script setup>
import {computed} from 'vue'

const props = defineProps({
    password: {
        type: String,
        default: '',
    },
})
const correctLength = computed(() => {
    if (props.password === null) {
        return false;
    }
    return props.password.length >= 8
})
const correctLowerUpper = computed(() => {
    if (props.password === null) {
        return false;
    }
    return props.password.match(/[a-z]/) && props.password.match(/[A-Z]/)
})
const correctNumber = computed(() => {
    if (props.password === null) {
        return false;
    }
    return props.password.match(/[0-9]/)
})

</script>
<script>
export default {
    name: 'PasswordInfoBox',
    props: {
        password: {
            type: String,
            default: '',
        },
    }
}
</script>
