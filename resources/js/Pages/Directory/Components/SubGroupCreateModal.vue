<template>
    <Dialog :open="open" @update:open="$emit('close')">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label for="team-name" class="text-sm font-medium mb-1 block">Name</label>
                        <Input id="team-name" v-model="form.name" class="w-full" />
                        <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="team-desc" class="text-sm font-medium mb-1 block">Description</label>
                        <textarea
                            id="team-desc"
                            v-model="form.description"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                    </div>
                </div>
                <DialogFooter class="mt-4">
                    <Button type="button" variant="secondary" @click="$emit('close')">{{ $t('directory_cancel') }}</Button>
                    <Button type="submit" :disabled="form.processing">{{ title }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'

const props = defineProps({
    open: Boolean,
    groupHashid: String,
    routeName: { type: String, required: true },
    title: { type: String, required: true },
})

const emit = defineEmits(['close'])

const form = useForm({
    name: '',
    description: '',
})

function submit() {
    form.post(route(props.routeName, props.groupHashid), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('close')
        },
    })
}
</script>
