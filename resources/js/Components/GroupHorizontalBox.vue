<template>
    <Card class="p-4 mb-6">
        <div class="flex gap-6">
            <div>
                <img
                    v-if="group.logo_url"
                    :src="group.logo_url"
                    alt=""
                    class="rounded max-h-[300px] max-w-[600px] shadow"
                />
                <img
                    v-else
                    alt=""
                    class="rounded max-h-[300px] max-w-[600px] shadow"
                    src="../../assets/fallback-group-image.png"
                />
            </div>
            <div>
                <h2 class="font-semibold text-3xl mb-2 dark:text-primary-100">
                    {{ transProp(group.name) }}
                    <span
                        class="rounded px-2 py-1 bg-gray-200 text-sm text-black"
                        >{{ group.type }}</span
                    >
                </h2>
                <p class="mb-3 dark:text-primary-300">
                    {{ transProp(group.description) }}
                </p>
                <Link :href="route('groups.edit', group.hashid)">
                    <PrimaryButton v-if="canSeeSettings"
                        >Settings</PrimaryButton
                    >
                </Link>
            </div>
        </div>
    </Card>
</template>
<script>
    import Card from '@/Components/Card.vue'
    import PrimaryButton from '@/Components/PrimaryButton.vue'
    import { Link } from '@inertiajs/vue3'

    export default {
        name: 'GroupHorizontalBox',
        components: { Card, PrimaryButton, Link },
        props: {
            canSeeSettings: {},
            group: {},
        },
    }
</script>
