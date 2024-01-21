<template>
    <div>Test {{ user.name }}</div>
</template>

<script setup>
import {computed, defineProps} from 'vue';
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    apps: Array,
});

const user = computed(() => usePage().props.user);

</script>
<script>
import AuthLayout from "../Layouts/AuthLayout.vue";

export default {
    layout: AuthLayout,
}
</script>
