<template>
    <Head :title="'Export Manager'" />

    <div class="space-y-8">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Export Manager</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select the fields you want to include in the CSV export of staff members.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 md:gap-10 border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Fields</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose which columns to include in the export.</p>
            </div>
            <div class="md:col-span-2 space-y-3">
                <button
                    type="button"
                    @click="toggleAll"
                    class="text-xs font-medium text-teal-600 dark:text-teal-400 hover:text-teal-500 dark:hover:text-teal-300"
                >
                    {{ allSelected ? 'Deselect all' : 'Select all' }}
                </button>
                <label
                    v-for="field in availableFields"
                    :key="field.value"
                    class="flex items-center gap-3 cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="field.value"
                        v-model="selectedFields"
                        class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:bg-gray-800"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ field.label }}</span>
                </label>
            </div>
        </div>

        <div class="border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
            <Button
                :disabled="selectedFields.length === 0 || downloading"
                @click="download"
            >
                {{ downloading ? 'Downloading...' : 'Download CSV' }}
            </Button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'

const availableFields = [
    { value: 'division', label: 'Division' },
    { value: 'department', label: 'Department' },
    { value: 'team', label: 'Team' },
    { value: 'username', label: 'Username' },
    { value: 'first_name', label: 'First Name' },
    { value: 'last_name', label: 'Last Name' },
    { value: 'role', label: 'Role' },
    { value: 'title', label: 'Title' },
    { value: 'credit_as', label: 'Credit As' },
    { value: 'pronouns', label: 'Pronouns' },
]

const selectedFields = ref(availableFields.map(f => f.value))
const allSelected = computed(() => selectedFields.value.length === availableFields.length)
const downloading = ref(false)

function toggleAll() {
    selectedFields.value = allSelected.value ? [] : availableFields.map(f => f.value)
}

async function download() {
    downloading.value = true
    try {
        const response = await fetch(route('export.download'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'text/csv',
            },
            body: JSON.stringify({ fields: selectedFields.value }),
        })

        if (!response.ok) throw new Error('Export failed')

        const blob = await response.blob()
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = response.headers.get('Content-Disposition')?.match(/filename="?(.+)"?/)?.[1] || 'staff-export.csv'
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(url)
    } finally {
        downloading.value = false
    }
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default {
    layout: AccountLayout,
}
</script>
