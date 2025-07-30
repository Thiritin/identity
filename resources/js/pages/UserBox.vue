<template>
    <div
        class="rounded border-primary-200 border px-6 py-6 flex justify-left items-center gap-4"
    >
        <CircleUser
            class="fill-current text-primary-500 dark:text-primary-300 w-14"
        ></CircleUser>
        <div>
            <div class="font-bold dark:text-primary-300">{{ user.name }}</div>
            <div class="text-primary-800 dark:text-primary-300 font-light">
                {{ user.email }}
            </div>
        </div>
    </div>
</template>
<script>
    import CircleUser from '@Shared/components/Icons/CircleUser.vue'

    export default {
        name: 'UserBox',
        components: { CircleUser },
        props: {
            user: {},
        },
    }
</script>
