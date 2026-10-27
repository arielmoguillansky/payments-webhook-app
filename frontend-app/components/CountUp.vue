<!-- Eased numeric tween; re-animates from the previous value when `value` changes -->
<template>
  <span class="count-up">{{ formatted }}</span>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  value: number
  decimals?: number
  prefix?: string
  duration?: number
}>(), { decimals: 0, prefix: '', duration: 900 })

const display = ref(0)
let frame = 0

const animate = (from: number, to: number) => {
  cancelAnimationFrame(frame)
  const start = performance.now()
  const step = (t: number) => {
    const p = Math.min(1, (t - start) / props.duration)
    const eased = 1 - Math.pow(1 - p, 4)
    display.value = from + (to - from) * eased
    if (p < 1) frame = requestAnimationFrame(step)
  }
  frame = requestAnimationFrame(step)
}

watch(() => props.value, (to, from) => animate(from ?? 0, to), { immediate: true })
onUnmounted(() => cancelAnimationFrame(frame))

const formatted = computed(() =>
  props.prefix + display.value.toLocaleString('en-US', {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals
  })
)
</script>

<style scoped>
.count-up {
  font-variant-numeric: tabular-nums;
}
</style>
