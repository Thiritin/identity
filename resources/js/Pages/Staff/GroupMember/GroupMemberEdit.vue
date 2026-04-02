<script setup>
import {defineProps} from 'vue'
import AppLayout from '../../../Layouts/AppLayout.vue'
import SiteHeader from '../../../Components/Staff/SiteHeader.vue'
import {Head, useForm} from '@inertiajs/vue3'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import PrimaryButton from '../../../Components/PrimaryButton.vue'

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
                <Select v-model="form.level">
                    <SelectTrigger class="w-full md:w-56">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="level in levels" :key="level.value" :value="level.value">
                            {{ level.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <div class="flex pt-2 justify-start">
                    <PrimaryButton type="submit" class="btn btn-primary w-full md:w-[14rem]">Update</PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
