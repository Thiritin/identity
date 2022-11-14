<template>
    <app-layout>
        <template #header>
            <slot name="header"></slot>
        </template>
        <div class="bg-white rounded-lg shadow px-5 py-6 sm:px-6">
            <slot></slot>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
    name: 'BoxedLayout',
    components: {AppLayout},
}
</script>

<style scoped></style>
