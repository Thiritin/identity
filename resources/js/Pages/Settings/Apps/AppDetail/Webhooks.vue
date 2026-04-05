<template>
    <Head title="Webhooks" />
    <AppDetailLayout :app="app" active-key="webhooks">
        <div class="space-y-6">
            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Webhook URL</label>
                    <Input v-model="form.webhook_url" type="url" placeholder="https://" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.webhook_url" class="text-xs text-destructive mt-1">{{ form.errors.webhook_url }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Event</label>
                    <Input v-model="form.webhook_event_name" type="text" placeholder="user.updated" class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.webhook_event_name" class="text-xs text-destructive mt-1">{{ form.errors.webhook_event_name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Subscribed fields</label>
                    <div class="space-y-2">
                        <div v-for="field in availableFields" :key="field" class="flex items-center gap-2">
                            <Checkbox
                                :id="'field-' + field"
                                :model-value="form.webhook_subscribed_fields.includes(field)"
                                @update:model-value="toggleField(field)"
                            />
                            <label :for="'field-' + field" class="text-sm text-gray-700 dark:text-gray-300">{{ field }}</label>
                        </div>
                    </div>
                    <p v-if="form.errors.webhook_subscribed_fields" class="text-xs text-destructive mt-1">{{ form.errors.webhook_subscribed_fields }}</p>
                </div>

                <div v-if="app.has_secret">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Signing secret</label>
                    <div class="flex gap-2">
                        <Input
                            :model-value="revealedSecret || '••••••••••••'"
                            readonly
                            class="flex-1 font-mono text-sm bg-gray-50 dark:bg-primary-950"
                        />
                        <Button type="button" variant="outline" size="sm" :disabled="revealing" @click="revealSecret">
                            Reveal
                        </Button>
                        <Button type="button" variant="outline" size="sm" :disabled="rotating" @click="rotateSecret">
                            Rotate
                        </Button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="!app.webhook_url || !app.has_secret || sendingTest"
                        @click="sendTest"
                    >
                        Send test delivery
                    </Button>
                </div>
            </form>

            <DeliveriesTable :app-id="app.id" ref="deliveriesTable" />
        </div>
    </AppDetailLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import AppDetailLayout from './Layout.vue'
import DeliveriesTable from './DeliveriesTable.vue'

const props = defineProps({
    app: Object,
})

const availableFields = ['email', 'username']

const form = useForm({
    webhook_url: props.app.webhook_url || '',
    webhook_event_name: props.app.webhook_event_name || '',
    webhook_subscribed_fields: [...(props.app.webhook_subscribed_fields || [])],
})

const revealedSecret = ref(null)
const revealing = ref(false)
const rotating = ref(false)
const sendingTest = ref(false)
const deliveriesTable = ref(null)

function csrf() {
    return {
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
}

function toggleField(field) {
    const idx = form.webhook_subscribed_fields.indexOf(field)
    if (idx === -1) {
        form.webhook_subscribed_fields.push(field)
    } else {
        form.webhook_subscribed_fields.splice(idx, 1)
    }
}

function submit() {
    form.put(route('developers.webhooks.update', props.app.id))
}

async function revealSecret() {
    revealing.value = true
    try {
        const res = await fetch(route('developers.webhooks.reveal-secret', props.app.id), {
            method: 'POST',
            headers: csrf(),
        })
        const data = await res.json()
        revealedSecret.value = data.secret
    } finally {
        revealing.value = false
    }
}

async function rotateSecret() {
    if (!confirm('Are you sure you want to rotate the signing secret? The old secret will stop working immediately.')) {
        return
    }
    rotating.value = true
    try {
        const res = await fetch(route('developers.webhooks.rotate-secret', props.app.id), {
            method: 'POST',
            headers: csrf(),
        })
        const data = await res.json()
        revealedSecret.value = data.secret
    } finally {
        rotating.value = false
    }
}

async function sendTest() {
    sendingTest.value = true
    try {
        await fetch(route('developers.webhooks.test', props.app.id), {
            method: 'POST',
            headers: csrf(),
        })
        deliveriesTable.value?.reload()
    } finally {
        sendingTest.value = false
    }
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'

export default {
    layout: AccountLayout,
}
</script>
