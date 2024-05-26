<script setup>
    import AppLayout from '../../../Layouts/AppLayout.vue'
    import SiteHeader from '../../../Components/Staff/SiteHeader.vue'
    import ChevronRightIcon from '../../../Components/Icons/ChevronRightIcon.vue'
    import { Link } from '@inertiajs/vue3'

    defineOptions({ layout: AppLayout })
    const props = defineProps({
        groups: Array,
        myGroups: Array,
    })

</script>

<template>
    <SiteHeader class="mb-4" title="Departments"></SiteHeader>
    <!--
    <div class="xl:grid grid-cols-2 gap-4">
        <div>
            <Head title="Departments"></Head>
            <PageHeadline>My Department(s)</PageHeadline>
            <ListDepartments :departments="myGroups"></ListDepartments>
        </div>
        <div>
            <PageHeadline>All Departments</PageHeadline>
            <ListDepartments :departments="groups"></ListDepartments>
        </div>
    </div>-->
    <!-- List of Groups -->
    <!-- Department list -->
    <ul role="list" class="divide-y divide-gray-900/5">
        <li v-for="department in groups" :key="department.id"
            class="relative flex items-center space-x-4 px-4 py-4 sm:px-6 lg:px-8">
            <div class="min-w-0 flex-auto">
                <div class="flex items-center gap-x-3">
                    <h2 class="min-w-0 font-semibold leading-6">
                        <Link :href="route('staff.departments.show',{department: department.hashid})" class="flex">
                            <span class="truncate">{{ department.name }}</span>
                        </Link>
                    </h2>
                </div>
                <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
                    <p class="truncate">{{ department.users_count }} Members</p>
                    <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
                        <circle cx="1" cy="1" r="1" />
                    </svg>
                    <p class="whitespace-nowrap">Lead by xxx</p>
                </div>
            </div>
            <Link :href="route('staff.departments.show',{department: department.hashid})" class="flex items-center ">
                <div
                    :class="'rounded-full flex-none py-1 px-2 mr-5 text-xs font-medium ring-1 ring-inset'">
                    <!-- todo: replace with user's role -->
                    Member
                </div>
                <ChevronRightIcon class="h-5 w-5 flex-none text-gray-400" aria-hidden="true" />
            </Link>
        </li>
    </ul>
</template>

<style scoped>

</style>
