<template>
    <Dialog :open="open" @update:open="$emit('close')">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('directory_edit_group_title') }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label for="group-name" class="text-sm font-medium mb-1 block">{{ $t('directory_group_name') }}</label>
                        <Input id="group-name" v-model="form.name" class="w-full" />
                        <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="group-desc" class="text-sm font-medium mb-1 block">{{ $t('directory_group_description') }}</label>
                        <textarea
                            id="group-desc"
                            v-model="form.description"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive mt-1">{{ form.errors.description }}</p>
                    </div>
                    <div>
                        <IconPicker v-model="form.icon" />
                        <p v-if="form.errors.icon" class="text-xs text-destructive mt-1">{{ form.errors.icon }}</p>
                    </div>
                </div>
                <DialogFooter class="mt-4">
                    <Button type="button" variant="secondary" @click="$emit('close')">{{ $t('directory_cancel') }}</Button>
                    <Button type="submit" :disabled="form.processing">{{ $t('directory_save') }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import IconPicker from './IconPicker.vue'

const props = defineProps({
    open: Boolean,
    group: Object,
})

const emit = defineEmits(['close'])

const form = useForm({
    name: props.group.name,
    description: props.group.description ?? '',
    icon: props.group.icon ?? null,
})

watch(() => props.group, (newGroup) => {
    form.name = newGroup.name
    form.description = newGroup.description ?? ''
    form.icon = newGroup.icon ?? null
}, { deep: true })

function submit() {
    form.post(route('directory.update', props.group.hashid), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close')
        },
    })
}
</script>
