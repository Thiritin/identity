<template>
    <Head :title="group.name" />
    <div class="flex flex-col lg:flex-row gap-6">
        <DirectoryTree :tree="tree" :selected="group.hashid" class="lg:w-72 shrink-0" />
        <div class="flex-1 min-w-0">
            <GroupDetail
                :group="group"
                :leaders="leaders"
                :members="members"
                :sub-groups="subGroups"
                :can-edit="canEdit"
                @toggle-edit="editMode = !editMode"
                @add-member="showAddMember = true"
                @create-sub-group="showCreateTeam = true"
            />
        </div>
    </div>

    <MemberAddModal
        v-if="canEdit"
        :open="showAddMember"
        :group-hashid="group.hashid"
        @close="showAddMember = false"
    />

    <TeamCreateModal
        v-if="canEdit"
        :open="showCreateTeam"
        :group-hashid="group.hashid"
        @close="showCreateTeam = false"
    />
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import DirectoryTree from './Components/DirectoryTree.vue'
import GroupDetail from './Components/GroupDetail.vue'
import MemberAddModal from './Components/MemberAddModal.vue'
import TeamCreateModal from './Components/TeamCreateModal.vue'

defineProps({
    tree: Array,
    group: Object,
    leaders: Array,
    members: Array,
    subGroups: Array,
    canEdit: Boolean,
})

const editMode = ref(false)
const showAddMember = ref(false)
const showCreateTeam = ref(false)
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
