<!-- Segmented control with a sliding active indicator -->
<template>
  <div ref="root" class="segmented" role="tablist">
    <span class="indicator" :style="indicatorStyle" />
    <button
      v-for="opt in options"
      :key="opt.value"
      :ref="el => setItem(opt.value, el as HTMLElement)"
      type="button"
      role="tab"
      :aria-selected="modelValue === opt.value"
      :class="['segment', { active: modelValue === opt.value }]"
      @click="emit('update:modelValue', opt.value)"
    >
      <span v-if="opt.dot" class="dot" :style="{ background: opt.dot }" />
      {{ opt.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string
  options: { label: string; value: string; dot?: string }[]
}>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const root = ref<HTMLElement>()
const items = new Map<string, HTMLElement>()
const setItem = (key: string, el: HTMLElement | null) => {
  if (el) items.set(key, el)
}

const indicatorStyle = ref<Record<string, string>>({ opacity: '0' })
const measure = () => {
  const el = items.get(props.modelValue)
  if (!el) return
  indicatorStyle.value = {
    opacity: '1',
    width: `${el.offsetWidth}px`,
    transform: `translateX(${el.offsetLeft}px)`
  }
}

watch(() => props.modelValue, () => nextTick(measure))
onMounted(() => {
  // Fonts shift widths after load; re-measure once they're ready
  measure()
  document.fonts?.ready.then(measure)
})
</script>

<style scoped>
.segmented {
  position: relative;
  display: inline-flex;
  padding: 2px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}
.indicator {
  position: absolute;
  top: 2px;
  bottom: 2px;
  left: 0;
  border-radius: 6px;
  background: var(--surface-3);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08), 0 1px 2px rgba(0, 0, 0, 0.6);
  transition:
    transform 380ms var(--ease-out),
    width 380ms var(--ease-out),
    opacity var(--dur) var(--ease-std);
}
.segment {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 26px;
  padding: 0 10px;
  border: 0;
  background: none;
  color: var(--text-muted);
  font-size: 12.5px;
  font-weight: 500;
  border-radius: 6px;
  transition: color var(--dur) var(--ease-std);
}
.segment:hover,
.segment.active {
  color: var(--text);
}
.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}
</style>
