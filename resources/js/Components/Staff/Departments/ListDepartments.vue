<script setup>

import Card from "../../Card.vue";
import CardBody from "../../CardBody.vue";
import BaseButton from "../../BaseButton.vue";

const props = defineProps({
    groups: Array,
})
</script>

<template>
    <div class="mb-4 space-y-3">
        <Card v-for="group in groups">
            <CardBody class="flex justify-between items-center">
                <div>
                    <h2 class="font-medium text-xl dark:text-primary-300">{{ group.name }}</h2>
                    <div class="text-sm text-gray-500">{{ group.users_count }} Members</div>
                </div>
                <div>
                    <BaseButton :href="route('staff.groups.show',group.hashid)" primary>View</BaseButton>
                </div>
            </CardBody>
        </Card>
    </div>
</template>

<style scoped>

</style>
