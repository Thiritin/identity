<template>
  <div class="mt-12">
    <h1 class="text-primary-500 font-semibold text-5xl">{{ title }}</h1>
    <h2 class="text-gray-600 text-3xl">{{ subTitle }}</h2>
  </div>
</template>
<script>
export default {
  name: 'LoginScreenWelcome',
  props: {
    title: {
      required: false
    },
    subTitle: {
      required: false
    },
  }
}
</script>
