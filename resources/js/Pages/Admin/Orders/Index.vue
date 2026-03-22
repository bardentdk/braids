<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhShoppingBag, PhMagnifyingGlass, PhX, PhArrowsClockwise,
    PhCheckCircle, PhEye, PhPackage, PhTruck, PhReceipt,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    orders:  Object,
    filters: Object,
    stats:   Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

let t = null
watch(search, () => { clearTimeout(t); t = setTimeout(applyFilters, 380) })
watch(status, applyFilters)

function applyFilters() {
    router.get(route('admin.commandes.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    search.value = ''; status.value = ''
    router.get(route('admin.commandes.index'))
}

const hasFilters = computed(() => search.value || status.value)

function updateStatus(order, newStatus) {
    router.patch(route('admin.commandes.status', order.id), { status: newStatus }, { preserveScroll: true })
}

function generateInvoice(order) {
    router.post(route('admin.commandes.invoice', order.id), {}, { preserveScroll: true })
}

const statusMap = {
    pending:    { label: 'En attente',    bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
    processing: { label: 'En traitement', bg: 'rgba(99,102,241,0.1)',  color: '#6366f1' },
    shipped:    { label: 'Expédiée',      bg: 'rgba(59,130,246,0.1)',  color: '#3b82f6' },
    delivered:  { label: 'Livrée',        bg: 'rgba(16,185,129,0.1)',  color: '#059669' },
    cancelled:  { label: 'Annulée',       bg: 'rgba(239,68,68,0.1)',   color: '#dc2626' },
    refunded:   { label: 'Remboursée',    bg: 'rgba(107,114,128,0.1)', color: '#6b7280' },
}

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}
</script>

<template>
    <Head title="Commandes" />
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Commandes</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">{{ orders.total }} commande(s) au total</p>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div v-for="s in [
                { label: 'Total',       value: stats?.total     ?? 0, color: '#6b7280' },
                { label: 'En attente',  value: stats?.pending   ?? 0, color: '#d97706' },
                { label: 'Expédiées',   value: stats?.shipped   ?? 0, color: '#3b82f6' },
                { label: 'CA ce mois',  value: formatPrice(stats?.revenue_month ?? 0), color: '#059669' },
            ]" :key="s.label" class="bg-white rounded-2xl shadow-card p-4">
                <p class="text-2xl font-bold font-poppins text-onyx-800">{{ s.value }}</p>
                <p class="text-xs font-poppins mt-0.5" :style="{ color: s.color }">{{ s.label }}</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-2xl shadow-card p-4 flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-48">
                <PhMagnifyingGlass :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                <input v-model="search" type="text" placeholder="N° commande, client…"
                       class="w-full pl-9 pr-8 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                <button v-if="search" @click="search=''" class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-300 hover:text-onyx-600">
                    <PhX :size="14" />
                </button>
            </div>
            <select v-model="status" class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400">
                <option value="">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="processing">En traitement</option>
                <option value="shipped">Expédiées</option>
                <option value="delivered">Livrées</option>
                <option value="cancelled">Annulées</option>
            </select>
            <button v-if="hasFilters" @click="resetFilters"
                    class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-500 hover:text-cognac-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                <PhArrowsClockwise :size="15" /> Réinitialiser
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #f5ede4; background: #faf7f4;">
                            <th class="text-left px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Commande</th>
                            <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden md:table-cell">Client</th>
                            <th class="text-right px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden lg:table-cell">Total</th>
                            <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Statut</th>
                            <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden xl:table-cell">Date</th>
                            <th class="text-right px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-50">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-cream-50/40 transition-colors">
                            <td class="px-5 py-4">
                                <p class="text-sm font-poppins font-bold text-onyx-800">{{ order.order_number }}</p>
                                <p class="text-xs font-poppins text-onyx-400">{{ order.items_count }} article(s)</p>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                <p class="text-sm font-poppins font-semibold text-onyx-700">{{ order.client_name }}</p>
                                <p class="text-xs font-poppins text-onyx-400">{{ order.client_email }}</p>
                            </td>
                            <td class="px-4 py-4 text-right hidden lg:table-cell">
                                <p class="text-sm font-poppins font-bold text-onyx-800">{{ formatPrice(order.total) }}</p>
                                <p class="text-xs font-poppins" :style="order.payment_status === 'paid' ? 'color: #059669' : 'color: #d97706'">
                                    {{ order.payment_status === 'paid' ? '✓ Payée' : 'En attente' }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <select :value="order.status"
                                        @change="updateStatus(order, $event.target.value)"
                                        class="px-2 py-1 rounded-xl text-xs font-poppins font-semibold border-0 cursor-pointer focus:outline-none"
                                        :style="{ background: statusMap[order.status]?.bg, color: statusMap[order.status]?.color }">
                                    <option v-for="(s, k) in statusMap" :key="k" :value="k">{{ s.label }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-4 hidden xl:table-cell">
                                <p class="text-xs font-poppins text-onyx-500">{{ order.date }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="route('admin.commandes.show', order.id)"
                                          class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-onyx-700 hover:bg-cream-100 transition-colors">
                                        <PhEye :size="15" />
                                    </Link>
                                    <button v-if="!order.invoice_id" @click="generateInvoice(order)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-cognac-600 hover:bg-cognac-50 transition-colors"
                                            title="Générer la facture">
                                        <PhReceipt :size="15" />
                                    </button>
                                    <Link v-else :href="route('admin.factures.show', order.invoice_id)"
                                          class="w-8 h-8 rounded-lg flex items-center justify-center text-cognac-500 hover:text-cognac-700 hover:bg-cognac-50 transition-colors"
                                          title="Voir la facture">
                                        <PhReceipt :size="15" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!orders.data?.length" class="flex flex-col items-center justify-center py-16">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: rgba(196,149,106,0.08);">
                    <PhShoppingBag :size="32" class="text-onyx-300" />
                </div>
                <p class="text-sm font-poppins font-semibold text-onyx-600 mb-1">Aucune commande</p>
                <p class="text-xs font-poppins text-onyx-400">Les commandes apparaîtront ici</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="orders.last_page > 1" class="flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-3.5">
            <p class="text-xs font-poppins text-onyx-500">Page {{ orders.current_page }} / {{ orders.last_page }}</p>
            <div class="flex gap-1.5">
                <Link v-if="orders.prev_page_url" :href="orders.prev_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">← Précédent</Link>
                <Link v-if="orders.next_page_url" :href="orders.next_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">Suivant →</Link>
            </div>
        </div>
    </div>
</template>