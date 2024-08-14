<script setup>
import AppLayout from '../../../Layouts/AppLayout.vue';
import SiteHeader from '../../../Components/Staff/SiteHeader.vue';
import GroupListItem from "../../../Components/GroupListItem.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    groups: Array,
    myGroups: Object,
});
</script>

<template>
    <SiteHeader class="mb-4" title="Departments" />

    <!-- Department list -->
    <ul role="list" class="divide-y divide-gray-900/5" v-if="groups.length">
        <GroupListItem
            v-for="department in groups"
            :key="department.id"
            :department="department"
            :myGroups="myGroups"
        />
    </ul>
    <!-- No Team created info message -->
    <div v-else class="flex items-center justify-center h-96">
        <p class="text-gray-400">No departments created yet.</p>
    </div>
</template>

