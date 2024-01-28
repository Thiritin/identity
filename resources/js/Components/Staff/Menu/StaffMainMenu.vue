<script setup>
import {defineProps} from 'vue'
import StaffMainMenuItem from "./StaffMainMenuItem.vue";

const props = defineProps({
    navigation: Array,
})
</script>

<template>
    <ul role="list" class="-mx-2 space-y-1">
        <StaffMainMenuItem v-for="item in navigation" :item="item" :key="item.name"/>
    </ul>
</template>

<style scoped>

</style>
