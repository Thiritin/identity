<template>
    <AppLayout>
        <div>
            <GroupHorizontalBox :can-see-settings="canSeeSettings" :group="group"/>
            <div v-if="members.length > 1">
                <h3 class="font-semibold text-2xl dark:text-primary-100">{{ $trans('group_members') }}</h3>
                <p class="mb-4 dark:text-primary-100">Below you can view all members within the group.</p>
                <div class="sm:grid sm:grid-cols-3 gap-3">
                    <Card v-for="user in members" class="p-4 mb-2 flex gap-3 items-center">
                        <AvatarImage :avatar="user.avatar" class="h-16 w-16 shadow"></AvatarImage>
                        <div>
                            <div class="font-semibold text-xl dark:text-primary-100">{{ user.name }}</div>
                            <div class="mt-1 flex items-center gap-2">
                                <UserLevelBadge v-if="user.level !== 'member'" :level="user.level"/>
                                <div class="dark:text-primary-100">{{ user.title }}</div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/Card.vue'
import AvatarImage from "@/Pages/Profile/AvatarImage.vue";
import UserLevelBadge from "@/Pages/Groups/UserTitleBadge.vue";
import GroupHorizontalBox from "@/Components/GroupHorizontalBox.vue";

export default {
    name: 'View',
    components: {GroupHorizontalBox, UserLevelBadge, AvatarImage, Card, AppLayout},
    props: {
        group: Object,
        members: Object,
        canSeeSettings: Boolean
    },
}
</script>

