<template>
    <Head title="Notification Preferences" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notification Preferences</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Choose how you want to receive notifications from each app. Transactional notifications always deliver via their default channels and cannot be disabled.
            </p>
        </div>
        <div class="md:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-4 space-y-3">
                <h4 class="text-sm font-semibold">Master channels</h4>
                <div class="flex gap-6">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" v-model="form.channels.email" /> Email
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" v-model="form.channels.telegram" /> Telegram
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" v-model="form.channels.database" /> In-app
                    </label>
                </div>
            </div>

            <div v-for="(types, appName) in groupedTypes" :key="appName" class="rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <h4 class="text-sm font-semibold mb-3">{{ appName }}</h4>
                <div v-for="type in types" :key="type.id" class="py-2 border-b last:border-0 border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium">{{ type.name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ type.description }}</div>
                            <div v-if="type.category === 'transactional'" class="text-xs text-yellow-600 mt-1">Always delivered (transactional)</div>
                        </div>
                        <div class="flex gap-3">
                            <label
                                v-for="channel in type.default_channels"
                                :key="channel"
                                class="inline-flex items-center gap-1 text-xs"
                                :class="{ 'opacity-50 cursor-not-allowed': type.category === 'transactional' }"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isChecked(type, channel)"
                                    :disabled="type.category === 'transactional'"
                                    @change="toggle(type, channel)"
                                />
                                {{ channel }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button @click="save" :disabled="form.processing">Save preferences</Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'

const props = defineProps({
    preferences: Object,
    groupedTypes: Object,
})

const form = useForm({
    channels: { ...props.preferences.channels },
    types: { ...props.preferences.types },
})

function isChecked(type, channel) {
    if (type.category === 'transactional') return true
    const override = form.types[String(type.id)]
    if (override && Object.prototype.hasOwnProperty.call(override, channel)) {
        return override[channel]
    }
    return type.default_channels.includes(channel)
}

function toggle(type, channel) {
    if (type.category === 'transactional') return
    const id = String(type.id)
    if (!form.types[id]) form.types[id] = {}
    form.types[id] = { ...form.types[id], [channel]: !isChecked(type, channel) }
}

function save() {
    form.post('/settings/notifications')
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
