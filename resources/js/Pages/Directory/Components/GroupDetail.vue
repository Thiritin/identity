<template>
    <div class="space-y-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <Badge variant="outline" class="text-xs capitalize">{{ group.type }}</Badge>
                <component :is="iconMap[group.icon]" v-if="group.icon && iconMap[group.icon]" class="h-5 w-5 text-gray-400" />
                <Button v-if="canEdit" variant="ghost" size="sm" @click="$emit('toggle-edit')">
                    <Pencil class="h-3.5 w-3.5 mr-1" /> {{ $t('directory_edit_group') }}
                </Button>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ group.name }}</h1>
        </div>

        <div v-if="leaders.length > 0" class="flex flex-wrap gap-3">
            <Link
                v-for="leader in leaders"
                :key="leader.hashid"
                :href="route('directory.members.show', leader.hashid)"
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-primary/5 dark:bg-primary/10 hover:bg-primary/10 dark:hover:bg-primary/20 transition-colors"
            >
                <div class="h-7 w-7 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0">
                    <img v-if="leader.avatar" :src="leader.avatar" :alt="leader.name" class="h-full w-full object-cover" />
                    <div v-else class="h-full w-full flex items-center justify-center text-xs font-medium text-gray-500">
                        {{ leader.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ leader.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ leader.title || levelLabel(leader.level) }}</div>
                </div>
            </Link>
        </div>

        <img v-if="group.logo_url" :src="group.logo_url" :alt="group.name" class="w-full max-h-48 object-cover rounded-lg" />

        <GroupDescription :description="group.description" />

        <MemberList :members="members" :can-edit="canEdit">
            <template v-if="canEdit" #actions>
                <Button variant="outline" size="sm" @click="$emit('add-member')">
                    <UserPlus class="h-3.5 w-3.5 mr-1" /> {{ $t('directory_add_member') }}
                </Button>
            </template>
        </MemberList>

        <SubGroupList :groups="subGroups">
            <template v-if="canEdit" #actions>
                <Button variant="outline" size="sm" @click="$emit('create-sub-group')">
                    <Plus class="h-3.5 w-3.5 mr-1" />
                    {{ group.type === 'division' ? $t('directory_create_department') : $t('directory_create_team') }}
                </Button>
            </template>
        </SubGroupList>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { trans } from 'laravel-vue-i18n'
import { Pencil, UserPlus, Plus } from 'lucide-vue-next'
import { iconMap } from './iconMap'
import GroupDescription from './GroupDescription.vue'
import MemberList from './MemberList.vue'
import SubGroupList from './SubGroupList.vue'

defineProps({
    group: Object,
    leaders: Array,
    members: Array,
    subGroups: Array,
    canEdit: Boolean,
})

defineEmits(['toggle-edit', 'add-member', 'create-sub-group'])

function levelLabel(level) {
    const val = typeof level === 'object' ? level.value ?? level : level
    return val ? trans(`level_${val}`) : ''
}
</script>
