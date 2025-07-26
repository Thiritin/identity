<template>
    <app-layout>
        <template #header>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-white">
                    {{ $trans('your_profile') }}
                </h1>
            </div>
        </template>
        <div class="bg-white rounded-lg shadow">
            <div>
                <img
                    :src="profile.backgroundImage"
                    alt=""
                    class="h-32 rounded-tl-lg rounded-tr-lg w-full object-cover lg:h-48"
                />
            </div>
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="-mt-12 sm:-mt-16 sm:flex sm:items-end sm:space-x-5">
                    <div class="flex">
                        <AvatarImage
                            :src="$page.props.user.avatar"
                            alt=""
                            class="h-24 w-24 rounded-full ring-4 ring-white sm:h-32 sm:w-32"
                        />
                    </div>
                    <div
                        class="mt-6 sm:flex-1 sm:min-w-0 sm:flex sm:items-center sm:justify-end sm:space-x-6 sm:pb-1"
                    >
                        <div class="sm:hidden md:block mt-6 min-w-0 flex-1">
                            <h1
                                class="text-2xl font-bold text-gray-900 truncate"
                            >
                                {{ $page.props.user.name }}
                            </h1>
                        </div>
                        <div
                            class="mt-6 flex flex-col justify-stretch space-y-3 sm:flex-row sm:space-y-0 sm:space-x-4"
                        >
                            <InertiaLink
                                :href="route('profile.edit')"
                                class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none"
                            >
                                <PencilIcon
                                    aria-hidden="true"
                                    class="-ml-1 mr-2 h-5 w-5 text-gray-400"
                                />
                                <span>Edit</span>
                            </InertiaLink>
                        </div>
                    </div>
                </div>
                <div class="hidden sm:block md:hidden mt-6 min-w-0 flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 truncate">
                        {{ profile.name }}
                    </h1>
                </div>
                <div class="py-12">
                    <div class="px-4 py-5 sm:p-0">
                        <dl class="sm:divide-y sm:divide-gray-200">
                            <div
                                class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                            >
                                <dt class="text-sm font-medium text-gray-500">
                                    {{ $trans('username') }}
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"
                                >
                                    @{{ $page.props.user.name }}
                                </dd>
                            </div>
                            <div
                                class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                            >
                                <dt class="text-sm font-medium text-gray-500">
                                    {{ $trans('email') }}
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"
                                >
                                    {{ $page.props.user.email }}
                                </dd>
                            </div>
                            <div
                                class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                            >
                                <dt class="text-sm font-medium text-gray-500">
                                    {{ $trans('account_id') }}
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"
                                >
                                    {{ $page.props.user.id }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>
<script>
    import AppLayout from '@Shared/Layouts/AppLayout.vue'
    import { PencilIcon, PhoneIcon } from '@heroicons/vue/solid'
    import AvatarModal from '../Components/AvatarModal.vue'
    import AvatarImage from './AvatarImage.vue'

    const profile = {
        backgroundImage:
            'https://images.unsplash.com/photo-1444628838545-ac4016a5418a?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
    }

    export default {
        name: 'Show',
        components: {
            AvatarImage,
            AvatarModal,
            PencilIcon,
            PhoneIcon,
            AppLayout,
        },
        setup() {
            return {
                profile,
            }
        },
    }
</script>
