<template>
    <a
        :href="app.url"
        class="group flex w-36 flex-col items-center gap-2"
    >
        <div class="relative h-36 w-36 overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 shadow-sm transition-shadow group-hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
            <img
                v-if="app.image_url"
                :src="app.image_url"
                :alt="app.name"
                class="h-full w-full object-cover"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-4xl font-semibold text-gray-400 dark:text-gray-500"
            >
                {{ app.name?.charAt(0)?.toUpperCase() }}
            </div>

            <Tooltip v-if="app.description">
                <TooltipTrigger as-child>
                    <button
                        type="button"
                        class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/40 text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100"
                        @click.prevent
                    >
                        <Info class="h-3.5 w-3.5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="bottom" class="max-w-xs">
                    {{ app.description }}
                </TooltipContent>
            </Tooltip>
        </div>
        <span class="w-full truncate text-center text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ app.name }}
        </span>
    </a>
</template>

<script setup>
import { Info } from 'lucide-vue-next'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip'

defineProps({
    app: {
        type: Object,
        required: true,
    },
})
</script>
