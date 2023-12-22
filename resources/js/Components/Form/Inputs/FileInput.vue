<template>
    <div class="mt-2 flex justify-center rounded-md max-w-lg border-2 border-dashed border-gray-300 px-6 pt-5 pb-6">
        <div class="space-y-1 text-center">
            <svg aria-hidden="true" class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                 viewBox="0 0 48 48">
                <path
                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
            <div class="flex text-sm text-gray-600">
                <label :for="id"
                       class="relative cursor-pointer rounded-md bg-white font-medium text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:text-indigo-500">
                    <span v-if="!fileUploaded">Upload a file</span>
                    <span v-else>Select a different file</span>
                    <input :id="id" :name="id" class="sr-only" type="file"
                           @change="updateImage($event)"/>
                </label>
            </div>
            <p class="text-xs text-gray-500">{{ hint }}</p>
        </div>
    </div>
</template>

<script>
export default {
    name: "FileInput",
    props: {
        hint: String,
        logo: String,
        id: String
    },
    data() {
        return {
            value: null,
            fileUploaded: false
        }
    },
    methods: {
        updateImage(event) {
            this.fileUploaded = true;
            this.$emit('update:modelValue', event.target.files[0])
        }
    },
}
</script>

<style scoped>

</style>
