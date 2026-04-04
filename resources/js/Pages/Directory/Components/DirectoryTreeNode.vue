<template>
    <div>
        <Link
            :href="route('directory.show', node.slug)"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors hover:bg-gray-100 dark:hover:bg-white/10"
            :class="selected === node.slug ? 'bg-primary/10 text-primary font-medium dark:bg-primary/20' : 'text-gray-700 dark:text-gray-300'"
            preserve-state
        >
            <button
                v-if="node.children && node.children.length > 0"
                type="button"
                class="p-0.5 -ml-1 rounded hover:bg-gray-200 dark:hover:bg-white/10"
                @click.prevent.stop="expanded = !expanded"
            >
                <ChevronRight class="h-3.5 w-3.5 transition-transform" :class="expanded ? 'rotate-90' : ''" />
            </button>
            <span v-else class="w-4.5" />
            <component :is="iconMap[node.icon]" v-if="node.icon && iconMap[node.icon]" class="h-3.5 w-3.5 shrink-0 text-gray-400" />
            <span class="truncate flex-1" :class="node.is_mine ? 'font-semibold' : ''">{{ node.name }}</span>
            <span v-if="node.is_mine" class="h-1.5 w-1.5 rounded-full bg-primary shrink-0" />
            <span class="text-xs text-gray-400 dark:text-gray-500 tabular-nums">{{ node.member_count }}</span>
        </Link>
        <div v-if="expanded && node.children?.length" class="ml-4">
            <DirectoryTreeNode
                v-for="child in node.children"
                :key="child.hashid"
                :node="child"
                :selected="selected"
                :default-expanded="isAncestor(child)"
                :force-expand="forceExpand"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import { iconMap } from './iconMap'

const props = defineProps({
    node: Object,
    selected: String,
    defaultExpanded: { type: Boolean, default: false },
    forceExpand: { type: Boolean, default: false },
})

const expanded = ref(props.defaultExpanded || props.forceExpand)

watch(() => props.forceExpand, (val) => {
    if (val) {
        expanded.value = true
    } else if (!props.defaultExpanded && !isAncestorOfSelected()) {
        expanded.value = false
    }
})

function isAncestor(child) {
    if (child.slug === props.selected) return true
    return (child.children || []).some(c => isAncestor(c))
}

function isAncestorOfSelected() {
    return isAncestor(props.node)
}
</script>

<script>
export default { name: 'DirectoryTreeNode' }
</script>
