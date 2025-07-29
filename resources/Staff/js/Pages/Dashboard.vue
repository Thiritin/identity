<script setup>
import AppLayout from "../../../Shared/js/Layouts/AppLayout.vue";
import {ChevronRightIcon, UserIcon, BuildingOfficeIcon, UserGroupIcon} from "@heroicons/vue/24/outline/index.js";
import {defineAsyncComponent} from "vue";
import {Head, usePage} from '@inertiajs/vue3'
import Card from "primevue/card";
import Avatar from "primevue/avatar";
import Badge from "primevue/badge";
import Divider from "primevue/divider";

const loadIconComponent = (name) => defineAsyncComponent(() => import(`../../Components/Icons/${name}.vue`))

const props = defineProps({
    apps: Array,
    orgStats: Object,
})

const user = usePage().props.user;

defineOptions({layout: AppLayout})
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Dashboard"></Head>
        
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-green-600 to-green-800 text-white p-6">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold mb-2">Welcome back, {{ user.name }}!</h1>
                <p class="text-green-100">Eurofurence Staff Directory</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- Profile Summary Card -->
                <div class="lg:col-span-1">
                    <Card class="mb-6">
                        <template #content>
                            <div class="text-center">
                                <Avatar 
                                    :image="user.profile_photo_url" 
                                    :label="user.name.charAt(0).toUpperCase()" 
                                    size="xlarge" 
                                    shape="circle" 
                                    class="mb-4"
                                />
                                <h3 class="text-lg font-semibold text-gray-900">{{ user.name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">@{{ user.username }}</p>
                                <Badge :value="orgStats.user_rank" severity="success" class="mb-4" />
                                
                                <Divider />
                                
                                <div class="text-left space-y-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <UserIcon class="h-4 w-4 mr-2" />
                                        <span>{{ user.email }}</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600" v-if="orgStats.user_groups.length">
                                        <UserGroupIcon class="h-4 w-4 mr-2" />
                                        <span>{{ orgStats.user_groups.length }} Groups</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Quick Stats -->
                    <Card>
                        <template #header>
                            <div class="p-4 pb-0">
                                <h4 class="text-lg font-semibold">Organization Overview</h4>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Divisions</span>
                                    <Badge :value="orgStats.total_divisions" />
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Departments</span>
                                    <Badge :value="orgStats.total_departments" />
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Teams</span>
                                    <Badge :value="orgStats.total_teams" />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Main Content Area -->
                <div class="lg:col-span-3">
                    
                    <!-- My Groups Section -->
                    <Card class="mb-6" v-if="orgStats.user_groups.length">
                        <template #header>
                            <div class="p-6 pb-0">
                                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                    <UserGroupIcon class="h-5 w-5 mr-2 text-green-600" />
                                    My Groups
                                </h2>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                <div 
                                    v-for="group in orgStats.user_groups" 
                                    :key="group.name"
                                    class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors cursor-pointer"
                                >
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ group.name }}</h3>
                                            <p class="text-sm text-gray-500">{{ group.type }}</p>
                                        </div>
                                        <Badge :value="group.level" severity="info" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Applications Section -->
                    <Card>
                        <template #header>
                            <div class="p-6 pb-0">
                                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                    <BuildingOfficeIcon class="h-5 w-5 mr-2 text-green-600" />
                                    Available Applications
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">Applications you have access to based on your group memberships</p>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" v-if="apps.length">
                                <a 
                                    :href="app.url" 
                                    v-for="app in apps"
                                    :key="app.id"
                                    class="group border border-gray-200 rounded-lg p-4 hover:border-green-300 hover:shadow-md transition-all duration-200 block"
                                >
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <component 
                                                :is="loadIconComponent(app.icon)"
                                                class="h-10 w-10 text-green-600 group-hover:text-green-700"
                                            />
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h3 class="text-sm font-medium text-gray-900 group-hover:text-green-800">
                                                {{ app.name }}
                                            </h3>
                                            <p class="text-xs text-gray-500 mt-1">{{ app.description }}</p>
                                        </div>
                                        <ChevronRightIcon class="h-4 w-4 text-gray-400 group-hover:text-green-600" />
                                    </div>
                                </a>
                            </div>
                            <div v-else class="text-center py-8">
                                <BuildingOfficeIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <p class="text-gray-500">No applications available</p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
