<template>
    <Dialog :open="open" @update:open="$emit('close')">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ member?.name }}</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium mb-1 block">{{ $t('staff_profile_title') }}</label>
                    <Input v-model="form.title" :placeholder="$t('staff_profile_title_placeholder')" />
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">{{ $t('staff_profile_level') }}</label>
                    <Select v-model="form.level">
                        <SelectTrigger>
                            <span>{{ levelLabel }}</span>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="lvl in assignableLevels" :key="lvl" :value="lvl" :text-value="$t('level_' + lvl)">{{ $t('level_' + lvl) }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox id="edit_can_manage" v-model="form.can_manage_members" />
                    <label for="edit_can_manage" class="text-sm">{{ $t('staff_profile_can_manage_members') }}</label>
                </div>
            </div>
            <DialogFooter class="mt-4">
                <Button type="button" variant="destructive" size="sm" @click="removeMember">
                    {{ $t('staff_profile_remove_from_group') }}
                </Button>
                <div class="flex-1" />
                <Button type="button" variant="secondary" @click="$emit('close')">{{ $t('directory_cancel') }}</Button>
                <Button type="button" :disabled="processing" @click="save">{{ $t('save') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'

const props = defineProps({
    open: Boolean,
    member: Object,
    groupHashid: String,
    assignableLevels: { type: Array, default: () => ['member'] },
})

const emit = defineEmits(['close'])

const form = ref({
    title: '',
    level: 'member',
    can_manage_members: false,
})

const processing = ref(false)

const levelLabel = computed(() => trans('level_' + (form.value.level ?? 'member')))

watch(() => props.member, (m) => {
    if (m) {
        const level = typeof m.level === 'object' ? m.level?.value ?? m.level : m.level
        form.value = {
            title: m.title ?? '',
            level: level ?? 'member',
            can_manage_members: !!m.can_manage_members,
        }
    }
}, { immediate: true })

function save() {
    processing.value = true
    router.patch(
        route('directory.members.update', { group: props.groupHashid, user: props.member.hashid }),
        {
            level: form.value.level,
            title: form.value.title || null,
            can_manage_members: form.value.can_manage_members,
        },
        {
            preserveScroll: true,
            onFinish: () => { processing.value = false },
            onSuccess: () => emit('close'),
        },
    )
}

function removeMember() {
    if (!confirm('Are you sure?')) return
    router.delete(
        route('directory.members.destroy', { group: props.groupHashid, user: props.member.hashid }),
        { preserveScroll: true },
    )
}
</script>
