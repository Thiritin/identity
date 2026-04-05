<template>
    <Head title="Notification Preferences" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notification Preferences</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Choose how you want to receive notifications. Transactional messages always deliver via their default channels and cannot be turned off.
            </p>
        </div>

        <div class="md:col-span-2 space-y-8">
            <!-- Master channels -->
            <section>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Master channels</h4>
                <div class="rounded-lg border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
                    <label
                        v-for="channel in channels"
                        :key="channel.key"
                        class="flex items-center justify-between px-4 py-3 cursor-pointer"
                    >
                        <div>
                            <div class="text-sm font-medium">{{ channel.label }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ channel.description }}</div>
                        </div>
                        <Switch v-model="form.channels[channel.key]" />
                    </label>
                </div>
            </section>

            <!-- Per-app matrix -->
            <section v-for="(types, appName) in groupedTypes" :key="appName">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">{{ appName }}</h4>
                <div class="rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="text-left font-medium px-4 py-2">Notification</th>
                                <th
                                    v-for="channel in channels"
                                    :key="channel.key"
                                    class="font-medium px-3 py-2 w-20 text-center"
                                >
                                    {{ channel.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="type in types" :key="type.id" class="align-top">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-medium">{{ type.name }}</div>
                                        <span
                                            v-if="type.category === 'transactional'"
                                            class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-[10px] font-medium px-1.5 py-0.5"
                                            title="Transactional — always delivered"
                                        >
                                            Required
                                        </span>
                                    </div>
                                    <div v-if="type.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ type.description }}
                                    </div>
                                </td>
                                <td
                                    v-for="channel in channels"
                                    :key="channel.key"
                                    class="px-3 py-3 text-center"
                                >
                                    <template v-if="type.default_channels.includes(channel.key)">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 disabled:opacity-60"
                                            :checked="isChecked(type, channel.key)"
                                            :disabled="type.category === 'transactional' || !form.channels[channel.key]"
                                            @change="toggle(type, channel.key)"
                                            :title="
                                                type.category === 'transactional'
                                                    ? 'Always delivered'
                                                    : !form.channels[channel.key]
                                                        ? channel.label + ' is off in master channels'
                                                        : ''
                                            "
                                        />
                                    </template>
                                    <span v-else class="text-gray-300 dark:text-gray-700 select-none">–</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="flex justify-end">
                <Button @click="save" :disabled="form.processing">Save preferences</Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Switch } from '@/Components/ui/switch'

const props = defineProps({
    preferences: Object,
    groupedTypes: Object,
})

const channels = [
    { key: 'email', label: 'Email', description: 'Delivered to your registered email address.' },
    { key: 'telegram', label: 'Telegram', description: 'Sent via the Eurofurence Telegram bot.' },
    { key: 'database', label: 'Inbox', description: 'Shown in the notification bell and inbox on this site.' },
]

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
