<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhPencilSimple, PhTrash, PhPaperPlaneTilt,
    PhDownloadSimple, PhCheckCircle, PhPlus, PhWarning,
    PhReceipt, PhCurrencyEur, PhCalendarBlank, PhUser,
    PhShoppingBag, PhCalendarCheck, PhSpinnerGap, PhX,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    invoice:         Object,
    payment_methods: Array,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Modals ─────────────────────────────────────────────────────────
const showPaymentModal = ref(false)
const sendingEmail     = ref(false)

// ── Formulaire paiement partiel ────────────────────────────────────
const paymentForm = useForm({
    amount:  props.invoice.amount_due,
    method:  'cash',
    paid_at: new Date().toISOString().split('T')[0],
    notes:   '',
})

function addPayment() {
    paymentForm.post(route('admin.factures.payment', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => { showPaymentModal.value = false; paymentForm.reset() },
    })
}

// ── Marquer payée (solde total) ────────────────────────────────────
const markPaidForm = useForm({
    method:  'cash',
    paid_at: new Date().toISOString().split('T')[0],
    notes:   '',
})
const showMarkPaidModal = ref(false)

function markAsPaid() {
    markPaidForm.patch(route('admin.factures.paid', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => { showMarkPaidModal.value = false },
    })
}

// ── Envoyer par email ──────────────────────────────────────────────
function sendByEmail() {
    sendingEmail.value = true
    router.post(route('admin.factures.send', props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => { sendingEmail.value = false },
    })
}

// ── Suppression ────────────────────────────────────────────────────
function deleteInvoice() {
    if (!confirm(`Supprimer la facture ${props.invoice.invoice_number} ?`)) return
    router.delete(route('admin.factures.destroy', props.invoice.id))
}

// ── Format ─────────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

function statusStyle(status) {
    const map = {
        paid:      { bg: 'rgba(16,185,129,0.1)', color: '#059669' },
        draft:     { bg: 'rgba(107,114,128,0.1)', color: '#6b7280' },
        sent:      { bg: 'rgba(59,130,246,0.1)', color: '#2563eb' },
        overdue:   { bg: 'rgba(239,68,68,0.1)', color: '#dc2626' },
        cancelled: { bg: 'rgba(107,114,128,0.08)', color: '#9ca3af' },
    }
    return map[status] ?? map.draft
}

const canMarkPaid = computed(() => !['paid', 'cancelled'].includes(props.invoice.status) && props.invoice.amount_due > 0)
const canSend     = computed(() => !['cancelled'].includes(props.invoice.status))

onMounted(() => {
    gsap.fromTo('.invoice-card',
        { opacity: 0, y: 18 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.07, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <!-- Modal paiement partiel -->
    <Transition name="fade">
        <div v-if="showPaymentModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
             @click.self="showPaymentModal = false">
            <div class="bg-white rounded-2xl shadow-luxury w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-poppins font-bold text-onyx-800">Enregistrer un paiement</h3>
                    <button @click="showPaymentModal = false" class="text-onyx-400 hover:text-onyx-700"><PhX :size="20" /></button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1 uppercase tracking-wide">Montant</label>
                        <input v-model="paymentForm.amount" type="number" step="0.01" :max="invoice.amount_due"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1 uppercase tracking-wide">Méthode</label>
                        <select v-model="paymentForm.method" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none">
                            <option v-for="m in payment_methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1 uppercase tracking-wide">Date</label>
                        <input v-model="paymentForm.paid_at" type="date"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1 uppercase tracking-wide">Notes (optionnel)</label>
                        <input v-model="paymentForm.notes" type="text"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showPaymentModal = false"
                            class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                        Annuler
                    </button>
                    <button @click="addPayment" :disabled="paymentForm.processing"
                            class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all disabled:opacity-60"
                            style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhSpinnerGap v-if="paymentForm.processing" :size="16" class="animate-spin mx-auto" />
                        <span v-else>Enregistrer</span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.factures.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                    <PhArrowLeft :size="18" />
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="font-cormorant text-3xl font-bold text-onyx-800">{{ invoice.invoice_number }}</h1>
                        <span class="px-3 py-1 rounded-xl text-xs font-poppins font-semibold"
                              :style="{ background: statusStyle(invoice.status).bg, color: statusStyle(invoice.status).color }">
                            {{ invoice.status_label }}
                        </span>
                        <span v-if="invoice.is_overdue"
                              class="px-2.5 py-1 rounded-xl text-xs font-poppins font-bold bg-red-100 text-red-600">
                            En retard
                        </span>
                    </div>
                    <p class="text-sm font-poppins text-onyx-500 mt-0.5">
                        {{ invoice.client.full_name }} · Émise le {{ invoice.issue_date }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button v-if="canSend" @click="sendByEmail" :disabled="sendingEmail"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors disabled:opacity-60">
                    <PhSpinnerGap v-if="sendingEmail" :size="16" class="animate-spin" />
                    <PhPaperPlaneTilt v-else :size="16" />
                    {{ sendingEmail ? 'Envoi…' : 'Envoyer' }}
                </button>
                <a :href="route('admin.factures.pdf', invoice.id)" target="_blank"
                   class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 transition-colors shadow-card">
                    <PhDownloadSimple :size="16" /> PDF
                </a>
                <Link :href="route('admin.factures.edit', invoice.id)"
                      class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 transition-colors shadow-card">
                    <PhPencilSimple :size="16" /> Modifier
                </Link>
                <button @click="deleteInvoice"
                        class="w-9 h-9 rounded-xl flex items-center justify-center border border-red-200 bg-white text-red-500 hover:bg-red-50 transition-colors">
                    <PhTrash :size="16" />
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Colonne principale -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Parties -->
                <div class="invoice-card bg-white rounded-2xl shadow-card p-5">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-poppins font-semibold text-cognac-600 uppercase tracking-widest mb-2">Émetteur</p>
                            <p class="font-poppins font-bold text-onyx-800">Patricia Braids Studio</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-1 leading-relaxed">
                                contact@patricia-braids.fr
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-poppins font-semibold text-cognac-600 uppercase tracking-widest mb-2">Client</p>
                            <p class="font-poppins font-bold text-onyx-800">{{ invoice.client_snapshot?.name ?? invoice.client.full_name }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-1 leading-relaxed">
                                {{ invoice.client_snapshot?.email ?? invoice.client.email }}<br>
                                <template v-if="invoice.client_snapshot?.phone">{{ invoice.client_snapshot.phone }}<br></template>
                                <template v-if="invoice.client_snapshot?.address">
                                    {{ invoice.client_snapshot.address }}, {{ invoice.client_snapshot.postal_code }} {{ invoice.client_snapshot.city }}
                                </template>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Lignes de facture -->
                <div class="invoice-card bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-cream-100">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Détail des prestations</h2>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr style="background: #faf7f4; border-bottom: 1px solid #f5ede4;">
                                <th class="text-left px-5 py-3 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Description</th>
                                <th class="text-right px-4 py-3 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Qté</th>
                                <th class="text-right px-4 py-3 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Prix unit.</th>
                                <th v-if="invoice.items.some(i => i.discount_percent > 0)"
                                    class="text-right px-4 py-3 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Remise</th>
                                <th class="text-right px-5 py-3 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-50">
                            <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-cream-50/40 transition-colors">
                                <td class="px-5 py-3.5">
                                    <p class="text-sm font-poppins font-semibold text-onyx-800">{{ item.description }}</p>
                                    <p v-if="item.details" class="text-xs font-poppins text-onyx-400 mt-0.5">{{ item.details }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-right text-sm font-poppins text-onyx-600">{{ item.quantity }}</td>
                                <td class="px-4 py-3.5 text-right text-sm font-poppins text-onyx-600">{{ formatPrice(item.unit_price) }}</td>
                                <td v-if="invoice.items.some(i => i.discount_percent > 0)"
                                    class="px-4 py-3.5 text-right text-sm font-poppins text-emerald-600">
                                    {{ item.discount_percent > 0 ? item.discount_percent + '%' : '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-right text-sm font-bold font-poppins text-onyx-800">{{ formatPrice(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totaux -->
                    <div class="px-5 py-4 border-t border-cream-100">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2">
                                <div class="flex justify-between text-sm font-poppins">
                                    <span class="text-onyx-500">Sous-total HT</span>
                                    <span class="text-onyx-700">{{ formatPrice(invoice.subtotal) }}</span>
                                </div>
                                <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm font-poppins">
                                    <span class="text-emerald-600">Remise</span>
                                    <span class="text-emerald-600 font-semibold">-{{ formatPrice(invoice.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm font-poppins">
                                    <span class="text-onyx-500">TVA ({{ invoice.tax_rate }}%)</span>
                                    <span class="text-onyx-700">{{ formatPrice(invoice.tax_amount) }}</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-cream-100">
                                    <span class="font-poppins font-bold text-onyx-800">Total TTC</span>
                                    <span class="text-xl font-bold font-poppins text-onyx-800">{{ formatPrice(invoice.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paiements reçus -->
                <div v-if="invoice.payments?.length" class="invoice-card bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-cream-100">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Paiements reçus</h2>
                    </div>
                    <div class="divide-y divide-cream-50">
                        <div v-for="payment in invoice.payments" :key="payment.id"
                             class="flex items-center justify-between px-5 py-3.5">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">{{ payment.method }}</p>
                                <p v-if="payment.notes" class="text-xs font-poppins text-onyx-400">{{ payment.notes }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold font-poppins text-emerald-600">{{ formatPrice(payment.amount) }}</p>
                                <p class="text-xs font-poppins text-onyx-400">{{ payment.paid_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="invoice.notes" class="invoice-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-2">Notes</h2>
                    <p class="text-sm font-poppins text-onyx-600 leading-relaxed">{{ invoice.notes }}</p>
                </div>
            </div>

            <!-- Colonne droite -->
            <div class="space-y-5">

                <!-- Solde -->
                <div class="invoice-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhCurrencyEur :size="16" class="text-cognac-500" />
                        Solde
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm font-poppins">
                            <span class="text-onyx-500">Total facturé</span>
                            <span class="font-semibold text-onyx-800">{{ formatPrice(invoice.total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-poppins">
                            <span class="text-onyx-500">Déjà payé</span>
                            <span class="font-semibold text-emerald-600">{{ formatPrice(invoice.amount_paid) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-cream-100">
                            <span class="font-poppins font-bold text-onyx-800">Reste dû</span>
                            <span class="text-xl font-bold font-poppins"
                                  :class="invoice.amount_due > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ invoice.amount_due > 0 ? formatPrice(invoice.amount_due) : '✓ Soldé' }}
                            </span>
                        </div>
                    </div>

                    <!-- Boutons paiement -->
                    <div v-if="canMarkPaid" class="mt-4 space-y-2">
                        <button @click="showPaymentModal = true"
                                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                                style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhPlus :size="16" /> Enregistrer un paiement
                        </button>
                    </div>
                </div>

                <!-- Dates -->
                <div class="invoice-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhCalendarBlank :size="16" class="text-cognac-500" />
                        Dates
                    </h2>
                    <div class="space-y-2.5 text-sm font-poppins">
                        <div class="flex justify-between">
                            <span class="text-onyx-500">Émission</span>
                            <span class="font-semibold text-onyx-700">{{ invoice.issue_date }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-onyx-500" :class="invoice.is_overdue ? 'text-red-500' : ''">Échéance</span>
                            <span class="font-semibold" :class="invoice.is_overdue ? 'text-red-600' : 'text-onyx-700'">
                                {{ invoice.due_date }}
                            </span>
                        </div>
                        <div v-if="invoice.sent_at" class="flex justify-between">
                            <span class="text-onyx-500">Envoyée</span>
                            <span class="font-semibold text-blue-600">{{ invoice.sent_at }}</span>
                        </div>
                        <div v-if="invoice.paid_at" class="flex justify-between">
                            <span class="text-onyx-500">Payée</span>
                            <span class="font-semibold text-emerald-600">{{ invoice.paid_at }}</span>
                        </div>
                    </div>
                </div>

                <!-- Liens liés -->
                <div class="invoice-card bg-white rounded-2xl shadow-card p-5 space-y-3">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Documents liés</h2>

                    <Link v-if="invoice.linked_order" :href="route('admin.commandes.show', invoice.linked_order.id)"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-cream-200 hover:border-cognac-300 hover:bg-cream-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(196,149,106,0.1);">
                            <PhShoppingBag :size="15" class="text-cognac-500" />
                        </div>
                        <div>
                            <p class="text-xs font-poppins font-semibold text-onyx-700 group-hover:text-cognac-700">Commande</p>
                            <p class="text-xs font-poppins text-onyx-400">{{ invoice.linked_order.number }}</p>
                        </div>
                    </Link>

                    <Link v-if="invoice.linked_appointment" :href="route('admin.rendez-vous.show', invoice.linked_appointment.id)"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-cream-200 hover:border-cognac-300 hover:bg-cream-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(196,149,106,0.1);">
                            <PhCalendarCheck :size="15" class="text-cognac-500" />
                        </div>
                        <div>
                            <p class="text-xs font-poppins font-semibold text-onyx-700 group-hover:text-cognac-700">Rendez-vous</p>
                            <p class="text-xs font-poppins text-onyx-400">{{ invoice.linked_appointment.ref }}</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.clients.show', invoice.client.id)"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-cream-200 hover:border-cognac-300 hover:bg-cream-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(196,149,106,0.1);">
                            <PhUser :size="15" class="text-cognac-500" />
                        </div>
                        <div>
                            <p class="text-xs font-poppins font-semibold text-onyx-700 group-hover:text-cognac-700">Fiche client</p>
                            <p class="text-xs font-poppins text-onyx-400">{{ invoice.client.full_name }}</p>
                        </div>
                    </Link>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>