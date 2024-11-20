<script setup>
  import { ref, onMounted } from 'vue'
  const emit = defineEmits(['click']);

  onMounted(() => {

  });

</script>

<template>
  <button class="helpOnboarding" @click="emit('click')">
    <img src="/src/assets/img/icon_help.svg" alt="link icon" >
  </button>
</template>

<style scoped>

</style>
