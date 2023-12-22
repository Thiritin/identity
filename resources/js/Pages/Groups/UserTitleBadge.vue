<template>
    <div :class="levelObject" class="rounded px-2 py-1 text-sm font-semibold">{{ level }}</div>
</template>
<script>
export default {
    name: 'UserLevelBadge',
    props: {
        level: String
    },
    computed: {
        levelObject() {
            return {
                'bg-yellow-600 text-white': this.level === 'moderator',
                'bg-red-600 text-white': this.level === 'admin',
                'bg-red-700 text-white': this.level === 'owner',
                'bg-blue-200 text-black': this.level === 'invited',
            }
        }
    }
}
</script>
