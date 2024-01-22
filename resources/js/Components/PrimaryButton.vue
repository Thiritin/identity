<template>
    <BaseButton primary>
        <slot></slot>
    </BaseButton>
</template>
<script>
import BaseButton from "@/Components/BaseButton.vue";

export default {
    name: 'PrimaryButton',
    components: {BaseButton},
}
</script>
