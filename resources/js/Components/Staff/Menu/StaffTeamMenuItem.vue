<script setup>
defineProps({
    team: Object,
})
</script>

<template>
    <li>
        <a :href="team.href"
           :class="[team.current ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white duration-200 hover:bg-primary-700', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                        <span
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-primary-400 bg-primary-700 text-[0.625rem] font-medium text-white">{{ team.initial }}</span>
            <span class="truncate">{{ team.name }}</span>
        </a>
    </li>
</template>

<style scoped>

</style>
