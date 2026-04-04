<template>
    <div class="space-y-3">
        <div v-if="attendance.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 font-medium">{{ $t('convention_attendance') }}</th>
                        <th class="pb-2 font-medium text-center">{{ $t('convention_attendance_attended') }}</th>
                        <th class="pb-2 font-medium text-center">{{ $t('convention_attendance_staff') }}</th>
                        <th v-if="!readonly" class="pb-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in attendance" :key="entry.id" class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 pr-4">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ entry.name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 ml-1">({{ entry.year }})</span>
                        </td>
                        <td class="py-2 text-center">
                            <Checkbox
                                v-if="!readonly"
                                :model-value="entry.is_attended"
                                @update:model-value="(val) => onUpdate(entry, 'is_attended', val)"
                            />
                            <Badge v-else-if="entry.is_attended" variant="secondary" class="text-xs">{{ $t('convention_attendance_attended') }}</Badge>
                        </td>
                        <td class="py-2 text-center">
                            <Checkbox
                                v-if="canManage"
                                :model-value="entry.is_staff"
                                @update:model-value="(val) => onUpdate(entry, 'is_staff', val)"
                            />
                            <Badge v-else-if="entry.is_staff" class="text-xs bg-amber-500/10 text-amber-600">{{ $t('convention_attendance_staff') }}</Badge>
                        </td>
                        <td v-if="!readonly" class="py-2 text-right">
                            <button
                                v-if="canManage || !entry.is_staff"
                                type="button"
                                class="text-gray-400 hover:text-destructive transition-colors"
                                :title="$t('convention_attendance_remove')"
                                @click="onRemove(entry)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                            <span
                                v-else
                                class="text-xs text-gray-400"
                                :title="$t('convention_attendance_cannot_remove_staff')"
                            >
                                <Lock class="h-3.5 w-3.5" />
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400">{{ $t('convention_attendance_no_entries') }}</p>

        <div v-if="!readonly && availableConventions.length > 0" class="flex items-center gap-2">
            <Select v-model="selectedConventionId">
                <SelectTrigger class="w-full max-w-xs bg-white dark:bg-primary-950">
                    <SelectValue :placeholder="$t('convention_attendance_add')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="conv in availableConventions" :key="conv.id" :value="String(conv.id)">
                        {{ conv.name }} ({{ conv.year }})
                    </SelectItem>
                </SelectContent>
            </Select>
            <Button type="button" size="sm" :disabled="!selectedConventionId" @click="onAdd">
                {{ $t('convention_attendance_add') }}
            </Button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Checkbox } from '@/Components/ui/checkbox'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import { X, Lock } from 'lucide-vue-next'

const props = defineProps({
    attendance: Array,
    allConventions: Array,
    canManage: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    endpoint: { type: String, required: true },
})

const selectedConventionId = ref(null)

const availableConventions = computed(() => {
    if (!props.allConventions) return []
    const linkedIds = new Set(props.attendance.map(a => a.id))
    return props.allConventions.filter(c => !linkedIds.has(c.id))
})

function onAdd() {
    if (!selectedConventionId.value) return
    router.post(props.endpoint, {
        action: 'add',
        convention_id: parseInt(selectedConventionId.value),
    }, {
        preserveScroll: true,
        onSuccess: () => { selectedConventionId.value = null },
    })
}

function onUpdate(entry, field, value) {
    const data = {
        action: 'update',
        convention_id: entry.id,
    }
    data[field] = value
    router.post(props.endpoint, data, { preserveScroll: true })
}

function onRemove(entry) {
    router.post(props.endpoint, {
        action: 'remove',
        convention_id: entry.id,
    }, { preserveScroll: true })
}
</script>
