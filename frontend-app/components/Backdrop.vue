<!-- Ambient page background: top-left spotlight, dashed guide rails, twinkling stars -->
<template>
  <div class="backdrop" aria-hidden="true">
    <div class="spotlight" />
    <div v-if="rails" class="rails" />
    <span
      v-for="(s, i) in stars"
      :key="i"
      class="star"
      :style="{ left: `${s.x}%`, top: `${s.y}%`, animationDelay: `${s.d}s`, animationDuration: `${s.t}s` }"
    />
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{ starCount?: number; rails?: boolean }>(), { starCount: 40, rails: true })

const stars = Array.from({ length: props.starCount }, () => ({
  x: Math.random() * 100,
  y: Math.random() * 100,
  d: Math.random() * 6,
  t: 3 + Math.random() * 5
}))
</script>

<style scoped>
.backdrop {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}
.spotlight {
  position: absolute;
  top: -30%;
  left: -15%;
  width: 90vw;
  height: 90vh;
  background: radial-gradient(ellipse at 30% 30%, rgba(255, 255, 255, 0.09), transparent 60%);
  transform: rotate(-12deg);
  filter: blur(20px);
  animation: drift 18s var(--ease-in-out) infinite alternate;
}
.rails {
  position: absolute;
  inset: 0 max(12px, calc((100vw - 1320px) / 2));
  border-left: 1px dashed var(--border-dashed);
  border-right: 1px dashed var(--border-dashed);
}
.star {
  position: absolute;
  width: 2px;
  height: 2px;
  border-radius: 50%;
  background: #fff;
  opacity: 0;
  animation: twinkle ease-in-out infinite;
}
@keyframes twinkle {
  0%, 100% { opacity: 0; }
  50% { opacity: 0.45; }
}
@keyframes drift {
  to { transform: rotate(-6deg) translate(4vw, 2vh); }
}
</style>
