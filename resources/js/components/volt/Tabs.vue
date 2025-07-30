<template>
    <TabView
        unstyled
        :pt="theme"
        :ptOptions="{
            mergeProps: ptViewMerge
        }"
    >
        <slot></slot>
    </TabView>
</template>

<script setup lang="ts">
import TabView, { type TabViewPassThroughOptions, type TabViewProps } from 'primevue/tabview';
import { ref } from 'vue';
import { ptViewMerge } from './utils';

interface Props extends /* @vue-ignore */ TabViewProps {}
const props = defineProps<Props>();

const theme = ref<TabViewPassThroughOptions>({
    root: `flex flex-col`
});
</script>
