<template>
    <div class='bg-white dark:bg-primary-600 rounded shadow h-full flex flex-col'>
        <img v-if='group.logo' :src='group.logo' alt='' class='rounded-t max-h-[300px] max-w-[600px]'>
        <img class='rounded-t' v-else src='../../../assets/fallback-group-image.png' alt=''>
        <div class='p-4 flex flex-col flex-1 justify-start'>
            <div class='flex justify-between pb-2'>
                <h1 class='font-semibold text-xl dark:text-primary-200'>{{ transProp(group.name) }}</h1>
                <div class='flex items-center gap-1 text-primary-400 dark:text-primary-200'>
                    <CircleUser class='fill-current w-4'></CircleUser>
                    <div class='text-sm font-semibold'>{{ group.users_count }}</div>
                </div>
            </div>
            <p class='h-full dark:text-primary-400 '>{{ description }}</p>
            <!-- Links -->
            <div class='flex justify-around mt-3'>
                <div>
                    <Link
                        class='text-primary-500 dark:text-primary-200 font-semibold hover:text-primary-800 dark:hover:text-primary-100 flex justify-center items-center gap-1'
                        :href='route("groups.show",{group: group.hashid})'>
                        <CircleUser class='h-3.5 inline fill-current'></CircleUser>
                        <span>{{ $trans('view') }}</span>
                    </Link>
                </div>
                <div>
                    <Link
                        class='text-primary-500 dark:text-primary-200 font-semibold hover:text-primary-800 dark:hover:text-primary-100 flex justify-center items-baseline gap-1'
                        :href='route("groups.edit",{group: group.hashid})'>
                        <CogsDuotone class='h-3.5 inline'></CogsDuotone>
                        <span>{{ $trans('settings') }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import CircleUser from '@/Components/Icons/CircleUser.vue'
import CogsDuotone from '@/Components/Icons/CogsDuotone.vue'
import {Link} from "@inertiajs/vue3";

export default {
    props: {
        group: Object,
    },
    computed: {
        description() {
            let description = this.transProp(this.group.description)
            if (description.length > 95) {
                description = description.substring(0, 95) + "...";
            }
            return description;
        },
    },
    name: 'GroupBox',
    components: {CircleUser, CogsDuotone, Link},
}
</script>
