<template>
    <div class="space-y-0.5" :class="$attrs.class">
        <div class="flex items-center justify-between px-3 mb-2">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $t('tab_directory') }}
            </h3>
        </div>
        <Link
            v-if="myGroupCount > 0"
            :href="route('directory.index')"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-100 dark:hover:bg-white/10 mb-1"
            :class="selected === null ? 'bg-primary/10 text-primary font-medium dark:bg-primary/20' : 'text-gray-700 dark:text-gray-300'"
        >
            <UserCircle class="h-4 w-4 shrink-0" />
            <span class="truncate flex-1">{{ $t('directory_my_groups') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500 tabular-nums">{{ myGroupCount }}</span>
        </Link>
        <div class="border-t border-gray-200 dark:border-gray-700 my-1.5" />
        <div class="flex items-center justify-between px-3 mb-1">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $t('directory_divisions') }}
            </h4>
            <button
                type="button"
                class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                @click="showAll = !showAll"
            >
                {{ showAll ? $t('directory_collapse_all') : $t('directory_expand_all') }}
            </button>
        </div>
        <DirectoryTreeNode
            v-for="node in tree"
            :key="node.hashid"
            :node="node"
            :selected="selected"
            :default-expanded="showAll || hasMine(node)"
            :force-expand="showAll"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { UserCircle } from 'lucide-vue-next'
import DirectoryTreeNode from './DirectoryTreeNode.vue'

defineProps({
    tree: Array,
    selected: String,
    myGroupCount: { type: Number, default: 0 },
})

const showAll = ref(false)

function hasMine(node) {
    if (node.is_mine) return true
    return (node.children || []).some(c => hasMine(c))
}
</script>
