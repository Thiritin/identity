<template>
    <div v-if="description" class="relative">
        <div
            ref="container"
            class="prose prose-sm dark:prose-invert max-w-none overflow-hidden transition-[max-height] duration-300"
            :style="{ maxHeight: expanded ? 'none' : '300px' }"
            v-html="renderedMarkdown"
        />
        <div v-if="needsTruncation && !expanded" class="absolute bottom-0 inset-x-0 h-16 bg-gradient-to-t from-white dark:from-gray-900 to-transparent" />
        <button
            v-if="needsTruncation"
            type="button"
            class="text-sm text-primary hover:underline mt-1"
            @click="expanded = !expanded"
        >
            {{ expanded ? $t('directory_view_less') : $t('directory_view_more') }}
        </button>
    </div>
    <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">{{ $t('directory_no_description') }}</p>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const props = defineProps({ description: String })

const expanded = ref(false)
const container = ref(null)
const needsTruncation = ref(false)

const renderedMarkdown = computed(() => {
    if (!props.description) return ''
    return DOMPurify.sanitize(marked.parse(props.description))
})

onMounted(async () => {
    await nextTick()
    if (container.value) {
        needsTruncation.value = container.value.scrollHeight > 300
    }
})
</script>
