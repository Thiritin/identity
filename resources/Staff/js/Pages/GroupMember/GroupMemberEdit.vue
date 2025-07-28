<script setup>
import {defineProps} from 'vue'
import AppLayout from '../../../../Shared/js/Layouts/AppLayout.vue'
import SiteHeader from '../../../../Shared/js/Components/Staff/SiteHeader.vue'
import {Head, useForm} from '@inertiajs/vue3'
import Dropdown from 'primevue/dropdown'
import PrimaryButton from '../../../../Shared/js/Components/PrimaryButton.vue'

defineOptions({layout: AppLayout})
const props = defineProps({
    group: Object,
    user: Object,
    member: Object,
})
const form = useForm({
    level: props.member.pivot.level,
})
const levels = [
    {name: 'Member', value: 'member'},
    {name: 'Moderator', value: 'moderator'},
    {name: 'Admin', value: 'admin'},
]
</script>

<template>
    <Head title="Edit a member"></Head>
    <SiteHeader :title="`${group.name} - Edit a member - ${member.name}`"></SiteHeader>

    <div>
        <div class="max-w-sm mx-auto mt-12">
            <form action="#" method="post" @submit.prevent="form.patch(route('staff.groups.members.update', {
            group: props.group.hashid,
            member: props.member.hashid,
        }))">
                <!-- Prime Vue Edit Member form with one select input of level -->
                <Dropdown v-model="form.level" :options="levels" option-value="value" optionLabel="name"
                          class="w-full md:w-56"/>
                <div class="flex pt-2 justify-start">
                    <PrimaryButton type="submit" class="btn btn-primary w-full md:w-56">Update</PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
