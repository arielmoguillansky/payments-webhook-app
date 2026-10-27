<!-- Word-by-word blur-to-sharp reveal -->
<template>
  <component :is="tag" class="blur-text" :aria-label="text">
    <span
      v-for="(word, i) in words"
      :key="`${word}-${i}`"
      class="word"
      aria-hidden="true"
      :style="{ animationDelay: `${delay + i * stagger}ms` }"
    >{{ word }}</span>
  </component>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  text: string
  tag?: string
  delay?: number
  stagger?: number
}>(), { tag: 'span', delay: 0, stagger: 60 })

const words = computed(() => props.text.split(' '))
</script>

<style scoped>
.word {
  display: inline-block;
  opacity: 0;
  margin-right: 0.25em;
  animation: blur-in 800ms var(--ease-out) forwards;
}
.word:last-child {
  margin-right: 0;
}
</style>
