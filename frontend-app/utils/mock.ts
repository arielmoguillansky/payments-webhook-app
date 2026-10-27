// In-memory stand-in for the Laravel API, enabled with NUXT_PUBLIC_MOCK=true.
// Mirrors the endpoints used by the stores and simulates webhooks arriving over time.

type Evt = { id: number; payment_id: string; event: string; event_id: string; amount: string; currency: string; timestamp: string }

const currencies = ['USD', 'EUR', 'GBP']
const users = ['user_1', 'user_2', 'user_3', 'user_4', 'user_5', 'user_7', 'user_12']

let evtSeq = 1
const events: Evt[] = []
const now = Date.now()

const addEvent = (paymentId: string, event: string, amount: string, currency: string, at: number) => {
    events.push({
        id: evtSeq++,
        payment_id: paymentId,
        event,
        event_id: `evt_${Math.random().toString(36).slice(2, 12)}`,
        amount,
        currency,
        timestamp: new Date(at).toISOString()
    })
}

const payments = Array.from({ length: 34 }, (_, i) => {
    const id = `pay_${(1000 + i * 37).toString(36)}${Math.random().toString(36).slice(2, 7)}`
    const amount = (Math.round((12 + Math.random() * 980) * 100) / 100).toFixed(2)
    const currency = currencies[i % 3]
    const created = now - (34 - i) * 1000 * 60 * 47
    addEvent(id, 'payment.pending', amount, currency, created)
    const roll = Math.random()
    if (roll > 0.25) addEvent(id, 'payment.success', amount, currency, created + 90_000)
    if (roll > 0.85) addEvent(id, 'payment.refunded', amount, currency, Math.min(created + 3_600_000, now - 120_000))
    return { payment_id: id, user_id: users[i % users.length], amount, currency }
})

const latest = (paymentId: string) => events.filter(e => e.payment_id === paymentId).at(-1)!

const toRow = (p: typeof payments[number]) => {
    const last = latest(p.payment_id)
    return { ...p, event: last.event, last_event_id: last.event_id, updated_at: last.timestamp }
}

// Every few polls a pending payment settles, so the "live" state is visible
let tick = 0
const simulateIncomingWebhook = () => {
    if (++tick % 2) return
    const pending = payments.filter(p => latest(p.payment_id).event === 'payment.pending')
    const p = pending[Math.floor(Math.random() * pending.length)]
    if (p) addEvent(p.payment_id, 'payment.success', p.amount, p.currency, Date.now())
}

const delay = (ms: number) => new Promise(r => setTimeout(r, ms))

export async function mockRequest(url: string, options: any = {}) {
    await delay(250 + Math.random() * 350)
    const method = options.method || 'GET'

    if (url === '/login' && method === 'POST') {
        return { token: 'demo-token', user: { name: 'Demo Admin', email: options.body?.email } }
    }

    if (url === '/payments' && method === 'GET') {
        simulateIncomingWebhook()
        const q = options.query || {}
        let rows = payments.map(toRow).sort((a, b) => b.updated_at.localeCompare(a.updated_at))
        if (q.event) rows = rows.filter(r => r.event === q.event)
        if (q.currency) rows = rows.filter(r => r.currency === q.currency)
        if (q.user_id) rows = rows.filter(r => r.user_id.includes(q.user_id))
        const perPage = Number(q.per_page) || 8
        const page = Number(q.page) || 1
        return {
            data: rows.slice((page - 1) * perPage, page * perPage),
            current_page: page,
            last_page: Math.max(1, Math.ceil(rows.length / perPage)),
            total: rows.length
        }
    }

    const match = url.match(/^\/payments\/([^/]+)\/(events|refund)$/)
    if (match) {
        const [, id, action] = match
        const p = payments.find(x => x.payment_id === id)
        if (!p) throw Object.assign(new Error('Not found'), { response: { status: 404 } })
        if (action === 'refund') {
            addEvent(id, 'payment.refunded', p.amount, p.currency, Date.now())
            return { message: 'Refund webhook dispatched' }
        }
        return { data: events.filter(e => e.payment_id === id) }
    }

    throw Object.assign(new Error('Not found'), { response: { status: 404 } })
}
