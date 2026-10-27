<template>
  <span :class="['status', tone]">
    <span class="dot" />
    {{ label }}
  </span>
</template>

<script setup lang="ts">
const props = defineProps<{ event: string }>()

const tone = computed(() => {
  if (props.event === 'payment.success') return 'success'
  if (props.event === 'payment.refunded') return 'danger'
  return 'warning'
})
const label = computed(() => props.event.replace('payment.', ''))
</script>

<style scoped>
.status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
  border: 1px solid;
  transition: background-color var(--dur) var(--ease-std), color var(--dur) var(--ease-std);
}
.dot {
  position: relative;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}
.success { color: var(--success); background: var(--success-soft); border-color: rgba(74, 222, 128, 0.18); }
.warning { color: var(--warning); background: var(--warning-soft); border-color: rgba(251, 191, 36, 0.18); }
.danger  { color: var(--danger);  background: var(--danger-soft);  border-color: rgba(248, 113, 113, 0.18); }

/* Pending payments are still waiting on a webhook — give them a heartbeat */
.warning .dot::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: currentColor;
  animation: pulse-ring 1.6s var(--ease-out) infinite;
}
</style>
