<template>
    <div class="rounded-md bg-yellow-50 p-4 w-full">
        <div class="flex">
            <div class="flex-shrink-0">
                <ExclamationTriangleIcon
                    aria-hidden="true"
                    class="h-5 w-5 text-yellow-400"
                />
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">{{ title }}</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>{{ message }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {ExclamationTriangleIcon} from "@heroicons/vue/24/solid/index.js";

export default {
    name: 'BaseAlert',
    components: {
        ExclamationTriangleIcon,
    },
    props: {
        message: {
            type: String,
            required: false,
        },
        title: {
            type: String,
            required: true,
        },
    },
}
</script>

<style scoped></style>
