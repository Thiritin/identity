<script setup>
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Box, Users, LayoutGrid } from 'lucide-vue-next';
import { router } from "@inertiajs/vue3";

const props = defineProps({
    activeTab: {
        type: String,
        required: true
    },
    group: {
        type: Object,
        required: true
    }
});

const tabRoutes = {
    info: () => router.visit(route('staff.groups.show', { group: props.group.hashid })),
    members: () => router.visit(route('staff.groups.members.index', { group: props.group.hashid })),
    teams: () => router.visit(route('staff.groups.teams.index', { group: props.group.hashid })),
};

function navigateTab(value) {
    if (tabRoutes[value]) {
        tabRoutes[value]();
    }
}
</script>

<template>
    <Tabs :model-value="activeTab" @update:model-value="navigateTab">
        <TabsList>
            <TabsTrigger value="info">
                <Box class="mr-2 h-4 w-4" /> Info
            </TabsTrigger>
            <TabsTrigger value="members">
                <Users class="mr-2 h-4 w-4" /> Members
            </TabsTrigger>
            <TabsTrigger v-if="group.parent_id === null" value="teams">
                <LayoutGrid class="mr-2 h-4 w-4" /> Teams
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
