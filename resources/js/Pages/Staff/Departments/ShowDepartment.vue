<script setup>
import AppLayout from "../../../Layouts/AppLayout.vue";
import BaseButton from "../../../Components/BaseButton.vue";
import {Head, Link} from '@inertiajs/vue3';
import SiteHeader from "../../../Components/Staff/SiteHeader.vue";


defineOptions({layout: AppLayout})
const props = defineProps({
    group: Object,
    users: Array,
})

</script>

<template>
    <div>
        <Head :title="group.name"></Head>
        <SiteHeader :title="group.name">
            <!-- Template Action -->
            <template v-slot:action>
                <div class="flex gap-2">
                    <BaseButton :href="route('staff.departments.members.create',{department: group.hashid})" small
                                info>Add User
                    </BaseButton>
                    <!-- @Todo Add Edit Department -->
                    <!--
                    <BaseButton small primary :href="route('staff.departments.edit',{department: group.id})">Edit
                    </BaseButton> -->
                </div>
            </template>
        </SiteHeader>
        <!-- User List -->
        <ul role="list" class="divide-y divide-gray-900/5">
            <li v-for="user in users">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-lg font-semibold">{{ user.name }}</div>
                            <div class="text-sm text-gray-600">{{ user.level }}</div>
                        </div>
                        <div class="flex gap-2">
                            <Link
                                :href="route('staff.departments.members.edit', {department: group.hashid, member: user.id})"
                                class="text-primary-600 font-semibold hover:text-primary-900"
                            >Edit<span class="sr-only">, {{ user.name }}</span></Link>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<style scoped>

</style>
<style>
.customize-table {

}
</style>
