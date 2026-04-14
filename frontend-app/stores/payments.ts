import { defineStore } from 'pinia'
import { useAuthStore } from './auth'
import { api } from '../utils/api'

export const usePaymentsStore = defineStore('payments', {
    state: () => ({
        payments: [] as any[],
        meta: null as null | { current_page: number; last_page: number; total: number },
        loading: false,
        filters: {
            event: '',
            currency: '',
            user_id: ''
        },
        selectedPaymentId: null as string | null,
        paymentEvents: [] as any[],
        eventsLoading: false,
        refundLoading: false,
        pollingInterval: null as ReturnType<typeof setInterval> | null
    }),
    
    actions: {
        async fetchPayments(page = 1, isSilent = false) {
            if (!isSilent) this.loading = true
            
            try {
                const query: Record<string, any> = { page, per_page: 8, ...this.filters }

                Object.keys(query).forEach(k => {
                    if (query[k] === '' || query[k] === null) delete query[k]
                })
                
                const res = await api.get('/payments', query) as any
                
                if (res.current_page) {
                    this.payments = res.data
                    this.meta = {
                        current_page: res.current_page,
                        last_page: res.last_page,
                        total: res.total
                    }
                } else {
                    this.payments = res.data || res
                }
            } catch (err: any) {
                if (err.response?.status === 401) {
                    const authStore = useAuthStore()
                    authStore.logout()
                }
            } finally {
                if (!isSilent) this.loading = false
            }
        },
        
        async fetchPaymentEvents(paymentId: string) {
            this.selectedPaymentId = paymentId
            this.eventsLoading = true
            try {
                const res = await api.get(`/payments/${paymentId}/events`) as any
                this.paymentEvents = res.data || res
            } catch (e) {
                console.error(e)
            } finally {
                this.eventsLoading = false
            }
        },
        
        async triggerRefund(paymentId: string) {
            this.refundLoading = true
            try {
                await api.post(`/payments/${paymentId}/refund`)
                // Refresh data states natively!
                await this.fetchPaymentEvents(paymentId)
                await this.fetchPayments(this.meta?.current_page || 1, true)
            } catch (e: any) {
                alert(e.response?._data?.message || 'Failed to refund')
            } finally {
                this.refundLoading = false
            }
        },
        
        startPolling() {
            this.stopPolling()
            this.pollingInterval = setInterval(() => {
                if (!this.loading) {
                   this.fetchPayments(this.meta?.current_page || 1, true)
                }
            }, 5000)
        },
        
        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval)
                this.pollingInterval = null
            }
        }
    }
})
