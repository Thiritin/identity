<script setup>
    import { defineProps } from 'vue'
    import AppLayout from '../../../Layouts/AppLayout.vue'
    import SiteHeader from '../../../Components/Staff/SiteHeader.vue'
    import { Head, useForm } from '@inertiajs/vue3'
    import Dropdown from 'primevue/dropdown'
    import PrimaryButton from '../../../Components/PrimaryButton.vue'

    defineOptions({ layout: AppLayout })
    const props = defineProps({
        department: Object,
        user: Object,
        member: Object,
    })
    const form = useForm({
        level: props.member.pivot.level,
    })
    const levels = [
        { name: 'Member', value: 'member' },
        { name: 'Moderator', value: 'moderator' },
        { name: 'Admin', value: 'admin' },
    ]
</script>

<template>
    <Head title="Edit a member"></Head>
    <SiteHeader :title="`${department.name} - Edit a member - ${member.name}`"></SiteHeader>

    <div>
        <div class="max-w-sm mx-auto mt-12">
            <form action="#" method="post" @submit.prevent="form.patch(route('staff.departments.members.update', {
            department: props.department.hashid,
            member: props.member.hashid,
        }))">
                <!-- Prime Vue Edit Member form with one select input of level -->
                <Dropdown v-model="form.level" :options="levels" option-value="value" optionLabel="name"
                          class="w-full md:w-[14rem]" />
                <div class="flex justify-start">
                    <PrimaryButton type="submit" class="btn btn-primary w-full md:w-[14rem]">Update</PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
