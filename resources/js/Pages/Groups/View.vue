<template>
    <AppLayout :title="transProp(group.name)" :description='transProp(group.description)'>

    </AppLayout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import GroupBox from '@/Pages/Groups/GroupBox.vue'
    import Card from '@/Components/Card.vue'

    export default {
        name: 'View',
        props: {
            group: Object,
        },
        components: { Card, GroupBox, AppLayout },
    }
</script>

