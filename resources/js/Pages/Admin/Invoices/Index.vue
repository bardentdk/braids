<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhReceipt, PhPlus, PhMagnifyingGlass, PhFunnel, PhEye,
    PhPaperPlaneTilt, PhDownloadSimple, PhCheckCircle, PhXCircle,
    PhWarning, PhArrowsClockwise, PhCurrencyEur, PhClock, PhX,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    invoices: Object,
    filters:  Object,
    statuses: Array,
    stats:    Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Filtres ────────────────────────────────────────────────────────
const search  = ref(props.filters?.search  ?? '')
const status  = ref(props.filters?.status  ?? '')
const type    = ref(props.filters?.type    ?? '')
const overdue = ref(props.filters?.overdue ?? false)

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})
watch([status, type, overdue], () => applyFilters())

function applyFilters() {
    router.get(route('admin.factures.index'), {
        search:  search.value  || undefined,
        status:  status.value  || undefined,
        type:    type.value    || undefined,
        overdue: overdue.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    search.value = ''; status.value = ''; type.value = ''; overdue.value = false
    router.get(route('admin.factures.index'))
}

const hasFilters = computed(() => search.value || status.value || type.value || overdue.value)

// ── Format ─────────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

// ── Couleurs statut ────────────────────────────────────────────────
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

const breadcrumbs = [{ label: 'Facturation' }, { label: 'Factures' }]
</script>

<template>
    <Head title="Factures" />

    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Factures</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">Gestion de la facturation</p>
            </div>
            <Link :href="route('admin.factures.create')"
                  class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                <PhPlus :size="17" weight="bold" /> Nouvelle facture
            </Link>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>
        <div v-if="flash?.error" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
            <PhXCircle :size="18" />{{ flash.error }}
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div v-for="(stat, i) in [
                { label: 'En attente',      value: formatPrice(stats.total_due),     icon: PhCurrencyEur, color: '#f59e0b', bg: 'rgba(245,158,11,0.1)' },
                { label: 'En retard',       value: stats.overdue_count + ' facture(s)', icon: PhWarning,    color: '#ef4444', bg: 'rgba(239,68,68,0.1)' },
                { label: 'Payées ce mois',  value: formatPrice(stats.paid_month),    icon: PhCheckCircle, color: '#10b981', bg: 'rgba(16,185,129,0.1)' },
                { label: 'Brouillons',      value: stats.draft_count,                icon: PhClock,       color: '#6b7280', bg: 'rgba(107,114,128,0.1)' },
            ]" :key="i" class="bg-white rounded-2xl p-4 shadow-card">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="{ background: stat.bg }">
                        <component :is="stat.icon" :size="18" :style="{ color: stat.color }" />
                    </div>
                </div>
                <p class="text-xl font-bold font-poppins text-onyx-800">{{ stat.value }}</p>
                <p class="text-xs font-poppins text-onyx-400 mt-0.5">{{ stat.label }}</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-2xl shadow-card p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-48">
                    <PhMagnifyingGlass :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                    <input v-model="search" type="text" placeholder="N° facture, client…"
                           class="w-full pl-9 pr-8 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                    <button v-if="search" @click="search=''" class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-300 hover:text-onyx-600">
                        <PhX :size="14" />
                    </button>
                </div>

                <select v-model="status" class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400">
                    <option value="">Tous les statuts</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>

                <select v-model="type" class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400">
                    <option value="">Tous les types</option>
                    <option value="order">Commande</option>
                    <option value="appointment">Rendez-vous</option>
                    <option value="manual">Manuel</option>
                </select>

                <label class="flex items-center gap-2 cursor-pointer px-3 py-2.5 rounded-xl border border-cream-200 bg-cream-50 hover:border-red-300 transition-colors">
                    <input type="checkbox" v-model="overdue" class="accent-red-500" />
                    <span class="text-sm font-poppins text-onyx-600">En retard</span>
                </label>

                <button v-if="hasFilters" @click="resetFilters"
                        class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-500 hover:text-cognac-600 border border-cream-200 hover:bg-cream-100 transition-colors">
                    <PhArrowsClockwise :size="15" /> Réinitialiser
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-card overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #f5ede4;">
                        <th class="text-left px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Facture</th>
                        <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden md:table-cell">Client</th>
                        <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Statut</th>
                        <th class="text-right px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden lg:table-cell">Total</th>
                        <th class="text-right px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden lg:table-cell">Reste dû</th>
                        <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden xl:table-cell">Échéance</th>
                        <th class="text-right px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-50">
                    <tr v-for="invoice in invoices.data" :key="invoice.id"
                        class="hover:bg-cream-50/50 transition-colors"
                        :class="invoice.is_overdue ? 'bg-red-50/30' : ''">
                        <!-- N° Facture -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                     style="background: rgba(196,149,106,0.1);">
                                    <PhReceipt :size="15" class="text-cognac-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold font-poppins text-onyx-800">
                                        {{ invoice.invoice_number }}
                                    </p>
                                    <p class="text-xs font-poppins text-onyx-400">{{ invoice.issue_date }}</p>
                                </div>
                            </div>
                        </td>
                        <!-- Client -->
                        <td class="px-4 py-3.5 hidden md:table-cell">
                            <p class="text-sm font-poppins text-onyx-700">{{ invoice.client.full_name }}</p>
                        </td>
                        <!-- Statut -->
                        <td class="px-4 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-poppins font-semibold"
                                      :style="{ background: statusStyle(invoice.status).bg, color: statusStyle(invoice.status).color }">
                                    {{ invoice.status_label }}
                                </span>
                                <span v-if="invoice.is_overdue"
                                      class="px-2 py-0.5 rounded text-xs font-poppins font-bold bg-red-100 text-red-600">
                                    En retard
                                </span>
                            </div>
                        </td>
                        <!-- Total -->
                        <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                            <span class="text-sm font-bold font-poppins text-onyx-800">{{ formatPrice(invoice.total) }}</span>
                        </td>
                        <!-- Reste dû -->
                        <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                            <span class="text-sm font-poppins"
                                  :class="invoice.amount_due > 0 ? 'font-bold text-red-600' : 'text-emerald-600 font-semibold'">
                                {{ invoice.amount_due > 0 ? formatPrice(invoice.amount_due) : '✓ Soldé' }}
                            </span>
                        </td>
                        <!-- Échéance -->
                        <td class="px-4 py-3.5 hidden xl:table-cell">
                            <span class="text-sm font-poppins" :class="invoice.is_overdue ? 'text-red-600 font-semibold' : 'text-onyx-500'">
                                {{ invoice.due_date }}
                            </span>
                        </td>
                        <!-- Actions -->
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <Link :href="route('admin.factures.show', invoice.id)"
                                      class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-onyx-700 hover:bg-cream-100 transition-colors">
                                    <PhEye :size="16" />
                                </Link>
                                <a :href="route('admin.factures.pdf', invoice.id)" target="_blank"
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-cognac-600 hover:bg-cognac-50 transition-colors">
                                    <PhDownloadSimple :size="16" />
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty -->
            <div v-if="!invoices.data.length" class="flex flex-col items-center justify-center py-16">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: rgba(196,149,106,0.08);">
                    <PhReceipt :size="32" class="text-onyx-300" />
                </div>
                <p class="text-sm font-poppins font-semibold text-onyx-600">Aucune facture trouvée</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.last_page > 1" class="flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-3.5">
            <p class="text-xs font-poppins text-onyx-500">Page {{ invoices.current_page }} / {{ invoices.last_page }} · {{ invoices.total }} résultats</p>
            <div class="flex items-center gap-1.5">
                <Link v-if="invoices.prev_page_url" :href="invoices.prev_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">← Précédent</Link>
                <Link v-if="invoices.next_page_url" :href="invoices.next_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">Suivant →</Link>
            </div>
        </div>

    </div>
</template>