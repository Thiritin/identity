<script setup>

import AppLayout from "../../../../../Shared/js/Layouts/AppLayout.vue";
import TabHeader from "./TabHeader.vue";
import TabComponent from "./TabComponent.vue";
import Button from 'primevue/button';

import {computed, ref} from "vue";
import {useForm} from "laravel-precognition-vue-inertia";
import {MdEditor} from "md-editor-v3";
import "md-editor-v3/lib/style.css";

defineOptions({layout: AppLayout})
const props = defineProps({
    group: Object,
    parent: {
        type: Object,
        required: false
    },
    users: Array,
    canEdit: Boolean,
    descriptionHtml: String,
})

const form = useForm('patch', route('staff.groups.update', {group: props.group.hashid}), {
    description: props.group.description ?? '',
})

const submit = () => form.submit({
    preserveScroll: true,
    onSuccess: () => {
        editModeEnabled.value = false
    }
});

const editModeEnabled = ref(false)

// If descriptionHtml contains a h1, h2, or h3 tag, omit the label
const showDescriptionLabel = computed(() => {
    return !props.descriptionHtml.includes('<h1') && !props.descriptionHtml.includes('<h2') && !props.descriptionHtml.includes('<h3')
});

</script>

<template>
    <TabHeader
        :parent="parent"
        :group="group"
        subtitle="Info"
        :can-edit="canEdit"
    ></TabHeader>
    <TabComponent :active-index="0" :group="group"></TabComponent>
    <!-- Body -->
    <div class="p-4 max-w-screen-md mx-auto pt-8">
        <!-- Description Field -->
        <div>
            <!-- Title -->
            <h2 v-if="showDescriptionLabel" class="text-lg font-semibold text-gray-800">Description</h2>
            <!-- Description -->
            <div v-if="!editModeEnabled">
                <div v-if="group.description" class="mt-2 text-gray-600 markdown-body break-words" v-html="descriptionHtml"/>
                <p v-else class="mt-2 text-gray-600">No description available</p>
                <!-- Edit Link -->
                <a v-if="canEdit" @click="editModeEnabled = true" href="#"
                   class="text-primary font-bold text-xs mt-2">Edit</a>
            </div>
            <!-- Edit Mode use Primevue -->
            <div v-else>
                <MdEditor
                    v-model="form.description"
                    :invalid="form.errors.description"
                    class="w-full"
                    language="en-US"
                    no-mermaid
                    no-katex
                    :preview="false"
                    no-upload-img
                    :toolbars="[
  'bold',
  'underline',
  'italic',
  '-',
  'title',
  'strikeThrough',
  'sub',
  'sup',
  'quote',
  'unorderedList',
  'orderedList',
  '-',
  'link',
  'table',
  '-',
  'revoke',
  'next',
]"
                    editorStyle="height: 320px"
                >
                </MdEditor>
                <small v-if="form.errors.description"
                       class="text-red-500 text-xs font-semibold">{{ form.errors.description }}</small>
                <div class="mt-2 flex gap-3 justify-end">
                    <Button
                        size="small"
                        severity="danger"
                        @click="editModeEnabled = false;form.reset()">Cancel
                    </Button>
                    <Button
                        size="small"
                        :loading="form.processing" @click="submit()">Save
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.markdown-body table {
    width: 100%;
}
</style>
