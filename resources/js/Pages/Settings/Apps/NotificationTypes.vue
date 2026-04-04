<template>
    <Head title="Notification Types" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notification Types</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Define the notification types users can receive from {{ app.name }}. Each type is assigned to a category that determines which channels the user can opt out of.
            </p>
            <div class="mt-4">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="route('developers.show', app.id)">&larr; Back to app</Link>
                </Button>
            </div>
        </div>
        <div class="md:col-span-2 space-y-8">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ types.length }} type{{ types.length === 1 ? '' : 's' }} defined
                </p>
                <Button size="sm" @click="openCreate">+ New type</Button>
            </div>

            <div v-if="deleteError" class="rounded-lg border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                {{ deleteError }}
            </div>

            <div v-for="category in categories" :key="category.key" class="space-y-2">
                <div class="flex items-baseline gap-2">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 capitalize">{{ category.label }}</h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ category.description }}</span>
                </div>

                <div v-if="typesByCategory[category.key].length === 0" class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-4 text-xs text-gray-500 dark:text-gray-400">
                    No types yet
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="type in typesByCategory[category.key]"
                        :key="type.id"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-primary-950 p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ type.key }}</code>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ type.name }}</span>
                                    <Badge v-if="type.disabled" variant="destructive">disabled</Badge>
                                </div>
                                <p v-if="type.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ type.description }}</p>
                                <div v-if="type.default_channels && type.default_channels.length" class="mt-2 flex flex-wrap gap-1">
                                    <Badge v-for="channel in type.default_channels" :key="channel" variant="secondary">{{ channel }}</Badge>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <Button size="sm" variant="outline" @click="openEdit(type)">Edit</Button>
                                <Button v-if="!type.disabled" size="sm" variant="outline" @click="disableType(type)">Disable</Button>
                                <Button size="sm" variant="destructive" @click="deleteType(type)">Delete</Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="dialogOpen" @update:open="val => !val && closeDialog()">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ editing ? 'Edit notification type' : 'New notification type' }}</DialogTitle>
                <DialogDescription>
                    {{ editing ? 'Update the notification type. Key and category cannot be changed.' : 'Create a new notification type for this app.' }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitForm">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Key</label>
                    <Input
                        v-model="form.key"
                        type="text"
                        required
                        pattern="[a-z][a-z0-9_]*"
                        placeholder="e.g. order_shipped"
                        :disabled="editing"
                        class="font-mono bg-white dark:bg-primary-950"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lowercase letters, numbers, and underscores only.</p>
                    <p v-if="form.errors.key" class="text-xs text-destructive mt-1">{{ form.errors.key }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Name</label>
                    <Input v-model="form.name" type="text" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        class="w-full rounded-md border border-input bg-white dark:bg-primary-950 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-ring"
                    ></textarea>
                    <p v-if="form.errors.description" class="text-xs text-destructive mt-1">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Category</label>
                    <select
                        v-model="form.category"
                        :disabled="editing"
                        class="w-full rounded-md border border-input bg-white dark:bg-primary-950 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60"
                    >
                        <option v-for="cat in categories" :key="cat.key" :value="cat.key">
                            {{ cat.label }} — {{ cat.description }}
                        </option>
                    </select>
                    <p v-if="form.errors.category" class="text-xs text-destructive mt-1">{{ form.errors.category }}</p>
                </div>

                <div v-if="form.category === 'transactional'" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30 p-3">
                    <p class="text-xs text-yellow-800 dark:text-yellow-200">
                        Transactional notifications cannot be disabled by users, and email is always required as a delivery channel.
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Default channels</label>
                    <div class="space-y-2">
                        <div v-for="channel in availableChannels" :key="channel" class="flex items-center gap-2">
                            <Checkbox
                                :id="'channel-' + channel"
                                :model-value="form.default_channels.includes(channel)"
                                :disabled="channel === 'email' && form.category === 'transactional'"
                                @update:model-value="toggleChannel(channel)"
                            />
                            <label :for="'channel-' + channel" class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ channel }}</label>
                        </div>
                    </div>
                    <p v-if="form.errors.default_channels" class="text-xs text-destructive mt-1">{{ form.errors.default_channels }}</p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeDialog">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create type' }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import { Checkbox } from '@/Components/ui/checkbox'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    app: Object,
    types: Array,
})

const categories = [
    { key: 'transactional', label: 'Transactional', description: 'Account and security notifications users cannot disable' },
    { key: 'operational', label: 'Operational', description: 'Important service notifications users can opt out of' },
    { key: 'informational', label: 'Informational', description: 'Updates and newsletters' },
    { key: 'promotional', label: 'Promotional', description: 'Marketing and promotional messages' },
]

const availableChannels = ['email', 'telegram', 'database']

const typesByCategory = computed(() => {
    const grouped = { transactional: [], operational: [], informational: [], promotional: [] }
    for (const type of props.types || []) {
        if (grouped[type.category]) {
            grouped[type.category].push(type)
        }
    }
    return grouped
})

const dialogOpen = ref(false)
const editing = ref(null)
const deleteError = ref(null)

const form = useForm({
    key: '',
    name: '',
    description: '',
    category: 'operational',
    default_channels: ['email'],
})

watch(
    () => form.category,
    (cat) => {
        if (cat === 'transactional' && !form.default_channels.includes('email')) {
            form.default_channels.push('email')
        }
    }
)

function resetForm() {
    form.reset()
    form.clearErrors()
    editing.value = null
}

function openCreate() {
    resetForm()
    form.category = 'operational'
    form.default_channels = ['email']
    dialogOpen.value = true
}

function openEdit(type) {
    editing.value = type
    form.key = type.key
    form.name = type.name
    form.description = type.description || ''
    form.category = type.category
    form.default_channels = [...(type.default_channels || [])]
    form.clearErrors()
    dialogOpen.value = true
}

function closeDialog() {
    dialogOpen.value = false
    resetForm()
}

function toggleChannel(channel) {
    const idx = form.default_channels.indexOf(channel)
    if (idx === -1) {
        form.default_channels.push(channel)
    } else {
        form.default_channels.splice(idx, 1)
    }
}

function submitForm() {
    if (editing.value) {
        form.put(`/developers/${props.app.id}/notification-types/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeDialog(),
        })
    } else {
        form.post(`/developers/${props.app.id}/notification-types`, {
            preserveScroll: true,
            onSuccess: () => closeDialog(),
        })
    }
}

function disableType(type) {
    if (!confirm(`Disable notification type "${type.name}"? Users will no longer receive this notification.`)) {
        return
    }
    router.post(
        `/developers/${props.app.id}/notification-types/${type.id}/disable`,
        {},
        { preserveScroll: true }
    )
}

function deleteType(type) {
    if (!confirm(`Permanently delete notification type "${type.name}"? This cannot be undone.`)) {
        return
    }
    deleteError.value = null
    router.delete(`/developers/${props.app.id}/notification-types/${type.id}`, {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.type) {
                deleteError.value = errors.type
            }
        },
    })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
