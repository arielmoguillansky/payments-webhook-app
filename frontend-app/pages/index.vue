<template>
  <div class="dashboard">
    <header>
      <div class="flex-row">
        <h1>Payment Webhooks</h1>
        <button class="logout-btn" @click="logout">Logout</button>
      </div>
    </header>

    <main>
      <!-- Filters -->
      <section class="filters-card">
        <div class="filter-group">
          <label>Status Event</label>
          <select v-model="filters.event" @change="fetchPayments(1)">
            <option value="">All</option>
            <option value="payment.success">Success</option>
            <option value="payment.pending">Pending</option>
            <option value="payment.refunded">Refunded</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Currency</label>
          <select v-model="filters.currency" @change="fetchPayments(1)">
            <option value="">All</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="GBP">GBP</option>
          </select>
        </div>
        <div class="filter-group">
          <label>User ID</label>
          <input v-model.lazy="filters.user_id" type="text" placeholder="e.g. user_1" @change="fetchPayments(1)" />
        </div>
        <button class="refresh-btn" @click="fetchPayments()">Manual Refresh</button>
      </section>

      <!-- Table array -->
      <section class="table-card">
        <table v-if="payments.length > 0">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Amount</th>
              <th>Currency</th>
              <th>Status</th>
              <th>Last Event ID</th>
              <th>Updated At</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments" :key="payment.payment_id" @click="viewDetails(payment.payment_id)">
              <td class="font-mono">{{ payment.payment_id }}</td>
              <td>{{ payment.user_id }}</td>
              <td class="font-bold">${{ parseFloat(payment.amount).toFixed(2) }}</td>
              <td>{{ payment.currency }}</td>
              <td>
                <span :class="['badge', getBadgeClass(payment.event)]">
                  {{ payment.event.replace('payment.', '') }}
                </span>
              </td>
              <td class="text-xs">{{ payment.last_event_id }}</td>
              <td>{{ new Date(payment.updated_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
        
        <div v-else class="empty-state" style="padding: 2rem; text-align: center; color: #64748b;">
           {{ loading ? 'Loading payments...' : 'No payments found matching the criteria.' }}
        </div>

        <!-- Pagination Controls -->
        <div class="pagination" v-if="meta && meta.last_page > 1">
           <button :disabled="meta.current_page === 1" @click="fetchPayments(meta.current_page - 1)">Previous</button>
           <span class="page-info">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
           <button :disabled="meta.current_page === meta.last_page" @click="fetchPayments(meta.current_page + 1)">Next</button>
        </div>
      </section>
    </main>

    <!-- Slide Over Modal for Details -->
    <div v-if="selectedPayment" class="modal-overlay" @click.self="selectedPayment = null">
      <div class="modal-content">
        <div class="modal-header">
           <h2>Audit Trail: {{ selectedPayment }}</h2>
           <button class="close-btn" @click="selectedPayment = null">&times;</button>
        </div>
        
        <div v-if="eventsLoading" class="modal-body">Loading...</div>
        <div v-else class="modal-body timeline">
           <div v-for="evt in paymentEvents" :key="evt.id" class="timeline-item">
             <div class="timeline-dot"></div>
             <div class="timeline-content">
                <span class="evt-time">{{ new Date(evt.timestamp).toLocaleString() }}</span>
                <h4>Event: {{ evt.event }}</h4>
                <p>Event ID: {{ evt.event_id }}</p>
                <div class="evt-meta">
                   <span>Amount: {{ evt.amount }} {{ evt.currency }}</span>
                </div>
             </div>
           </div>
        </div>
        
        <div class="modal-footer">
           <!-- Admin action: Refund -->
           <button class="refund-btn" @click="triggerRefund(selectedPayment)" :disabled="refundLoading || paymentEvents[paymentEvents.length-1]?.event === 'payment.refunded'">
             {{ refundLoading ? 'Processing...' : 'Simulate Webhook Refund' }}
           </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

definePageMeta({
  middleware: 'auth'
})

const token = useCookie('auth_token')
const fetchWithAuth = useApi()

// Table State
const payments = ref([])
const meta = ref(null)
const loading = ref(false)

// Filter State
const filters = ref({
  event: '',
  currency: '',
  user_id: ''
})

const selectedPayment = ref(null)
const paymentEvents = ref([])
const eventsLoading = ref(false)
const refundLoading = ref(false)

let refreshInterval = null

const fetchPayments = async (page = null) => {
  if (!page && meta.value) page = meta.value.current_page
  if (!page) page = 1
  
  loading.value = true
  
  try {
    const query = { page, per_page: 8 }
    if (filters.value.event) query.event = filters.value.event
    if (filters.value.currency) query.currency = filters.value.currency
    if (filters.value.user_id) query.user_id = filters.value.user_id
    
    const res = await fetchWithAuth('/payments', { query })
    
    // Laravel LengthAwarePaginator object
    if (res.current_page) {
       payments.value = res.data
       meta.value = {
         current_page: res.current_page,
         last_page: res.last_page,
         total: res.total
       }
    } else {
       payments.value = res.data || res
    }
  } catch (err) {
    if (err.response?.status === 401) {
       token.value = null
       navigateTo('/login')
    }
  } finally {
    loading.value = false
  }
}

const viewDetails = async (paymentId) => {
  selectedPayment.value = paymentId
  eventsLoading.value = true
  try {
    const res = await fetchWithAuth(`/payments/${paymentId}/events`)
    paymentEvents.value = res.data || res
  } catch (e) {
    console.error(e)
  } finally {
    eventsLoading.value = false
  }
}

const triggerRefund = async (paymentId) => {
  refundLoading.value = true
  try {
    const res = await fetchWithAuth(`/payments/${paymentId}/refund`, {
      method: 'POST'
    })
    await viewDetails(paymentId)
    await fetchPayments()
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to trigger refund')
  } finally {
    refundLoading.value = false
  }
}

const getBadgeClass = (evt) => {
  if (evt === 'payment.success') return 'badge-success'
  if (evt === 'payment.refunded') return 'badge-danger'
  return 'badge-warning'
}

const logout = () => {
  token.value = null
  navigateTo('/login')
}

// Quiet background refresh!
const autoRefresh = async () => {
  if (loading.value) return // Don't interrupt manual loading
  const page = meta.value ? meta.value.current_page : 1
  const query = { page, per_page: 8 }
  if (filters.value.event) query.event = filters.value.event
  if (filters.value.currency) query.currency = filters.value.currency
  if (filters.value.user_id) query.user_id = filters.value.user_id
  
  try {
     const res = await fetchWithAuth('/payments', { query })
     if (res.current_page) {
       payments.value = res.data
       meta.value.total = res.total
     }
  } catch(e) {}
}

onMounted(() => {
  fetchPayments(1)
  refreshInterval = setInterval(autoRefresh, 5000) // Poll API every 5s
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>

<style scoped>
.dashboard {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
header .flex-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
h1 {
  margin: 0;
  font-size: 1.8rem;
}
.logout-btn {
  background: white;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}
.filters-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2rem;
  align-items: flex-end;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
}
.filter-group {
  display: flex;
  flex-direction: column;
}
label {
  font-size: 0.8rem;
  color: #64748b;
  margin-bottom: 0.4rem;
  font-weight: 500;
}
select, input {
  padding: 0.5rem;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  min-width: 150px;
}
.refresh-btn {
  background: var(--primary);
  color: white;
  border: none;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  height: max-content;
}

.table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  overflow: hidden;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}
th {
  background-color: #f8fafc;
  color: #64748b;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
}
tbody tr {
  cursor: pointer;
  transition: background-color 0.1s;
}
tbody tr:hover {
  background-color: #f8fafc;
}

.font-mono { font-family: monospace; color: #475569; }
.font-bold { font-weight: 600; }
.text-xs { font-size: 0.75rem; color: #94a3b8; }

.badge {
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}
.badge-success { background: #dcfce7; color: #166534; }
.badge-pending { background: #fef08a; color: #854d0e; }
.badge-warning { background: #ffedd5; color: #9a3412; }
.badge-danger { background: #fee2e2; color: #991b1b; }

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
}
.pagination button {
  padding: 0.5rem 1rem;
  border: 1px solid #cbd5e1;
  background: white;
  border-radius: 6px;
  cursor: pointer;
}
.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.page-info { font-size: 0.9rem; color: #64748b; }

/* MODAL STYLES */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: flex-end;
  z-index: 50;
}
.modal-content {
  background: white;
  width: 500px;
  height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: -4px 0 15px rgba(0,0,0,0.1);
  animation: slideIn 0.3s ease-out forwards;
}
@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-header h2 { margin: 0; font-size: 1.25rem; }
.close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; }
.modal-body {
  padding: 1.5rem;
  flex: 1;
  overflow-y: auto;
}
.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
}
.refund-btn {
  width: 100%;
  background: #ef4444;
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
}
.refund-btn:disabled { opacity: 0.7; cursor: not-allowed; background: #fca5a5; }

/* TIMELINE */
.timeline {
  display: flex;
  flex-direction: column;
}
.timeline-item {
  position: relative;
  padding-left: 2rem;
  padding-bottom: 2rem;
  border-left: 2px solid #e2e8f0;
}
.timeline-item:last-child { border-left-color: transparent; }
.timeline-dot {
  position: absolute;
  left: -6px;
  top: 0;
  width: 10px;
  height: 10px;
  background: var(--primary);
  border-radius: 50%;
}
.timeline-content {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.evt-time { font-size: 0.75rem; color: #64748b; font-weight: bold; margin-bottom: 0.25rem; display: block; }
.timeline-content h4 { margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1rem; }
.timeline-content p { margin: 0 0 0.5rem 0; font-size: 0.85rem; font-family: monospace; color: #475569;}
.evt-meta span { display: inline-block; background: #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;}
</style>
