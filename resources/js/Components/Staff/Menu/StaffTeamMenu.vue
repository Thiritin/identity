<script setup>
import StaffTeamMenuItem from "./StaffTeamMenuItem.vue";

defineProps({
    teams: Array,
})
</script>

<template>
    <div class="text-xs font-semibold leading-6 text-primary-200">Your Departments</div>
    <ul role="list" class="-mx-2 mt-2 space-y-1">
        <StaffTeamMenuItem v-for="team in teams" :team="team" :key="team.name">
            <a :href="team.href"
               :class="[team.current ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white duration-200 hover:bg-primary-700', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                        <span
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-primary-400 bg-primary-700 text-[0.625rem] font-medium text-white">{{ team.initial }}</span>
                <span class="truncate">{{ team.name }}</span>
            </a>
        </StaffTeamMenuItem>
    </ul>
</template>

<style scoped>

</style>
