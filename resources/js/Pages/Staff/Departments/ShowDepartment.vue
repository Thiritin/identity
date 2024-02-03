<script setup>
import AppLayout from "../../../Layouts/AppLayout.vue";
import BaseButton from "../../../Components/BaseButton.vue";


defineOptions({layout: AppLayout})
const props = defineProps({
    group: Object,
    users: Array,
})

const headers = [
    {text: "Name", value: "name"},
    {text: "Height (cm)", value: "height", sortable: true},
    {text: "Weight (kg)", value: "weight", sortable: true},
    {text: "Age", value: "age", sortable: true}
];

const items = [
    {"name": "Curry", "height": 178, "weight": 77, "age": 20},
    {"name": "James", "height": 180, "weight": 75, "age": 21},
    {"name": "Jordan", "height": 181, "weight": 73, "age": 22}
];

</script>

<template>
    <div>
        <!-- Group Info Card -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-semibold">{{ group.name }}</h2>
                <p class="text-sm text-gray-600">{{ group.users_count }} Member(s)</p>
            </div>
            <div class="flex gap-2">
                <BaseButton primary>Edit Group</BaseButton>
                <BaseButton info>Add User</BaseButton>
            </div>
        </div>
        <!-- Users -->
        <div class="mt-8 mb-2">
            <h3 class="text-xl font-semibold">Users</h3>
        </div>
        <!-- User List -->
        <table class="min-w-full divide-y divide-gray-300">
            <thead>
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                <!-- Custom Columns -->
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                    <span class="sr-only">Edit</span>
                </th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.email">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ user.name }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ user.level }}</td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <a href="#" class="text-primary-600 hover:text-primary-900"
                    >Edit<span class="sr-only">, {{ user.name }}</span></a
                    >
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
<style>
.customize-table {

}
</style>
