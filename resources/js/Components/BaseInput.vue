<template>
    <div
        class='sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:pt-5'>
        <label v-if="label" :for="fieldId" class='block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2'>{{
                label
            }}</label>
        <div class='mt-1 sm:mt-0 sm:col-span-2'>
            <input
                :id="fieldId"
                :class="[error ? 'border-red-600' : 'border-gray-300']"
                :placeholder="label"
                :value="modelValue"
                class='form-input block transition-all max-w-lg w-full shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md'
                v-bind="$attrs"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <transition name="error">
                <span v-if="error" class="text-sm text-red-600">{{ error }}</span>
            </transition>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        label: {
            type: String,
            default: ''
        },
        modelValue: {
            type: [String, Number],
            default: ''
        },
        error: {
            type: String
        }
    },
    setup(props) {
        const fieldId = "input-" + props.label.trim().replace(/\W/g, '').toLowerCase() + "-" + Math.random().toString(36).slice(2);
        return {
            fieldId
        };
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


