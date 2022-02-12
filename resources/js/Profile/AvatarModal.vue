<!-- This example requires Tailwind CSS v2.0+ -->
<template>
    <TransitionRoot :show="open" as="template">
        <Dialog as="div" auto-reopen="true" class="fixed z-10 inset-0 overflow-y-auto" @close="open = false">
            <div
                class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0 bg-gray-200 bg-opacity-75">
                <!-- This element is to trick the browser into centering the modal contents. -->
                <span aria-hidden="true" class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <TransitionChild as="template" enter="ease-out duration-300"
                                 enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                 enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                 leave-from="opacity-100 translate-y-0 sm:scale-100"
                                 leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div
                        class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
                                        Crop Avatar
                                    </DialogTitle>
                                    <div class="mt-2">
                                        <vue-cropper v-if="url != null" ref="cropper" :aspectRatio="1" :guides="false"
                                                     :rotatable="false" :scalable="false" :src="url" :zoomable="false"
                                                     alt="Source Image"
                                                     @cropend="avatarform.crop = $event.target.cropper.getData()"
                                                     @ready="avatarform.crop = $event.target.cropper.getData()"/>
                                    </div>
                                </div>
                            </div>

                            <progress v-if="avatarform.progress" :value="avatarform.progress.percentage" class="w-full"
                                      max="100">
                                {{ avatarform.progress.percentage }}%
                            </progress>

                            <div class="mt-5 sm:mt-6 flex flex-row justify-end">
                                <button autofocus
                                        class="bg-primary-600 mr-2 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-100 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        type="submit">
                                    Submit
                                </button>
                                <button
                                    class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    type="button" @click="open = false">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {ref} from 'vue'
import {Dialog, DialogOverlay, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import {CheckIcon} from '@heroicons/vue/outline'
import VueCropper from 'vue-cropperjs';
import 'cropperjs/dist/cropper.css';
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    components: {
        Dialog,
        DialogOverlay,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        CheckIcon,
        VueCropper
    },
    props: {
        url: String,
        file: File
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
    setup() {
        const open = ref(false)


        return {
            open
        }
    },
}
</script>
