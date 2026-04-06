<template>
    <Head :title="group.name" />
    <div>
        <GroupDetail
                :group="group"
                :leaders="leaders"
                :members="members"
                :sub-groups="subGroups"
                :can-edit="canEdit"
                :can-create-child-group="canCreateChildGroup"
                @toggle-edit="showEdit = true"
                @add-member="showAddMember = true"
                @create-sub-group="showCreateSubGroup = true"
                @edit-member="(m) => { editingMember = m; showEditMember = true }"
            />
    </div>

    <MemberAddModal
        v-if="canEdit"
        :open="showAddMember"
        :group-hashid="group.hashid"
        :group-type="group.type"
        :assignable-levels="assignableLevels"
        @close="showAddMember = false"
    />

    <SubGroupCreateModal
        v-if="canCreateChildGroup"
        :open="showCreateSubGroup"
        :group-hashid="group.hashid"
        :route-name="childGroupRouteName"
        :title="childGroupLabel"
        @close="showCreateSubGroup = false"
    />

    <GroupEditModal
        v-if="canEdit"
        :open="showEdit"
        :group="group"
        @close="showEdit = false"
    />

    <MemberEditModal
        v-if="canEdit"
        :open="showEditMember"
        :member="editingMember"
        :group-hashid="group.hashid"
        :assignable-levels="assignableLevels"
        :group-type="group.type"
        @close="showEditMember = false"
    />
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import GroupDetail from './Components/GroupDetail.vue'
import MemberAddModal from './Components/MemberAddModal.vue'
import SubGroupCreateModal from './Components/SubGroupCreateModal.vue'
import GroupEditModal from './Components/GroupEditModal.vue'
import MemberEditModal from './Components/MemberEditModal.vue'

const props = defineProps({
    group: Object,
    leaders: Array,
    members: Array,
    subGroups: Array,
    canEdit: Boolean,
    assignableLevels: Array,
    canCreateChildGroup: Boolean,
    childGroupType: { type: String, default: null },
})

const childGroupRouteName = computed(() =>
    props.childGroupType === 'department' ? 'directory.departments.store' : 'directory.teams.store'
)
const childGroupLabel = computed(() =>
    props.childGroupType === 'department' ? trans('directory_create_department') : trans('directory_create_team')
)

const showEdit = ref(false)
const showAddMember = ref(false)
const showCreateSubGroup = ref(false)
const showEditMember = ref(false)
const editingMember = ref(null)
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
