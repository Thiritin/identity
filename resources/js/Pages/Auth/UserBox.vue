<template>
    <div class="rounded border-primary-200 border px-6 py-6 flex justify-left items-center gap-4">
        <CircleUser class="fill-current text-primary-500 w-14"></CircleUser>
        <div>
            <div class="font-bold">{{ user.name }}</div>
            <div class="text-primary-800 font-light">{{ user.email }}</div>
        </div>
    </div>
</template>
<script>
import CircleUser from "@/Components/Icons/CircleUser.vue"

export default {
    name: 'UserBox',
    components: {CircleUser},
    props: {
        user: {}
    }
}
</script>
