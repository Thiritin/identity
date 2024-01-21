<template>
    <transition name="error">
        <span v-if="value" class="text-sm text-red-600">{{ value }}</span>
    </transition>
</template>

<script>
    export default {
        name: 'FieldError',
        props: {
            value: {
                type: [String, null],
                required: false,
            },
        },
    }
</script>

<style scoped>
    .error-enter-from {
        opacity: 0;
    }

    .error-enter-active {
        transition: all 0.2s ease;
    }

    .error-enter-to {
        opacity: 1;
    }

    .error-leave-from {
        opacity: 1;
    }

    .error-leave-active {
        transition-duration: 200ms;
    }

    .error-leave-to {
        opacity: 0;
    }
</style>
