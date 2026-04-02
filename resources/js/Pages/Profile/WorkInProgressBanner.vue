<template>
    <div class="w-100 p-4 rounded-md shadow bg-white text-center">
        <div class="border-dashed rounded-md border-4 border-gray-400 py-10">
            <h2 class="text-gray-600 text-3xl mb-2">Oh <s>Snep!</s> {{ $t('wip_title') }}</h2>
            <p class="italic text-xl">{{ $t('wip_subtitle') }}</p>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'WorkInProgressBanner',
    }
</script>
