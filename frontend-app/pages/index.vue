<template>
  <div class="shell">
    <Backdrop :star-count="24" :rails="false" />

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="brand">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 12a8 8 0 0 1 14.9-4M20 12a8 8 0 0 1-14.9 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
          <circle cx="12" cy="12" r="2.6" fill="currentColor" />
        </svg>
        Relay
      </div>

      <nav class="nav">
        <a class="nav-item active" href="#">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <rect x="1.5" y="3.5" width="13" height="9" rx="2" stroke="currentColor" stroke-width="1.4" />
            <path d="M1.5 6.5h13" stroke="currentColor" stroke-width="1.4" />
          </svg>
          Payments
          <span class="nav-count">{{ meta?.total ?? '—' }}</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="live-card">
          <span class="live-dot" />
          <div>
            <strong>Listening for webhooks</strong>
            <span>Auto-refresh every 5s</span>
          </div>
        </div>
        <button class="btn btn-ghost logout" @click="logout">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M6 14H3.5A1.5 1.5 0 0 1 2 12.5v-9A1.5 1.5 0 0 1 3.5 2H6M10.5 11l3-3-3-3M13.5 8H6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          Log out
        </button>
      </div>
    </aside>

    <!-- Main -->
    <main class="main">
      <header class="page-header">
        <div>
          <div class="crumbs reveal">Console <span>/</span> Payments</div>
          <BlurText tag="h1" text="Payment webhooks" class="title" :delay="80" />
        </div>
        <button class="btn btn-secondary reveal" style="--i: 3" :disabled="refreshing" @click="refreshData">
          <svg :class="['refresh-icon', { spinning: refreshing }]" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M13.5 8a5.5 5.5 0 1 1-1.6-3.9M13.5 2.5v3h-3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          Refresh
        </button>
      </header>

      <!-- KPIs -->
      <section class="stats">
        <div
          v-for="(stat, i) in stats"
          :key="stat.label"
          class="stat reveal"
          :style="{ '--i': i + 2 }"
          @mousemove="trackPointer"
        >
          <span class="stat-label">{{ stat.label }}</span>
          <span class="stat-value">
            <CountUp :value="stat.value" :decimals="stat.decimals || 0" :prefix="stat.prefix || ''" />
          </span>
          <span class="stat-hint">{{ stat.hint }}</span>
        </div>
      </section>

      <!-- Toolbar -->
      <section class="toolbar reveal" style="--i: 6">
        <SegmentedControl v-model="filters.event" :options="statusOptions" />
        <SegmentedControl v-model="filters.currency" :options="currencyOptions" />
        <Transition name="fade">
          <button v-if="hasFilters" class="btn btn-ghost" @click="clearFilters">Clear</button>
        </Transition>
        <label class="search">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.4" />
            <path d="m10.5 10.5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
          </svg>
          <input ref="searchInput" v-model="userQuery" class="field" type="text" placeholder="Filter by user ID" />
          <span class="kbd">/</span>
        </label>
      </section>

      <!-- Table -->
      <section class="table-card reveal" style="--i: 7">
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Payment</th>
                <th>User</th>
                <th class="num">Amount</th>
                <th>Status</th>
                <th>Last event</th>
                <th>Updated</th>
                <th aria-label="Open" />
              </tr>
            </thead>

            <tbody v-if="loading && payments.length === 0">
              <tr v-for="n in 8" :key="n" class="skeleton-row">
                <td v-for="w in [120, 60, 70, 80, 140, 90, 0]" :key="w"><div v-if="w" class="skeleton" :style="{ width: `${w}px`, height: '12px' }" /></td>
              </tr>
            </tbody>

            <tbody v-else-if="payments.length" :key="listKey" :class="{ dimmed: loading }">
              <tr
                v-for="(payment, i) in payments"
                :key="payment.payment_id"
                :class="['row', { flash: flashed.has(payment.payment_id), selected: selectedPayment === payment.payment_id }]"
                :style="{ '--i': i }"
                tabindex="0"
                @click="viewDetails(payment.payment_id)"
                @keydown.enter="viewDetails(payment.payment_id)"
              >
                <td>
                  <span class="id-cell">
                    <span class="mono">{{ payment.payment_id }}</span>
                    <button class="copy" :aria-label="`Copy ${payment.payment_id}`" @click.stop="copy(payment.payment_id)">
                      <svg v-if="copied !== payment.payment_id" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <rect x="5" y="5" width="9" height="9" rx="2" stroke="currentColor" stroke-width="1.4" />
                        <path d="M11 5V3.5A1.5 1.5 0 0 0 9.5 2h-6A1.5 1.5 0 0 0 2 3.5v6A1.5 1.5 0 0 0 3.5 11H5" stroke="currentColor" stroke-width="1.4" />
                      </svg>
                      <svg v-else width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="m3 8.5 3 3 7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </button>
                  </span>
                </td>
                <td class="muted">{{ payment.user_id }}</td>
                <td class="num amount">
                  {{ formatMoney(payment.amount, payment.currency) }}
                  <span class="currency">{{ payment.currency }}</span>
                </td>
                <td><StatusBadge :event="payment.event" /></td>
                <td class="mono subtle">{{ payment.last_event_id }}</td>
                <td class="muted" :title="new Date(payment.updated_at).toLocaleString()">{{ timeAgo(payment.updated_at) }}</td>
                <td class="chevron">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="m6 4 4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!loading && payments.length === 0" class="empty">
          <div class="empty-icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.4" />
              <path d="m10.5 10.5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
            </svg>
          </div>
          <strong>No payments match these filters</strong>
          <span>Try a different status, currency or user.</span>
          <button v-if="hasFilters" class="btn btn-secondary" @click="clearFilters">Clear filters</button>
        </div>

        <footer v-if="meta && payments.length" class="pagination">
          <span class="muted">
            Showing <strong>{{ rangeStart }}–{{ rangeEnd }}</strong> of <strong>{{ meta.total }}</strong>
          </span>
          <div class="pager">
            <button class="btn btn-ghost icon-btn" aria-label="Previous page" :disabled="meta.current_page === 1" @click="fetchPayments(meta.current_page - 1)">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m10 4-4 4 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
            <span class="page-info">{{ meta.current_page }} / {{ meta.last_page }}</span>
            <button class="btn btn-ghost icon-btn" aria-label="Next page" :disabled="meta.current_page === meta.last_page" @click="fetchPayments(meta.current_page + 1)">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m6 4 4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
          </div>
        </footer>
      </section>
    </main>

    <!-- Audit trail drawer -->
    <Transition name="drawer">
      <div v-if="selectedPayment" class="overlay" @click.self="closeDrawer">
        <aside class="drawer" role="dialog" aria-modal="true" :aria-label="`Audit trail for ${selectedPayment}`">
          <header class="drawer-header">
            <div>
              <span class="eyebrow">Audit trail</span>
              <h2 class="mono">{{ selectedPayment }}</h2>
            </div>
            <button class="btn btn-ghost icon-btn" aria-label="Close" @click="closeDrawer">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
            </button>
          </header>

          <div v-if="selectedRow" class="summary">
            <div>
              <span class="eyebrow">Amount</span>
              <span class="summary-amount">{{ formatMoney(selectedRow.amount, selectedRow.currency) }}</span>
            </div>
            <div>
              <span class="eyebrow">Status</span>
              <StatusBadge :event="lastEvent?.event || selectedRow.event" />
            </div>
            <div>
              <span class="eyebrow">User</span>
              <span>{{ selectedRow.user_id }}</span>
            </div>
          </div>

          <div class="drawer-body">
            <div v-if="eventsLoading" class="timeline">
              <div v-for="n in 3" :key="n" class="tl-item">
                <span class="tl-dot" />
                <div class="tl-card">
                  <div class="skeleton" style="width: 90px; height: 10px; margin-bottom: 10px" />
                  <div class="skeleton" style="width: 180px; height: 12px" />
                </div>
              </div>
            </div>

            <ol v-else class="timeline">
              <li
                v-for="(evt, i) in paymentEvents"
                :key="evt.id"
                class="tl-item"
                :style="{ '--i': i }"
              >
                <span :class="['tl-dot', toneOf(evt.event)]" />
                <div class="tl-card">
                  <div class="tl-top">
                    <StatusBadge :event="evt.event" />
                    <time class="subtle">{{ new Date(evt.timestamp).toLocaleString() }}</time>
                  </div>
                  <dl>
                    <div><dt>Event ID</dt><dd class="mono">{{ evt.event_id }}</dd></div>
                    <div><dt>Amount</dt><dd>{{ evt.amount }} {{ evt.currency }}</dd></div>
                  </dl>
                </div>
              </li>
            </ol>
          </div>

          <footer class="drawer-footer">
            <button
              v-if="!isRefunded"
              class="btn btn-danger refund-btn"
              :disabled="refundLoading || eventsLoading"
              @click="triggerRefund(selectedPayment)"
            >
              <span v-if="refundLoading" class="spinner" />
              {{ refundLoading ? 'Dispatching webhook…' : 'Simulate refund webhook' }}
            </button>
            <div v-else class="refunded-note">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 8.5 3 3 7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
              This payment has been refunded
            </div>
          </footer>
        </aside>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { usePaymentsStore } from '@/stores/payments'

definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()
const paymentsStore = usePaymentsStore()

// Map state to refs so the existing template bindings work perfectly
const {
  payments,
  meta,
  loading,
  filters,
  selectedPaymentId: selectedPayment,
  paymentEvents,
  eventsLoading,
  refundLoading
} = storeToRefs(paymentsStore)

// Map actions
const fetchPayments = (page = null) => paymentsStore.fetchPayments(page)
const viewDetails = (paymentId) => paymentsStore.fetchPaymentEvents(paymentId)
const triggerRefund = (paymentId) => paymentsStore.triggerRefund(paymentId)
const closeDrawer = () => { selectedPayment.value = null }
const logout = () => authStore.logout()

const refreshing = ref(false)
const refreshData = async () => {
  refreshing.value = true
  const minSpin = new Promise(r => setTimeout(r, 600))
  await Promise.all([paymentsStore.fetchPayments(meta.value?.current_page || 1, true), minSpin])
  refreshing.value = false
}

// Filters
const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Success', value: 'payment.success', dot: 'var(--success)' },
  { label: 'Pending', value: 'payment.pending', dot: 'var(--warning)' },
  { label: 'Refunded', value: 'payment.refunded', dot: 'var(--danger)' }
]
const currencyOptions = [
  { label: 'All', value: '' },
  { label: 'USD', value: 'USD' },
  { label: 'EUR', value: 'EUR' },
  { label: 'GBP', value: 'GBP' }
]

const userQuery = ref(filters.value.user_id)
let searchTimer
watch(userQuery, (v) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { filters.value.user_id = v.trim() }, 300)
})
watch(() => [filters.value.event, filters.value.currency, filters.value.user_id], () => fetchPayments(1))

const hasFilters = computed(() => !!(filters.value.event || filters.value.currency || filters.value.user_id))
const clearFilters = () => {
  userQuery.value = ''
  Object.assign(filters.value, { event: '', currency: '', user_id: '' })
}

// Re-key the tbody on page/filter changes so rows stagger in again, but not on silent polls
const listKey = computed(() => `${meta.value?.current_page}-${filters.value.event}-${filters.value.currency}-${filters.value.user_id}`)

// Highlight rows whose status changed between polls
const flashed = ref(new Set())
let lastSeen = new Map()
watch(payments, (rows) => {
  const changed = rows.filter(p => lastSeen.has(p.payment_id) && lastSeen.get(p.payment_id) !== p.event).map(p => p.payment_id)
  lastSeen = new Map(rows.map(p => [p.payment_id, p.event]))
  if (!changed.length) return
  flashed.value = new Set(changed)
  setTimeout(() => { flashed.value = new Set() }, 1600)
})

// KPIs (status mix is for the rows currently loaded)
const stats = computed(() => {
  const rows = payments.value
  const count = (e) => rows.filter(p => p.event === e).length
  return [
    { label: 'Total payments', value: meta.value?.total ?? 0, hint: 'Matching current filters' },
    { label: 'Settled', value: count('payment.success'), hint: 'On this page' },
    { label: 'Pending', value: count('payment.pending'), hint: 'Awaiting webhook' },
    { label: 'Refunded', value: count('payment.refunded'), hint: 'On this page' }
  ]
})

const rangeStart = computed(() => (meta.value.current_page - 1) * 8 + 1)
const rangeEnd = computed(() => rangeStart.value + payments.value.length - 1)

// Drawer
const selectedRow = computed(() => payments.value.find(p => p.payment_id === selectedPayment.value))
const lastEvent = computed(() => paymentEvents.value[paymentEvents.value.length - 1])
const isRefunded = computed(() => lastEvent.value?.event === 'payment.refunded')

// Helpers
const toneOf = (evt) => evt === 'payment.success' ? 'success' : evt === 'payment.refunded' ? 'danger' : 'warning'

const formatMoney = (amount, currency) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(parseFloat(amount))

const now = ref(Date.now())
const timeAgo = (iso) => {
  const s = Math.max(0, Math.round((now.value - new Date(iso).getTime()) / 1000))
  if (s < 60) return `${s}s ago`
  if (s < 3600) return `${Math.floor(s / 60)}m ago`
  if (s < 86400) return `${Math.floor(s / 3600)}h ago`
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

const copied = ref(null)
const copy = async (text) => {
  await navigator.clipboard?.writeText(text)
  copied.value = text
  setTimeout(() => { if (copied.value === text) copied.value = null }, 1200)
}

// Cursor-following glow on stat cards
const trackPointer = (e) => {
  const r = e.currentTarget.getBoundingClientRect()
  e.currentTarget.style.setProperty('--mx', `${e.clientX - r.left}px`)
  e.currentTarget.style.setProperty('--my', `${e.clientY - r.top}px`)
}

// Keyboard: "/" focuses search, Esc closes drawer
const searchInput = ref()
const onKey = (e) => {
  if (e.key === 'Escape' && selectedPayment.value) closeDrawer()
  if (e.key === '/' && document.activeElement?.tagName !== 'INPUT') {
    e.preventDefault()
    searchInput.value?.focus()
  }
}

let clock
onMounted(() => {
  paymentsStore.fetchPayments(1)
  paymentsStore.startPolling()
  window.addEventListener('keydown', onKey)
  clock = setInterval(() => { now.value = Date.now() }, 1000)
})

onUnmounted(() => {
  paymentsStore.stopPolling()
  window.removeEventListener('keydown', onKey)
  clearInterval(clock)
})
</script>

<style scoped>
.shell {
  position: relative;
  display: grid;
  grid-template-columns: 232px 1fr;
  min-height: 100vh;
}

/* ---------- Sidebar ---------- */
.sidebar {
  position: sticky;
  top: 0;
  z-index: 1;
  height: 100vh;
  display: flex;
  flex-direction: column;
  padding: 14px 12px;
  background: rgba(10, 10, 11, 0.7);
  border-right: 1px solid var(--border);
  backdrop-filter: blur(12px);
}
.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 32px;
  padding: 0 8px;
  margin-bottom: 18px;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: -0.02em;
}
.nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 32px;
  padding: 0 8px;
  border-radius: var(--radius);
  color: var(--text-muted);
  text-decoration: none;
  font-weight: 500;
  font-size: 13.5px;
  transition: background-color var(--dur-fast) var(--ease-std), color var(--dur-fast) var(--ease-std);
}
.nav-item:hover {
  background: var(--surface-hover);
  color: var(--text);
}
.nav-item.active {
  background: var(--surface-3);
  color: var(--text);
  box-shadow: inset 0 0 0 1px var(--border);
}
.nav-count {
  margin-left: auto;
  font-size: 11.5px;
  color: var(--text-subtle);
  font-variant-numeric: tabular-nums;
}
.sidebar-footer {
  margin-top: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.live-card {
  display: flex;
  gap: 10px;
  padding: 10px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  background: var(--surface-2);
}
.live-card strong {
  display: block;
  font-size: 12.5px;
  font-weight: 500;
}
.live-card span:not(.live-dot) {
  font-size: 11.5px;
  color: var(--text-subtle);
}
.live-dot {
  position: relative;
  flex: none;
  width: 7px;
  height: 7px;
  margin-top: 6px;
  border-radius: 50%;
  background: var(--success);
}
.live-dot::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: var(--success);
  animation: pulse-ring 2s var(--ease-out) infinite;
}
.logout {
  justify-content: flex-start;
  padding: 0 8px;
}

/* ---------- Main ---------- */
.main {
  position: relative;
  z-index: 1;
  min-width: 0;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 28px 32px 64px;
}
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
}
.crumbs {
  margin-bottom: 8px;
  font-size: 12.5px;
  color: var(--text-subtle);
}
.crumbs span {
  margin: 0 4px;
  opacity: 0.6;
}
.title {
  margin: 0;
  font-size: 30px;
  font-weight: 500;
  letter-spacing: -0.03em;
  line-height: 1.1;
}
.refresh-icon {
  transition: transform var(--dur) var(--ease-out);
}
.btn:hover .refresh-icon {
  transform: rotate(45deg);
}
.refresh-icon.spinning {
  animation: spin 0.8s linear infinite;
}

/* KPI cards */
.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  margin-bottom: 20px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--border);
  overflow: hidden;
}
.stat {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 16px 18px;
  background: var(--surface);
  overflow: hidden;
}
.stat::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(220px circle at var(--mx, 50%) var(--my, 50%), rgba(255, 255, 255, 0.06), transparent 70%);
  opacity: 0;
  transition: opacity var(--dur) var(--ease-std);
  pointer-events: none;
}
.stat:hover::before {
  opacity: 1;
}
.stat-label {
  font-size: 12.5px;
  color: var(--text-muted);
}
.stat-value {
  font-size: 26px;
  font-weight: 500;
  letter-spacing: -0.03em;
}
.stat-hint {
  font-size: 11.5px;
  color: var(--text-subtle);
}

/* Toolbar */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}
.search {
  position: relative;
  flex: 1 1 160px;
  max-width: 240px;
  display: flex;
  align-items: center;
  margin-left: auto;
  color: var(--text-subtle);
}
.search svg {
  position: absolute;
  left: 10px;
  pointer-events: none;
}
.search .field {
  width: 100%;
  padding-left: 30px;
  padding-right: 30px;
}
.search .kbd {
  position: absolute;
  right: 7px;
  pointer-events: none;
  transition: opacity var(--dur-fast);
}
.search .field:focus ~ .kbd {
  opacity: 0;
}

/* Table */
.table-card {
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  overflow: hidden;
}
.table-scroll {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th {
  height: 36px;
  padding: 0 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 500;
  color: var(--text-subtle);
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
td {
  height: 48px;
  padding: 0 16px;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
tbody tr:last-child td {
  border-bottom: 0;
}
.num {
  text-align: right;
}
.muted { color: var(--text-muted); }
.subtle { color: var(--text-subtle); }

tbody {
  transition: opacity var(--dur) var(--ease-std);
}
tbody.dimmed {
  opacity: 0.5;
}
.row {
  position: relative;
  cursor: pointer;
  opacity: 0;
  animation: fade-up 500ms var(--ease-out) forwards;
  animation-delay: calc(var(--i) * 35ms);
  transition: background-color var(--dur-fast) var(--ease-std);
}
.row:hover,
.row:focus-visible,
.row.selected {
  background: var(--surface-hover);
  outline: none;
}
.row.flash td {
  animation: flash 1.6s var(--ease-out);
}
@keyframes flash {
  0%, 20% { background: rgba(74, 222, 128, 0.1); }
  100% { background: transparent; }
}
.amount {
  font-weight: 500;
  font-variant-numeric: tabular-nums;
}
.currency {
  margin-left: 4px;
  font-size: 11px;
  font-weight: 400;
  color: var(--text-subtle);
}
.id-cell {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.copy {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  padding: 0;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: var(--text-subtle);
  opacity: 0;
  transform: translateX(-4px);
  transition: all var(--dur) var(--ease-out);
}
.row:hover .copy,
.copy:focus-visible {
  opacity: 1;
  transform: none;
}
.copy:hover {
  background: var(--surface-3);
  color: var(--text);
}
.chevron {
  width: 40px;
  color: var(--text-subtle);
}
.chevron svg {
  opacity: 0;
  transform: translateX(-6px);
  transition: all var(--dur) var(--ease-out);
}
.row:hover .chevron svg,
.row.selected .chevron svg {
  opacity: 1;
  transform: none;
}
.skeleton-row td {
  height: 48px;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 56px 16px;
  text-align: center;
  color: var(--text-muted);
  animation: fade-up 500ms var(--ease-out);
}
.empty strong {
  color: var(--text);
  font-weight: 500;
}
.empty .btn {
  margin-top: 10px;
}
.empty-icon {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  margin-bottom: 8px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--surface-2);
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 48px;
  padding: 0 8px 0 16px;
  border-top: 1px solid var(--border);
  font-size: 12.5px;
}
.pagination strong {
  color: var(--text);
  font-weight: 500;
}
.pager {
  display: flex;
  align-items: center;
  gap: 4px;
}
.page-info {
  min-width: 44px;
  text-align: center;
  color: var(--text-muted);
  font-variant-numeric: tabular-nums;
}
.icon-btn {
  width: 30px;
  padding: 0;
}

/* ---------- Drawer ---------- */
.overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  justify-content: flex-end;
  padding: 8px;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
}
.drawer {
  display: flex;
  flex-direction: column;
  width: min(460px, 100%);
  height: 100%;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.04);
  overflow: hidden;
}
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 300ms var(--ease-std), backdrop-filter 300ms var(--ease-std);
}
.drawer-enter-active .drawer,
.drawer-leave-active .drawer {
  transition: transform 500ms var(--ease-out), opacity 300ms var(--ease-std);
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
  backdrop-filter: blur(0);
}
.drawer-enter-from .drawer,
.drawer-leave-to .drawer {
  transform: translateX(40px) scale(0.98);
  opacity: 0;
}
.drawer-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 18px 14px 20px;
}
.drawer-header h2 {
  margin: 4px 0 0;
  font-size: 15px;
  font-weight: 500;
}
.eyebrow {
  display: block;
  margin-bottom: 4px;
  font-size: 11.5px;
  color: var(--text-subtle);
}
.summary {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr;
  gap: 1px;
  margin: 0 20px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--border);
  overflow: hidden;
}
.summary > div {
  padding: 12px 14px;
  background: var(--surface-2);
}
.summary-amount {
  font-size: 18px;
  font-weight: 500;
  letter-spacing: -0.02em;
}
.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px 20px;
}
.timeline {
  position: relative;
  margin: 0;
  padding: 0;
  list-style: none;
}
.timeline::before {
  content: '';
  position: absolute;
  top: 8px;
  bottom: 8px;
  left: 4px;
  width: 1px;
  background: linear-gradient(var(--border-strong), var(--border) 80%, transparent);
  transform-origin: top;
  animation: draw 700ms var(--ease-out) both;
}
@keyframes draw {
  from { transform: scaleY(0); }
}
.tl-item {
  position: relative;
  padding-left: 24px;
  margin-bottom: 12px;
  opacity: 0;
  animation: fade-up 500ms var(--ease-out) forwards;
  animation-delay: calc(120ms + var(--i, 0) * 90ms);
}
.tl-dot {
  position: absolute;
  top: 16px;
  left: 0;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: var(--surface);
  border: 1.5px solid var(--text-subtle);
}
.tl-dot.success { border-color: var(--success); box-shadow: 0 0 10px rgba(74, 222, 128, 0.4); }
.tl-dot.warning { border-color: var(--warning); box-shadow: 0 0 10px rgba(251, 191, 36, 0.35); }
.tl-dot.danger  { border-color: var(--danger);  box-shadow: 0 0 10px rgba(248, 113, 113, 0.4); }
.tl-card {
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface-2);
  transition: border-color var(--dur) var(--ease-std);
}
.tl-card:hover {
  border-color: var(--border-strong);
}
.tl-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
  font-size: 12px;
}
dl {
  display: grid;
  gap: 4px;
  margin: 0;
  font-size: 12.5px;
}
dl > div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}
dt { color: var(--text-subtle); }
dd { margin: 0; color: var(--text-muted); }

.drawer-footer {
  padding: 14px 20px 18px;
  border-top: 1px solid var(--border);
}
.refund-btn {
  width: 100%;
  height: 38px;
}
.refunded-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 38px;
  border-radius: var(--radius);
  background: var(--surface-2);
  color: var(--text-muted);
  font-size: 13px;
  animation: fade-up 400ms var(--ease-out);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity var(--dur) var(--ease-std);
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ---------- Responsive ---------- */
@media (max-width: 960px) {
  .stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 760px) {
  .shell { grid-template-columns: 1fr; }
  .sidebar {
    position: relative;
    height: auto;
    flex-direction: row;
    align-items: center;
    padding: 8px 12px;
    border-right: 0;
    border-bottom: 1px solid var(--border);
  }
  .brand { margin: 0; }
  .nav, .live-card { display: none; }
  .sidebar-footer { margin: 0 0 0 auto; }
  .main { padding: 20px 16px 48px; }
  .search { flex-basis: 100%; max-width: none; }
}
</style>
