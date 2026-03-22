<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import {
    PhShoppingBag, PhArrowLeft, PhArrowRight, PhTruck,
} from '@phosphor-icons/vue'

defineOptions({ layout: AccountLayout })

const props = defineProps({
    orders:  Object,
    filters: Object,
})

const status = ref(props.filters?.status ?? '')

function filterByStatus(s) {
    status.value = s
    router.get(route('account.orders'), { status: s || undefined }, { preserveState: true, replace: true })
}

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const statusMap = {
    pending:    { label: 'En attente',  bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
    processing: { label: 'En cours',    bg: 'rgba(99,102,241,0.1)',  color: '#6366f1' },
    shipped:    { label: 'Expédié',     bg: 'rgba(59,130,246,0.1)',  color: '#2563eb' },
    delivered:  { label: 'Livré',       bg: 'rgba(16,185,129,0.1)', color: '#059669' },
    cancelled:  { label: 'Annulé',      bg: 'rgba(239,68,68,0.1)',  color: '#dc2626' },
    refunded:   { label: 'Remboursé',   bg: 'rgba(107,114,128,0.1)',color: '#6b7280' },
}

const filterTabs = [
    { value: '', label: 'Toutes' },
    { value: 'pending', label: 'En attente' },
    { value: 'processing', label: 'En cours' },
    { value: 'shipped', label: 'Expédiées' },
    { value: 'delivered', label: 'Livrées' },
]
</script>

<template>
    <Head title="Mes commandes" />

    <div class="max-w-4xl mx-auto px-4 py-10 space-y-6">

        <div class="flex items-center gap-4">
            <Link :href="route('account.dashboard')"
                  class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                <PhArrowLeft :size="18" />
            </Link>
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Mes commandes</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">Suivez vos achats et téléchargez vos factures</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="flex items-center gap-2 flex-wrap">
            <button v-for="f in filterTabs" :key="f.value"
                    @click="filterByStatus(f.value)"
                    class="px-3.5 py-1.5 rounded-xl text-sm font-poppins font-medium transition-all border"
                    :class="status === f.value
                        ? 'text-white border-transparent shadow-sm'
                        : 'text-onyx-600 border-cream-200 bg-white hover:border-cognac-300'"
                    :style="status === f.value ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                {{ f.label }}
            </button>
        </div>

        <!-- Liste -->
        <div v-if="orders.data?.length" class="space-y-4">
            <div v-for="order in orders.data" :key="order.id"
                 class="bg-white rounded-2xl shadow-card overflow-hidden hover:shadow-float transition-all duration-300">
                <div class="flex items-center justify-between px-5 py-4 border-b border-cream-100">
                    <div>
                        <div class="flex items-center gap-3">
                            <p class="font-poppins font-bold text-onyx-800">{{ order.order_number }}</p>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-poppins font-semibold"
                                  :style="{ background: statusMap[order.status]?.bg, color: statusMap[order.status]?.color }">
                                {{ statusMap[order.status]?.label ?? order.status_label }}
                            </span>
                        </div>
                        <p class="text-xs font-poppins text-onyx-400 mt-0.5">{{ order.date }} · {{ order.items_count }} article(s)</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold font-poppins text-onyx-800">{{ formatPrice(order.total) }}</p>
                        <Link :href="route('account.order', order.id)"
                              class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 transition-colors flex items-center gap-1 justify-end mt-0.5">
                            Voir le détail <PhArrowRight :size="12" />
                        </Link>
                    </div>
                </div>
                <div v-if="order.items_preview?.length" class="px-5 py-3 flex items-center gap-3 flex-wrap">
                    <div v-for="(item, i) in order.items_preview" :key="i" class="flex items-center gap-2">
                        <img v-if="item.thumbnail" :src="item.thumbnail" class="w-10 h-10 rounded-xl object-cover" />
                        <div v-else class="w-10 h-10 rounded-xl bg-cream-100 shrink-0" />
                        <p class="text-xs font-poppins text-onyx-600 truncate max-w-24">{{ item.name }}</p>
                    </div>
                    <p v-if="order.items_count > 2" class="text-xs font-poppins text-onyx-400">
                        + {{ order.items_count - 2 }} autre(s)
                    </p>
                </div>
                <div v-if="order.tracking_number" class="px-5 py-3 border-t border-cream-50">
                    <div class="flex items-center gap-2 text-xs font-poppins">
                        <PhTruck :size="14" class="text-blue-500" />
                        <span class="text-onyx-500">Suivi :</span>
                        <a v-if="order.tracking_url" :href="order.tracking_url" target="_blank"
                           class="text-blue-600 font-semibold hover:underline">
                            {{ order.tracking_number }}
                        </a>
                        <span v-else class="text-onyx-700 font-semibold">{{ order.tracking_number }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl shadow-card">
            <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-5"
                 style="background: rgba(196,149,106,0.08);">
                <PhShoppingBag :size="40" class="text-onyx-300" />
            </div>
            <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucune commande</p>
            <p class="text-sm font-poppins text-onyx-400 mb-5">Vous n'avez pas encore passé de commande</p>
            <Link :href="route('shop.index')"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white"
                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                Voir la boutique <PhArrowRight :size="16" weight="bold" />
            </Link>
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