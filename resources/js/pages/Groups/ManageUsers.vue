<template>
  <div class="manage-users">
    <div class="card">
      <div class="card-header">
        <h1 class="text-2xl font-bold">Manage Users - {{ group.name }}</h1>
        <p class="text-gray-600">Manage user permissions and levels for this {{ group.type.toLowerCase() }}</p>
      </div>

      <div class="card-content">
        <!-- Current User's Permissions -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
          <h3 class="font-semibold text-blue-800 mb-2">Your Permissions</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="font-medium">Global Rank:</span>
              <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded">{{ userPermissions.globalRank }}</span>
            </div>
            <div>
              <span class="font-medium">Team Level:</span>
              <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded">{{ userPermissions.teamLevel }}</span>
            </div>
            <div>
              <span class="font-medium">Can Manage Users:</span>
              <span class="ml-2 px-2 py-1" :class="userPermissions.canManageUsers ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ userPermissions.canManageUsers ? 'Yes' : 'No' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <DataTable 
          :value="users" 
          :paginator="true" 
          :rows="10"
          :globalFilterFields="['user.full_name', 'user.email']"
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #header>
            <div class="flex justify-between">
              <h3 class="text-lg font-semibold">Group Members ({{ users.length }})</h3>
              <span class="relative">
                <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                <InputText v-model="filters['global'].value" placeholder="Search users..." class="pl-10" />
              </span>
            </div>
          </template>

          <Column field="user.avatar_url" header="Avatar" style="width: 80px">
            <template #body="slotProps">
              <img 
                :src="slotProps.data.user.avatar_url || '/images/default-avatar.png'" 
                :alt="slotProps.data.user.full_name"
                class="w-10 h-10 rounded-full object-cover"
              />
            </template>
          </Column>

          <Column field="user.full_name" header="Name" sortable>
            <template #body="slotProps">
              <div>
                <div class="font-medium">{{ slotProps.data.user.full_name }}</div>
                <div class="text-sm text-gray-500">{{ slotProps.data.user.email }}</div>
              </div>
            </template>
          </Column>

          <Column field="global_rank" header="Global Rank" sortable>
            <template #body="slotProps">
              <span class="px-2 py-1 rounded text-xs font-medium" :class="getRankBadgeClass(slotProps.data.global_rank)">
                {{ slotProps.data.global_rank }}
              </span>
            </template>
          </Column>

          <Column field="level" header="Team Level" sortable>
            <template #body="slotProps">
              <Dropdown
                v-model="slotProps.data.level"
                :options="availableLevels"
                option-label="label"
                option-value="value"
                @change="updateUserLevel(slotProps.data)"
                :disabled="!canEditUser(slotProps.data)"
                class="w-full md:w-40"
              />
            </template>
          </Column>

          <Column field="can_manage_users" header="Can Manage Users">
            <template #body="slotProps">
              <div class="flex items-center">
                <Checkbox
                  v-model="slotProps.data.can_manage_users"
                  :binary="true"
                  @change="toggleUserManagement(slotProps.data)"
                  :disabled="!canEditUser(slotProps.data)"
                />
                <span class="ml-2 text-sm">
                  {{ slotProps.data.can_manage_users ? 'Yes' : 'No' }}
                </span>
              </div>
            </template>
          </Column>

          <Column header="Actions" style="width: 120px">
            <template #body="slotProps">
              <div class="flex gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  @click="viewUser(slotProps.data)"
                  v-tooltip="'View Profile'"
                >
                  <EyeIcon class="w-4 h-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  @click="removeUser(slotProps.data)"
                  :disabled="!canRemoveUser(slotProps.data)"
                  v-tooltip="'Remove from Group'"
                  class="text-destructive hover:text-destructive"
                >
                  <TrashIcon class="w-4 h-4" />
                </Button>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Remove User Confirmation Dialog -->
    <Dialog 
      v-model:visible="showRemoveDialog" 
      :style="{width: '450px'}" 
      header="Confirm Removal" 
      :modal="true"
    >
      <div class="confirmation-content">
        <ExclamationTriangleIcon class="w-8 h-8 mr-3 text-orange-500" />
        <span v-if="userToRemove">
          Are you sure you want to remove <strong>{{ userToRemove.user.full_name }}</strong> from this {{ group.type.toLowerCase() }}?
        </span>
      </div>
      <template #footer>
        <Button variant="ghost" @click="showRemoveDialog = false">
          <TimesIcon class="w-4 h-4 mr-2" />
          Cancel
        </Button>
        <Button variant="destructive" @click="confirmRemoval">
          <CheckIcon class="w-4 h-4 mr-2" />
          Remove
        </Button>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { Search as SearchIcon, Eye as EyeIcon, Trash as TrashIcon, TriangleAlert as ExclamationTriangleIcon, X as TimesIcon, Check as CheckIcon } from 'lucide-vue-next'

import DataTable from '@Shared/components/volt/DataTable.vue'
import Column from 'primevue/column'
import { Button } from '@/components/ui/button'
import InputText from '@Shared/components/volt/InputText.vue'
import Dropdown from '@Shared/components/volt/Select.vue'
import Checkbox from '@Shared/components/volt/Checkbox.vue'
import Dialog from '@Shared/components/volt/Dialog.vue'

const props = defineProps({
  group: Object,
  users: Array,
  userPermissions: Object,
})

const toast = useToast()

// Reactive data
const showRemoveDialog = ref(false)
const userToRemove = ref(null)

// Table filters
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

// Available user levels for the dropdown
const availableLevels = [
  { label: 'Member', value: 'Member' },
  { label: 'Team Lead', value: 'TeamLead' },
  { label: 'Director', value: 'Director' },
  { label: 'Division Director', value: 'DivisionDirector' }
]

// Computed
const canManageUsers = computed(() => {
  return props.userPermissions.canManageUsers
})

// Methods
const getRankBadgeClass = (rank) => {
  switch (rank) {
    case 'Director':
      return 'bg-purple-100 text-purple-800'
    case 'Staff':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const canEditUser = (user) => {
  if (!canManageUsers.value) return false
  
  // Can't edit users with higher global rank
  if (user.global_rank === 'Director' && props.userPermissions.globalRank !== 'Director') {
    return false
  }
  
  // Can't edit users with higher team level
  const userLevel = user.level
  const myLevel = props.userPermissions.teamLevel
  
  const levelHierarchy = ['Member', 'TeamLead', 'Director', 'DivisionDirector']
  const userIndex = levelHierarchy.indexOf(userLevel)
  const myIndex = levelHierarchy.indexOf(myLevel)
  
  return myIndex >= userIndex
}

const canRemoveUser = (user) => {
  return canEditUser(user) && user.user.id !== props.userPermissions.userId
}

const updateUserLevel = async (user) => {
  if (!canEditUser(user)) {
    toast.add({
      severity: 'error',
      summary: 'Permission Denied',
      detail: 'You cannot modify this user\'s level'
    })
    return
  }

  try {
    await router.patch(route('groups.update-user-level', [props.group.id, user.user.id]), {
      level: user.level
    })
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'User level updated successfully'
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update user level'
    })
  }
}

const toggleUserManagement = async (user) => {
  if (!canEditUser(user)) {
    toast.add({
      severity: 'error',
      summary: 'Permission Denied',
      detail: 'You cannot modify this user\'s management rights'
    })
    return
  }

  try {
    if (user.can_manage_users) {
      await router.post(route('groups.grant-user-management', [props.group.id, user.user.id]))
    } else {
      await router.delete(route('groups.revoke-user-management', [props.group.id, user.user.id]))
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `User management rights ${user.can_manage_users ? 'granted' : 'revoked'}`
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update user management rights'
    })
    // Revert the checkbox
    user.can_manage_users = !user.can_manage_users
  }
}

const viewUser = (user) => {
  // Navigate to user profile or open user details modal
  router.visit(route('user.profile', user.user.id))
}

const removeUser = (user) => {
  if (!canRemoveUser(user)) {
    toast.add({
      severity: 'error',
      summary: 'Permission Denied',
      detail: 'You cannot remove this user'
    })
    return
  }
  
  userToRemove.value = user
  showRemoveDialog.value = true
}

const confirmRemoval = async () => {
  try {
    await router.delete(route('groups.members.destroy', [props.group.id, userToRemove.value.user.id]))
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'User removed from group successfully'
    })
    
    showRemoveDialog.value = false
    userToRemove.value = null
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to remove user from group'
    })
  }
}
</script>

<style scoped>
.manage-users {
  @apply p-6;
}

.card {
  @apply bg-white rounded-lg shadow-sm border;
}

.card-header {
  @apply p-6 border-b;
}

.card-content {
  @apply p-6;
}

.confirmation-content {
  @apply flex items-center;
}

:deep(.p-datatable .p-datatable-header) {
  @apply bg-gray-50 border-b;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  @apply bg-gray-50;
}

:deep(.p-dropdown) {
  @apply text-sm;
}

:deep(.p-checkbox) {
  @apply scale-90;
}
</style>
