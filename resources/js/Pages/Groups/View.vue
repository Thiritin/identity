<template>
    <AppLayout>
        <div>
            <Card class="p-4 mb-6">
                <div class="flex gap-6">
                    <div>
                        <img v-if='group.logo' :src='group.logo' alt=''
                             class='rounded-t max-h-[300px] max-w-[600px] shadow'>
                        <img v-else alt=''
                             class='rounded-t max-h-[300px] max-w-[600px] shadow'
                             src='../../../assets/fallback-group-image.png'>
                    </div>
                    <div>
                        <h2 class="font-semibold text-3xl mb-2">{{ transProp(group.name) }} <span
                            class="rounded px-2 py-1 bg-gray-200 text-sm">{{ group.type }}</span></h2>
                        <p class="mb-3">{{ transProp(group.description) }}</p>
                        <PrimaryButton>Settings</PrimaryButton>
                    </div>
                </div>
            </Card>
            <h3 class="font-semibold text-2xl">{{ $trans('group_members') }}</h3>
            <p class="mb-4">Below you can view all members within the group. Please note that you can click group members to see contact information they published.</p>
            <div class="sm:grid sm:grid-cols-3 gap-3">
                <Card v-for="user in members" class="p-4 mb-2 flex gap-3 items-center">
                    <AvatarImage :avatar="user.avatar" class="h-16 w-16 shadow"></AvatarImage>
                    <div>
                        <div class="font-semibold text-xl">{{ user.name }}</div>
                        <div class="mt-1 flex items-center gap-2">
                            <UserLevelBadge v-if="user.level !== 'member'" :level="user.level"/>
                            <div>{{ user.title }}</div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/Card.vue'
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AvatarImage from "@/Pages/Profile/AvatarImage.vue";
import UserLevelBadge from "@/Pages/Groups/UserTitleBadge.vue";

export default {
    name: 'View',
    props: {
        group: Object,
        members: Object,
    },
    components: {UserLevelBadge, AvatarImage, PrimaryButton, Card, AppLayout},
}
</script>

