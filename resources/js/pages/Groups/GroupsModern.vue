<script setup>
import AppLayout from "../../layouts/AppLayout.vue";
import {Head, usePage} from '@inertiajs/vue3'
import {ref, computed} from "vue";
import Card from "@Shared/components/volt/Card.vue";
import TabView from "@Shared/components/volt/Tabs.vue";
import TabPanel from "@Shared/components/volt/TabPanel.vue";
import DataTable from "@Shared/components/volt/DataTable.vue";
import Column from "primevue/column";
import { Button } from "@/components/ui/button";
import Badge from "@Shared/components/volt/Badge.vue";
import Avatar from "@Shared/components/volt/Avatar.vue";
import InputText from "@Shared/components/volt/InputText.vue";
import {
    Users as UserGroupIcon, 
    Building as BuildingOfficeIcon,
    ChevronDown as ChevronDownIcon,
    ChevronRight as ChevronRightIcon,
    Search as MagnifyingGlassIcon,
    ExternalLink as ExternalLinkIcon,
    Eye as EyeIcon,
    Settings as CogIcon
} from "lucide-vue-next";

const props = defineProps({
    myGroups: Array,
    allDepartments: Array,
    userDepartments: Array,
})

const user = usePage().props.user;
const searchTerm = ref('');
const expandedRows = ref({});

// Filter groups based on search
const filteredMyGroups = computed(() => {
    if (!searchTerm.value) return props.myGroups;
    return props.myGroups.filter(group => 
        group.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        group.type.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const filteredDepartments = computed(() => {
    if (!searchTerm.value) return props.allDepartments;
    return props.allDepartments.filter(dept => 
        dept.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        dept.leadership.some(leader => 
            leader.name.toLowerCase().includes(searchTerm.value.toLowerCase())
        )
    );
});

const onRowExpand = (event) => {
    // This would typically load subteams data
    console.log('Expanded:', event.data);
};

const onRowCollapse = (event) => {
    console.log('Collapsed:', event.data);
};

defineOptions({layout: AppLayout})
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Groups & Departments"></Head>
        
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-green-600 to-green-800 text-white p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Groups & Departments</h1>
                        <p class="text-green-100">Manage and explore organizational structure</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <MagnifyingGlassIcon class="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-green-200" />
                            <InputText
                                v-model="searchTerm"
                                placeholder="Search groups, departments, or people..."
                                class="pl-10 bg-green-700 border-green-600 text-white placeholder-green-200 focus:ring-green-400 focus:border-green-400"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-8">
            <TabView>
                <!-- My Groups Tab -->
                <TabPanel header="My Groups">
                    <template #header>
                        <UserGroupIcon class="h-5 w-5 mr-2" />
                        My Groups ({{ myGroups.length }})
                    </template>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                        <Card 
                            v-for="group in filteredMyGroups" 
                            :key="group.id"
                            class="hover:shadow-lg transition-shadow cursor-pointer"
                        >
                            <template #content>
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <Avatar 
                                            :image="group.logo_url" 
                                            :label="group.name.charAt(0)" 
                                            shape="circle" 
                                            class="bg-green-100 text-green-600"
                                        />
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ group.name }}</h3>
                                            <p class="text-sm text-gray-600">{{ group.type }}</p>
                                        </div>
                                    </div>
                                    <Badge :value="group.user_level" severity="success" />
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Members:</span>
                                        <span class="font-medium">{{ group.members_count }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm" v-if="group.user_title">
                                        <span class="text-gray-600">My Role:</span>
                                        <span class="font-medium">{{ group.user_title }}</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <Button 
                                        class="w-full"
                                        variant="secondary"
                                        size="sm"
                                    >
                                        <ExternalLinkIcon class="w-4 h-4 mr-2" />
                                        View {{ group.type }}
                                    </Button>
                                </div>
                            </template>
                        </Card>
                    </div>
                    
                    <div v-if="filteredMyGroups.length === 0" class="text-center py-12">
                        <UserGroupIcon class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                        <p class="text-gray-500 text-lg">
                            {{ searchTerm ? 'No groups found matching your search' : 'You are not a member of any groups yet' }}
                        </p>
                    </div>
                </TabPanel>

                <!-- Departments Tab -->
                <TabPanel header="Departments">
                    <template #header>
                        <BuildingOfficeIcon class="h-5 w-5 mr-2" />
                        All Departments ({{ allDepartments.length }})
                    </template>
                    
                    <Card class="mt-6">
                        <template #content>
                            <DataTable 
                                :value="filteredDepartments" 
                                :expandable-rows="true"
                                v-model:expanded-rows="expandedRows"
                                @row-expand="onRowExpand"
                                @row-collapse="onRowCollapse"
                                paginator 
                                :rows="10"
                                responsive-layout="scroll"
                                class="p-datatable-sm"
                            >
                                <Column :expander="true" style="width: 3rem" />
                                
                                <Column field="name" header="Department" sortable>
                                    <template #body="slotProps">
                                        <div class="flex items-center space-x-3">
                                            <Avatar 
                                                :image="slotProps.data.logo_url" 
                                                :label="slotProps.data.name.charAt(0)" 
                                                shape="circle" 
                                                size="large"
                                                class="bg-green-100 text-green-600"
                                            />
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ slotProps.data.name }}</div>
                                                <div class="text-sm text-gray-500">{{ slotProps.data.description }}</div>
                                            </div>
                                        </div>
                                    </template>
                                </Column>
                                
                                <Column field="leadership" header="Leadership">
                                    <template #body="slotProps">
                                        <div class="flex -space-x-2">
                                            <Avatar 
                                                v-for="leader in slotProps.data.leadership.slice(0, 3)" 
                                                :key="leader.id"
                                                :image="leader.profile_photo_url" 
                                                :label="leader.name.charAt(0)" 
                                                shape="circle" 
                                                class="border-2 border-white"
                                                :title="leader.name"
                                            />
                                            <Avatar 
                                                v-if="slotProps.data.leadership.length > 3"
                                                :label="`+${slotProps.data.leadership.length - 3}`" 
                                                shape="circle" 
                                                class="border-2 border-white bg-gray-300 text-gray-600 text-xs"
                                            />
                                        </div>
                                    </template>
                                </Column>
                                
                                <Column field="members_count" header="Members" sortable>
                                    <template #body="slotProps">
                                        <Badge :value="slotProps.data.members_count" />
                                    </template>
                                </Column>
                                
                                <Column field="teams_count" header="Teams" sortable>
                                    <template #body="slotProps">
                                        <Badge :value="slotProps.data.teams_count || 0" severity="info" />
                                    </template>
                                </Column>
                                
                                <Column header="Actions">
                                    <template #body="slotProps">
                                        <div class="flex space-x-2">
                                            <Button 
                                                size="sm" 
                                                variant="ghost" 
                                                :title="`View ${slotProps.data.name}`"
                                            >
                                                <EyeIcon class="w-4 h-4" />
                                            </Button>
                                            <Button 
                                                v-if="slotProps.data.can_manage"
                                                size="sm" 
                                                variant="ghost" 
                                                title="Manage Department"
                                            >
                                                <CogIcon class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </template>
                                </Column>

                                <!-- Expandable row template for subteams -->
                                <template #expansion="slotProps">
                                    <div class="p-4">
                                        <h5 class="font-semibold mb-3 text-gray-700">Teams in {{ slotProps.data.name }}</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div 
                                                v-for="team in slotProps.data.teams || []" 
                                                :key="team.id"
                                                class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <h6 class="font-medium text-gray-900">{{ team.name }}</h6>
                                                    <Badge :value="team.members_count" size="small" />
                                                </div>
                                                <p class="text-sm text-gray-600 mb-3">{{ team.description }}</p>
                                                <div class="flex items-center space-x-2">
                                                    <Avatar 
                                                        v-for="member in (team.members || []).slice(0, 2)" 
                                                        :key="member.id"
                                                        :image="member.profile_photo_url" 
                                                        :label="member.name.charAt(0)" 
                                                        shape="circle" 
                                                        size="small"
                                                        :title="member.name"
                                                    />
                                                    <span v-if="(team.members || []).length > 2" class="text-xs text-gray-500">
                                                        +{{ team.members.length - 2 }} more
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="!slotProps.data.teams || slotProps.data.teams.length === 0" class="text-center py-4">
                                            <p class="text-gray-500">No teams in this department</p>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>
                        </template>
                    </Card>
                </TabPanel>
            </TabView>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    @apply flex items-center;
}
</style>