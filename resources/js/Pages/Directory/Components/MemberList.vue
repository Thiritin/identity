<template>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('directory_members') }}
                <span class="text-gray-400 font-normal">({{ members.length }})</span>
            </h3>
            <slot name="actions" />
        </div>
        <div v-if="members.length > 10" class="mb-3">
            <Input
                v-model="search"
                :placeholder="$t('directory_search_members')"
                class="h-8 text-sm bg-white dark:bg-gray-900"
            />
        </div>
        <div class="space-y-0.5">
            <MemberRow
                v-for="member in filtered"
                :key="member.hashid"
                :member="member"
            >
                <template v-if="canEdit" #actions>
                    <slot name="member-actions" :member="member" />
                </template>
            </MemberRow>
        </div>
        <p v-if="filtered.length === 0" class="text-sm text-gray-400 py-4 text-center">
            {{ search ? 'No members found.' : 'No members.' }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Input } from '@/Components/ui/input'
import MemberRow from './MemberRow.vue'

const props = defineProps({
    members: Array,
    canEdit: Boolean,
})

const search = ref('')

const filtered = computed(() => {
    if (!search.value) return props.members
    const q = search.value.toLowerCase()
    return props.members.filter(m =>
        m.name.toLowerCase().includes(q) ||
        (m.title && m.title.toLowerCase().includes(q))
    )
})
</script>
