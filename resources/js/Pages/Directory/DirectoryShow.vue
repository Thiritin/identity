<template>
    <Head :title="group.name" />
    <div>
        <GroupDetail
                :group="group"
                :leaders="leaders"
                :members="members"
                :sub-groups="subGroups"
                :can-edit="canEdit"
                @toggle-edit="showEdit = true"
                @add-member="showAddMember = true"
                @create-sub-group="showCreateTeam = true"
            />
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

    <GroupEditModal
        v-if="canEdit"
        :open="showEdit"
        :group="group"
        @close="showEdit = false"
    />
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import GroupDetail from './Components/GroupDetail.vue'
import MemberAddModal from './Components/MemberAddModal.vue'
import TeamCreateModal from './Components/TeamCreateModal.vue'
import GroupEditModal from './Components/GroupEditModal.vue'

defineProps({
    group: Object,
    leaders: Array,
    members: Array,
    subGroups: Array,
    canEdit: Boolean,
})

const showEdit = ref(false)
const showAddMember = ref(false)
const showCreateTeam = ref(false)
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
