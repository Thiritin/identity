<template>
    <div class="altcha-container">
        <altcha-widget
            challengeurl="/altcha-challenge"
            hidefooter
            @statechange="onStateChange"
        />
    </div>
</template>
<script setup>
import { onMounted } from 'vue'

onMounted(() => {
    import('altcha')
})

const model = defineModel()

function onStateChange(ev) {
    model.value = ev.detail.state === 'verified' ? ev.detail.payload : null
}
</script>
<style scoped>
.altcha-container {
    --altcha-max-width: 100%;
}
</style>
