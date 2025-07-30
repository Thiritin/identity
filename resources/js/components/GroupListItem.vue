<template>
    <li
        @click="handleClick"
        class="relative flex items-center space-x-4 px-4 py-4 sm:px-6 lg:px-8 cursor-pointer hover:bg-gray-50 duration-200"
    >
        <div class="min-w-0 flex-auto">
            <div class="flex items-center gap-x-3">
                <h2 class="min-w-0 font-semibold leading-6">
                    <Link :href="groupLink" class="flex">
                        <span class="truncate">{{ department.name }}</span>
                    </Link>
                </h2>
            </div>
            <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
                <p class="truncate">{{ department.users_count }} Members</p>
            </div>
        </div>
        <Link :href="groupLink" class="flex items-center">
            <div
                v-if="myGroupLabel"
                class="rounded-full flex-none py-1 px-2 mr-5 text-xs font-medium ring-1 ring-inset"
            >
                {{ myGroupLabel }}
            </div>
            <ChevronRightIcon class="h-5 w-5 flex-none text-gray-400" aria-hidden="true" />
        </Link>
    </li>
</template>

<script setup>
import {computed} from 'vue';
import {Link, router} from '@inertiajs/vue3';
import ChevronRightIcon from "./Icons/ChevronRightIcon.vue";

const props = defineProps({
    department: {
        type: Object,
        required: true,
    },
    myGroups: {
        type: Object,
        required: true,
    },
});

const groupLink = computed(() =>
    route('staff.groups.show', { group: props.department.hashid })
);

const handleClick = () => {
    router.visit(groupLink.value);
};

const myGroupLabel = computed(() => props.myGroups[props.department.id]);
</script>

<style scoped>
/* Add any necessary styles here */
</style>
