<template>
    <div class="hidden lg:block relative w-0 flex-auto">
        <div
            class="absolute inset-0 object-right h-full w-full object-cover bg-primary-600 auth-background"
        >
            <div
                class="absolute bottom-2 left-2 text-sm text-primary-200 bg-black px-2 py-1 rounded shadow"
            >
                Artwork by
                <a class="hover:underline" :href="url">{{ name }}</a>
            </div>
        </div>
    </div>
</template>
<script setup>
    defineProps({
        url: String,
        name: String,
    })
</script>
<script>
    export default {
        name: 'ArtistNotice',
    }
</script>
<style>
    .auth-background {
        background-position: right;
        background-repeat: no-repeat;
        background-size: cover;
        background-image: url('../../../assets/space_chromamancer.png');
    }

    .page * {
        @apply transition-colors;
    }
</style>
