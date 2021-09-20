<template>
    <boxed-layout>
        <template #header>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-white">{{ $trans('edit-your-profile') }}</h1>
            </div>
            <avatar-modal ref="avatarmodal" :file="file" :url="previewUrl"></avatar-modal>
        </template>

        <form class="space-y-8 divide-y divide-gray-200">
            <div class="space-y-8 divide-y divide-gray-200 sm:space-y-5">
                <div>
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Public Profile </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            This information will be used across services. </p>
                    </div>

                    <div class="mt-6 sm:mt-5 space-y-6 sm:space-y-5">
                        <div
                            class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-gray-200 sm:pt-5">
                            <label class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2" for="username">
                                Username </label>
                            <div class="mt-1 sm:mt-0 sm:col-span-2">
                                <div class="max-w-lg flex rounded-md shadow-sm">
                <span
                    class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                  @
                </span> <input id="username" v-model="form.name" autocomplete="username"
                               class="flex-1 block w-full focus:ring-indigo-500 focus:border-indigo-500 min-w-0 rounded-none rounded-r-md sm:text-sm border-gray-300"
                               name="username" type="text"/>
                                </div>
                            </div>
                        </div>

                        <div
                            class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-center sm:border-t sm:border-gray-200 sm:pt-5">
                            <label class="block text-sm font-medium text-gray-700" for="avatar"> Avatar </label>
                            <div class="mt-1 sm:mt-0 sm:col-span-2">
                                <div class="flex items-center">
                                    <span class="h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                                      <AvatarImage/>
                                        </span>
                                    <div
                                        class="relative  ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <label class="relative" for="avatar" type="button"> <span>Change</span> <span
                                            class="sr-only"> user photo</span> </label> <input id="avatar"
                                                                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer border-gray-300 rounded-md"
                                                                                               name="avatar" type="file"
                                                                                               accept="image/png,image/jpeg,image/jpg"
                                                                                               @change="onFileChange($event)"/>
                                    </div>
                                </div>
                                <span v-show="errors.image" class="text-xs text-red-600">{{ errors.image }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-8 space-y-6 sm:pt-10 sm:space-y-5">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Contact Information </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            How can we reach you? </p>
                    </div>
                    <div class="space-y-6 sm:space-y-5">

                        <div
                            class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-gray-200 sm:pt-5">
                            <label class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2" for="email"> Email
                                address </label>
                            <div class="mt-1 sm:mt-0 sm:col-span-2">
                                <input id="email" v-model="form.email" autocomplete="email"
                                       class="block max-w-lg w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md"
                                       name="email" type="email"/>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <InertiaLink :href="route('profile')"
                                 class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                 type="button">
                        Cancel
                    </InertiaLink>
                    <button
                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        type="submit">
                        Save
                    </button>
                </div>
            </div>
        </form>
    </boxed-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout'
import BoxedLayout from "@/Layouts/BoxedLayout";
import AvatarModal from "@/Profile/AvatarModal";
import {useForm} from '@inertiajs/inertia-vue3'
import {HomeIcon} from '@heroicons/vue/outline'
import AvatarImage from "@/Pages/Profile/AvatarImage";

export default {
    props: {
        errors: Object
    },

    components: {
        AvatarImage,
        AvatarModal,
        BoxedLayout
    },

    data() {
        return {
            previewUrl: null,
            file: null,
            form: useForm({
                name: this.$page.props.user.name,
                email: this.$page.props.user.email
            })
        }
    },

    methods: {
        onFileChange(e) {
            const file = e.target.files[0];
            this.file = file;
            this.previewUrl = URL.createObjectURL(file);
            this.$refs.avatarmodal.open = true;
            URL.revokeObjectURL(file);
        }
    },
}
</script>
