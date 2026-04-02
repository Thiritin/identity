<script setup>
import {defineProps} from 'vue'
import AppLayout from '../../../Layouts/AppLayout.vue'
import SiteHeader from '../../../Components/Staff/SiteHeader.vue'
import {Head, useForm} from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
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
    {name: trans('staff_member_label'), value: 'member'},
    {name: trans('staff_team_lead_label'), value: 'team_lead'},
    {name: trans('staff_director_label'), value: 'director'},
    {name: trans('staff_division_director_label'), value: 'division_director'},
]
</script>

<template>
    <Head :title="$t('staff_edit_member_title')"></Head>
    <SiteHeader :title="`${group.name} - ${$t('staff_edit_member_title')} - ${member.name}`"></SiteHeader>

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
                    <PrimaryButton type="submit" class="btn btn-primary w-full md:w-[14rem]">{{ $t('staff_update') }}</PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
