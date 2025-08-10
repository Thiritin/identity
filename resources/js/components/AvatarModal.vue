<!-- This example requires Tailwind CSS v2.0+ -->
<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="sm:max-w-lg">
            <form @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Crop Avatar</DialogTitle>
                    <DialogDescription>
                        Adjust your avatar image below.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="mt-4">
                    <vue-cropper
                        v-if="url != null" ref="cropper"
                        :aspect-ratio="1" :guides="false"
                        :toggle-drag-mode-on-dblclick="false"
                        :rotatable="false" :scalable="false" :src="url" :zoomable="false"
                        alt="Source Image"
                        @cropend="avatarform.crop = $event.target.cropper.getData()"
                        @ready="avatarform.crop = $event.target.cropper.getData()"/>
                </div>

                <progress
                    v-if="avatarform.progress" :value="avatarform.progress.percentage" class="w-full mt-4"
                    max="100">
                    {{ avatarform.progress.percentage }}%
                </progress>

                <DialogFooter class="mt-6">
                    <button
                        type="button" 
                        @click="open = false"
                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        autofocus
                        type="submit"
                        class="bg-primary-600 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-100 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Submit
                    </button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script>
import {ref} from 'vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {Check as CheckIcon} from 'lucide-vue-next'
import VueCropper from 'vue-cropperjs';
import 'cropperjs/dist/cropper.css';
import {useForm} from "@inertiajs/vue3";

export default {
    components: {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
        CheckIcon,
        VueCropper
    },
    props: {
        url: String,
        file: File
    },
    setup() {
        const open = ref(false)


        return {
            open
        }
    },
    data() {
        return {
            avatarform: useForm({
                image: null,
                crop: null,
            })
        }
    },
    methods: {
        submit() {
            this.avatarform.image = this.file
            this.avatarform.transform((data) => ({
                ...data,
                crop: {
                    x: Math.round(data.crop.x),
                    y: Math.round(data.crop.y),
                    width: Math.round(data.crop.width),
                    height: Math.round(data.crop.height)
                }
            })).post(route('profile.avatar.store'), {
                onSuccess: () => (this.open = false),
                onError: () => (this.open = false),
            })
        }
    },
}
</script>
