<script setup>
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";
import {Head} from "@inertiajs/vue3";
import {ref} from "vue";
import ListItem from "@/Components/ListItem.vue";
import BaseButton from "@/Components/BaseButton.vue";
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Trash2 } from 'lucide-vue-next';
import {useForm} from '@inertiajs/vue3'

const disableKeyId = ref(null)
const showCreateField = ref(false)

const props = defineProps({
    keys: {
        type: Array,
        default: []
    },
})

const enableForm = useForm('post', route('settings.two-factor.yubikey.store'), {
    code: '',
    name: '',
})

const disableForm = useForm('delete', route('settings.two-factor.yubikey.destroy'), {
    keyId: '',
    password: '',
})

function submitForm() {
    enableForm.submit({
        onSuccess: () => {
            form.reset()
        }
    })
}

function submitDisableForm() {
    disableForm.keyId = disableKeyId.value;
    disableForm.submit()
}
</script>

<template>
    <Head :title="$t('yubikey_setup_title')"></Head>
    <div v-if="props.keys.length === 0 || showCreateField === true">
        <SettingsHeader>{{ $t('yubikey_setup_header') }}</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">{{ $t('yubikey_setup_description') }}
        </SettingsSubHeader>
        <form @submit.prevent="submitForm()" class="space-y-4">
            <div class="flex flex-col gap-2">
                <label for="code">{{ $t('yubikey_otp') }}</label>
                <Input id="code"
                       type="text"
                       @keydown.enter.capture="null"
                       autocomplete="code"
                       @change="enableForm.validate('code')"
                       :class="{ 'border-destructive': enableForm.invalid('code') }"
                       v-model.trim.lazy="enableForm.code"
                />
                <p v-if="enableForm.invalid('code')" class="text-sm text-destructive">{{ enableForm.errors.code }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <label for="name">{{ $t('yubikey_name_label') }}</label>
                <Input id="name"
                       type="text"
                       :placeholder="$t('yubikey_name_placeholder')"
                       autocomplete="name"
                       @change="enableForm.validate('name')"
                       :class="{ 'border-destructive': enableForm.invalid('name') }"
                       v-model.trim.lazy="enableForm.name"
                />
                <p v-if="enableForm.invalid('name')" class="text-sm text-destructive">{{ enableForm.errors.name }}</p>
            </div>

            <div class="flex justify-end">
                <Button
                    :disabled="enableForm.processing"
                    type="submit"
                    class="block"
                >{{ $t('submit') }}</Button>
            </div>
        </form>
    </div>
    <div v-else-if="disableKeyId !== null">
        <SettingsHeader>{{ $t('authenticator_disable_header') }}</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">{{ $t('yubikey_disable_description') }}
        </SettingsSubHeader>
        <form @submit.prevent="submitDisableForm" class="space-y-6">
            <div class="flex flex-col gap-2">
                <label for="password">{{ $t('current_password') }}</label>
                <Input id="password"
                       type="password"
                       autocomplete="password"
                       @change="disableForm.validate('password')"
                       :class="{ 'border-destructive': disableForm.invalid('password') }"
                       v-model.trim.lazy="disableForm.password"
                />
                <p v-if="disableForm.invalid('password')" class="text-sm text-destructive">{{ disableForm.errors.password }}</p>
            </div>
            <div class="flex justify-end gap-3">
                <Button variant="secondary" size="sm" @click="disableKeyId = null">{{ $t('cancel') }}</Button>
                <Button size="sm" type="submit">{{ $t('submit') }}</Button>
            </div>
        </form>
    </div>
    <div v-else>
        <SettingsHeader>{{ $t('yubikey_manage_header') }}</SettingsHeader>
        <SettingsSubHeader
            class="mb-4">{{ $t('yubikey_manage_description') }}
        </SettingsSubHeader>
        <div class="divide-y divide-gray-300 mb-4">
            <ListItem v-for="key in keys" class="items-center">
                <div>
                    <div class="font-semibold">{{ key.name }}</div>
                    <div class="text-sm">{{ key.last_used_at }}</div>
                </div>
                <div>
                    <Button variant="ghost" size="sm" @click="disableKeyId = key.id">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </ListItem>
        </div>
        <div class="flex justify-end">
            <BaseButton @click="showCreateField = true" primary>{{ $t('add_new') }}</BaseButton>
        </div>
    </div>
</template>

<style scoped>

</style>

<script>
import AuthLayout from "@/Layouts/AuthLayout.vue";

export default {
    layout: AuthLayout
}
</script>
