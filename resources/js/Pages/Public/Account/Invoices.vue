<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import {
    PhReceipt, PhArrowLeft, PhDownloadSimple,
    PhCheckCircle, PhWarning, PhClock,
} from '@phosphor-icons/vue'

defineOptions({ layout: AccountLayout })

const props = defineProps({
    invoices: Array,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const statusMap = {
    paid:    { label: 'Payée',     bg: 'rgba(16,185,129,0.1)',  color: '#059669', icon: PhCheckCircle },
    sent:    { label: 'Envoyée',   bg: 'rgba(59,130,246,0.1)',  color: '#2563eb', icon: PhClock },
    overdue: { label: 'En retard', bg: 'rgba(239,68,68,0.1)',   color: '#dc2626', icon: PhWarning },
    draft:   { label: 'Brouillon', bg: 'rgba(107,114,128,0.1)', color: '#6b7280', icon: PhClock },
}

const typeLabels = { order: 'Commande', appointment: 'Rendez-vous', manual: 'Manuel' }
</script>

<template>
    <Head title="Mes factures" />

    <div class="max-w-3xl mx-auto px-4 py-10 space-y-6">

        <div class="flex items-center gap-4">
            <Link :href="route('account.dashboard')"
                  class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                <PhArrowLeft :size="18" />
            </Link>
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Mes factures</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">Téléchargez vos factures en PDF</p>
            </div>
        </div>

        <!-- Liste -->
        <div v-if="invoices.length" class="bg-white rounded-2xl shadow-card overflow-hidden">
            <div class="divide-y divide-cream-50">
                <div v-for="invoice in invoices" :key="invoice.id"
                     class="flex items-center gap-4 px-5 py-4 hover:bg-cream-50/50 transition-colors"
                     :class="invoice.is_overdue ? 'bg-red-50/30' : ''">

                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                         :style="{ background: statusMap[invoice.status]?.bg ?? 'rgba(196,149,106,0.1)' }">
                        <component :is="statusMap[invoice.status]?.icon ?? PhReceipt" :size="20"
                                   :style="{ color: statusMap[invoice.status]?.color ?? '#c4956a' }" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-poppins font-bold text-onyx-800">{{ invoice.invoice_number }}</p>
                            <span class="px-2 py-0.5 rounded-lg text-xs font-poppins font-semibold"
                                  :style="{ background: statusMap[invoice.status]?.bg, color: statusMap[invoice.status]?.color }">
                                {{ statusMap[invoice.status]?.label ?? invoice.status_label }}
                            </span>
                        </div>
                        <p class="text-xs font-poppins text-onyx-400 mt-0.5">
                            {{ typeLabels[invoice.type] ?? invoice.type }} · {{ invoice.issue_date }}
                            <span v-if="invoice.is_overdue" class="text-red-500 font-semibold"> · En retard</span>
                        </p>
                    </div>

                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold font-poppins text-onyx-800">{{ formatPrice(invoice.total) }}</p>
                        <p v-if="invoice.amount_due > 0" class="text-xs font-poppins text-red-500 font-semibold">
                            {{ formatPrice(invoice.amount_due) }} dû
                        </p>
                    </div>

                    <a :href="invoice.pdf_url" target="_blank"
                       class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white text-onyx-500 hover:text-cognac-600 hover:border-cognac-300 hover:bg-cognac-50 transition-all shadow-sm shrink-0">
                        <PhDownloadSimple :size="16" />
                    </a>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl shadow-card">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background: rgba(196,149,106,0.08);">
                <PhReceipt :size="32" class="text-onyx-300" />
            </div>
            <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucune facture</p>
            <p class="text-sm font-poppins text-onyx-400">Vos factures apparaîtront ici après vos achats</p>
        </div>

    </div>
</template>