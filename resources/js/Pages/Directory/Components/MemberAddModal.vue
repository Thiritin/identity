<template>
    <Dialog :open="open" @update:open="$emit('close')">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('directory_add_member') }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">{{ $t('directory_search_staff') }}</label>
                        <Command v-model="selectedUser" class="rounded-md border">
                            <CommandInput :placeholder="$t('directory_search_staff')" />
                            <CommandList class="max-h-48">
                                <CommandEmpty>No users found.</CommandEmpty>
                                <CommandGroup>
                                    <CommandItem
                                        v-for="user in staffMembers"
                                        :key="user.id"
                                        :value="user.hashid ?? user.id"
                                    >
                                        {{ user.name }}
                                    </CommandItem>
                                </CommandGroup>
                            </CommandList>
                        </Command>
                        <p v-if="form.errors.user_hashid" class="text-xs text-destructive mt-1">{{ form.errors.user_hashid }}</p>
                    </div>
                </div>
                <DialogFooter class="mt-4">
                    <Button type="button" variant="secondary" @click="$emit('close')">{{ $t('directory_cancel') }}</Button>
                    <Button type="submit" :disabled="form.processing || !selectedUser">{{ $t('directory_add_member') }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command'

const props = defineProps({
    open: Boolean,
    groupHashid: String,
})

const emit = defineEmits(['close'])

const page = usePage()
const staffMembers = computed(() => page.props.staffMemberList ?? [])

const selectedUser = ref(null)

const form = useForm({
    user_hashid: '',
})

function submit() {
    form.user_hashid = selectedUser.value
    form.post(route('directory.members.store', props.groupHashid), {
        preserveScroll: true,
        onSuccess: () => {
            selectedUser.value = null
            form.reset()
            emit('close')
        },
    })
}
</script>
