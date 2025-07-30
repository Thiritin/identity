<script setup>
import TabMenu from "@Shared/components/volt/Menu.vue";
import {router} from "@inertiajs/vue3";
import {computed} from "vue";

const props = defineProps({
    activeIndex: {
        type: Number,
        required: true
    },
    group: {
        type: Object,
        required: true
    }
});
const tabMenuItemsSource = [
    {label: 'Info', icon: 'pi pi-box', command: () => router.visit(route('staff.groups.show', {group: props.group.hashid}))},
    {label: 'Members', icon: 'pi pi-users', command: () => router.visit(route('staff.groups.members.index', {group: props.group.hashid}))},
    {label: 'Teams', icon: 'pi pi-th-large', command: () => router.visit(route('staff.groups.teams.index', {group: props.group.hashid}))},
];

const tabMenuItems = computed(() => {
    if (props.group.parent_id === null) {
        return tabMenuItemsSource;
    }
    return tabMenuItemsSource.filter(item => item.label !== 'Teams');
});
</script>

<template>
    <TabMenu :activeIndex="activeIndex" :model="tabMenuItems"></TabMenu>
</template>

<style scoped>

</style>
