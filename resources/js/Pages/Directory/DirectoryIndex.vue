<template>
    <Head :title="$t('tab_directory')" />
    <div class="flex flex-col lg:flex-row gap-6">
        <DirectoryTree :tree="tree" :selected="null" class="lg:w-72 shrink-0" />
        <div class="flex-1 flex items-center justify-center min-h-[400px]">
            <div class="text-center max-w-lg px-6">
                <BookUser class="h-12 w-12 mx-auto mb-4 text-primary/40" />
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    {{ $t('directory_welcome_title') }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                    {{ $t('directory_welcome_description') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link :href="route('settings.profile')" class="inline-flex">
                        <Button>{{ $t('directory_manage_profile') }}</Button>
                    </Link>
                    <Link v-if="rootHashid" :href="route('directory.show', rootHashid)" class="inline-flex">
                        <Button variant="outline">{{ $t('directory_view_directory') }}</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { BookUser } from 'lucide-vue-next'
import DirectoryTree from './Components/DirectoryTree.vue'

defineProps({
    tree: Array,
    rootHashid: String,
})
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
