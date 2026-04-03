<template>
    <div class="space-y-0.5" :class="$attrs.class">
        <div class="flex items-center justify-between px-3 mb-2">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $t('tab_directory') }}
            </h3>
        </div>
        <DirectoryTreeNode
            v-for="node in tree"
            :key="node.hashid"
            :node="node"
            :selected="selected"
            :default-expanded="true"
        />
    </div>
</template>

<script setup>
import DirectoryTreeNode from './DirectoryTreeNode.vue'

defineProps({
    tree: Array,
    selected: String,
})
</script>
