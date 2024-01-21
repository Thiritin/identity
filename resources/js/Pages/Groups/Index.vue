<template>
    <!-- Your Groups -->
    <div v-if='myGroups.length' class='grid sm:grid-cols-2 md:grid-cols-3 gap-4'>
        <GroupBox v-for='group in myGroups' :group='group'/>
    </div>
    <Card v-else class='px-4 py-48 w-full'>
        <h2 class='font-semibold text-center text-4xl text-primary-700 dark:text-primary-300 pb-2'>{{
                $trans('not_a_member_of_any_groups')
            }}</h2>
        <p class='text-center text-primary-500 dark:text-primary-400 text-lg'>{{
                $trans('groups_page_staff_notice')
            }}</p>
    </Card>
    <div v-if='departments.length'>
        <h2 class='mt-6 font-semibold text-2xl dark:text-primary-200'>{{ $trans('departments') }}</h2>
        <p class='mb-4 dark:text-primary-300'>{{ $trans('staff_members_can_see_departments') }}</p>
        <!-- Departments -->
        <div class='grid sm:grid-cols-2 md:grid-cols-3 gap-4'>
            <GroupBox v-for='group in departments' :group='group'/>
        </div>
    </div>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import GroupBox from '@/Pages/Groups/GroupBox.vue'
import Card from '@/Components/Card.vue'

export default {
    name: 'Index',
    components: {Card, GroupBox, AppLayout},
    layout: AppLayout,
    props: {
        myGroups: Array,
        departments: Array,
    },
}
</script>

