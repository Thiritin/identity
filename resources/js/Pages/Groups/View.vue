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
                        <h2 class="font-semibold text-3xl">{{ transProp(group.name) }}</h2>
                        <p class="mb-3">{{ transProp(group.description) }}</p>

                        <PrimaryButton>Settings</PrimaryButton>
                    </div>
                </div>
            </Card>
            <h3 class="font-semibold text-2xl mb-4">{{ $trans('group_members') }}</h3>
            <div class="sm:grid sm:grid-cols-3">
                <Card class="p-4 mb-2 flex gap-3 items-center">
                    <AvatarImage class="h-16 w-16 shadow"></AvatarImage>
                    <div>
                        <div class="font-semibold text-xl">test123</div>
                        <div>Title</div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import GroupBox from '@/Pages/Groups/GroupBox.vue'
import Card from '@/Components/Card.vue'
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AvatarImage from "@/Pages/Profile/AvatarImage.vue";

export default {
    name: 'View',
    props: {
        group: Object,
    },
    components: {AvatarImage, PrimaryButton, Card, GroupBox, AppLayout},
}
</script>

