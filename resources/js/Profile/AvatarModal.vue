<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ $t('crop_avatar') }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="mt-2">
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
                    v-if="avatarform.progress" :value="avatarform.progress.percentage" class="w-full"
                    max="100">
                    {{ avatarform.progress.percentage }}%
                </progress>

                <div class="mt-5 sm:mt-6 flex flex-row justify-end">
                    <button
                        autofocus
                        class="bg-primary-600 mr-2 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-100 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        type="submit">
                        {{ $t('submit') }}
                    </button>
                    <button
                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        type="button" @click="open = false">
                        {{ $t('cancel') }}
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script>
import {ref} from 'vue'
import {Dialog, DialogContent, DialogHeader, DialogTitle} from '@/Components/ui/dialog'
import VueCropper from 'vue-cropperjs';
import 'cropperjs/dist/cropper.css';
import {useForm} from "@inertiajs/vue3";

export default {
    components: {
        Dialog,
        DialogContent,
        DialogHeader,
        DialogTitle,
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
