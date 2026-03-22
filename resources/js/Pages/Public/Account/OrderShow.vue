<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import {
    PhArrowLeft, PhShoppingBag, PhTruck, PhMapPin,
    PhCheckCircle, PhClock, PhPackage, PhReceipt,
    PhArrowSquareOut,
} from '@phosphor-icons/vue'

defineOptions({ layout: AccountLayout })

const props = defineProps({
    order: Object,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const statusSteps = [
    { key: 'pending',    label: 'Commande passée',  icon: PhShoppingBag },
    { key: 'processing', label: 'En préparation',   icon: PhPackage     },
    { key: 'shipped',    label: 'Expédiée',          icon: PhTruck       },
    { key: 'delivered',  label: 'Livrée',            icon: PhCheckCircle },
]

const statusOrder = ['pending', 'processing', 'shipped', 'delivered']

function stepState(stepKey) {
    const currentIdx = statusOrder.indexOf(props.order.status)
    const stepIdx    = statusOrder.indexOf(stepKey)
    if (currentIdx === -1 || props.order.status === 'cancelled') return 'inactive'
    if (stepIdx < currentIdx)  return 'done'
    if (stepIdx === currentIdx) return 'active'
    return 'inactive'
}

onMounted(() => {
    gsap.fromTo('.order-section',
        { opacity: 0, y: 16 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.08, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="`Commande ${order.order_number}`" />

    <div class="space-y-5">

        <!-- Header -->
        <div class="order-section flex items-center gap-4">
            <Link :href="route('account.orders')"
                  class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                <PhArrowLeft :size="17" />
            </Link>
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="font-cormorant text-2xl font-bold text-onyx-800">{{ order.order_number }}</h1>
                    <span class="px-2.5 py-1 rounded-xl text-xs font-poppins font-semibold"
                          :style="{ background: order.status_color + '20', color: order.status_color }">
                        {{ order.status_label }}
                    </span>
                </div>
                <p class="text-xs font-poppins text-onyx-400 mt-0.5">Passée le {{ order.date }}</p>
            </div>
        </div>

        <!-- Statut visuel (stepper) — masqué si annulé -->
        <div v-if="!['cancelled', 'refunded'].includes(order.status)"
             class="order-section bg-white rounded-2xl shadow-card p-5">
            <div class="flex items-center justify-between relative">

                <!-- Ligne de progression -->
                <div class="absolute left-6 right-6 top-5 h-0.5 bg-cream-200 z-0" />
                <div class="absolute left-6 top-5 h-0.5 z-0 transition-all duration-700"
                     style="background: linear-gradient(90deg, #c4956a, #d4af37);"
                     :style="{ width: `${(statusOrder.indexOf(order.status)) / (statusOrder.length - 1) * 88}%` }" />

                <div v-for="step in statusSteps" :key="step.key"
                     class="flex flex-col items-center gap-2 z-10">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                         :class="{
                             'text-white shadow-md': stepState(step.key) !== 'inactive',
                             'bg-cream-100 text-onyx-300': stepState(step.key) === 'inactive',
                         }"
                         :style="stepState(step.key) === 'done'
                             ? 'background: #10b981;'
                             : stepState(step.key) === 'active'
                                 ? 'background: linear-gradient(135deg, #c4956a, #b07d52);'
                                 : ''">
                        <component :is="step.icon" :size="17"
                                   :weight="stepState(step.key) !== 'inactive' ? 'fill' : 'regular'" />
                    </div>
                    <p class="text-xs font-poppins text-center w-20 leading-tight"
                       :class="stepState(step.key) !== 'inactive' ? 'font-semibold text-onyx-700' : 'text-onyx-400'">
                        {{ step.label }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Suivi colis -->
        <div v-if="order.tracking_number"
             class="order-section flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background: rgba(59,130,246,0.1);">
                    <PhTruck :size="17" class="text-blue-500" />
                </div>
                <div>
                    <p class="text-sm font-poppins font-semibold text-onyx-800">Numéro de suivi</p>
                    <p class="text-xs font-poppins text-onyx-400">{{ order.tracking_number }}</p>
                </div>
            </div>
            <a v-if="order.tracking_url" :href="order.tracking_url" target="_blank"
               class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-poppins font-semibold text-blue-600 border border-blue-200 hover:bg-blue-50 transition-colors">
                Suivre <PhArrowSquareOut :size="13" />
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Articles -->
            <div class="order-section bg-white rounded-2xl shadow-card overflow-hidden md:col-span-2">
                <div class="flex items-center gap-2 px-5 py-4 border-b border-cream-100">
                    <PhShoppingBag :size="16" class="text-cognac-500" />
                    <h2 class="text-sm font-poppins font-semibold text-onyx-800">
                        Articles commandés
                    </h2>
                </div>
                <div class="divide-y divide-cream-50">
                    <div v-for="item in order.items" :key="item.product_name"
                         class="flex items-center gap-4 px-5 py-4">
                        <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 bg-cream-100">
                            <img v-if="item.thumbnail" :src="item.thumbnail"
                                 :alt="item.product_name" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <PhShoppingBag :size="20" class="text-onyx-300" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-poppins font-semibold text-onyx-800">
                                {{ item.product_name }}
                            </p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">
                                Réf : {{ item.product_sku ?? '—' }} · {{ formatPrice(item.unit_price) }} / unité
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold font-poppins text-onyx-800">
                                {{ formatPrice(item.total) }}
                            </p>
                            <p class="text-xs font-poppins text-onyx-400">× {{ item.quantity }}</p>
                        </div>
                    </div>
                </div>

                <!-- Totaux -->
                <div class="px-5 py-4 border-t border-cream-100 space-y-2">
                    <div class="flex justify-between text-sm font-poppins">
                        <span class="text-onyx-500">Sous-total</span>
                        <span class="text-onyx-700">{{ formatPrice(order.subtotal) }}</span>
                    </div>
                    <div v-if="order.discount_amount > 0" class="flex justify-between text-sm font-poppins">
                        <span class="text-emerald-600">Réduction</span>
                        <span class="text-emerald-600 font-semibold">-{{ formatPrice(order.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-poppins">
                        <span class="text-onyx-500">Livraison</span>
                        <span :class="order.shipping_amount === 0 ? 'text-emerald-600 font-semibold' : 'text-onyx-700'">
                            {{ order.shipping_amount === 0 ? 'Gratuite' : formatPrice(order.shipping_amount) }}
                        </span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-cream-100">
                        <span class="font-poppins font-bold text-onyx-800">Total</span>
                        <span class="text-xl font-bold font-poppins text-onyx-800">
                            {{ formatPrice(order.total) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Adresse livraison -->
            <div class="order-section bg-white rounded-2xl shadow-card p-5">
                <div class="flex items-center gap-2 mb-3">
                    <PhMapPin :size="16" class="text-cognac-500" />
                    <h2 class="text-sm font-poppins font-semibold text-onyx-800">Adresse de livraison</h2>
                </div>
                <div class="text-sm font-poppins text-onyx-600 space-y-1">
                    <p class="font-semibold text-onyx-800">
                        {{ order.shipping_address?.first_name }} {{ order.shipping_address?.last_name }}
                    </p>
                    <p>{{ order.shipping_address?.address }}</p>
                    <p>{{ order.shipping_address?.postal_code }} {{ order.shipping_address?.city }}</p>
                    <p>{{ order.shipping_address?.country ?? 'France' }}</p>
                    <p v-if="order.shipping_address?.phone" class="text-onyx-400 text-xs">
                        {{ order.shipping_address.phone }}
                    </p>
                </div>
            </div>

            <!-- Dates & infos -->
            <div class="order-section bg-white rounded-2xl shadow-card p-5 space-y-3">
                <div class="flex items-center gap-2 mb-1">
                    <PhClock :size="16" class="text-cognac-500" />
                    <h2 class="text-sm font-poppins font-semibold text-onyx-800">Informations</h2>
                </div>
                <div class="space-y-2 text-sm font-poppins">
                    <div class="flex justify-between">
                        <span class="text-onyx-500">Commande passée</span>
                        <span class="text-onyx-700 font-medium">{{ order.date }}</span>
                    </div>
                    <div v-if="order.paid_at" class="flex justify-between">
                        <span class="text-onyx-500">Payée le</span>
                        <span class="text-emerald-600 font-semibold">{{ order.paid_at }}</span>
                    </div>
                    <div v-if="order.shipped_at" class="flex justify-between">
                        <span class="text-onyx-500">Expédiée le</span>
                        <span class="text-blue-600 font-semibold">{{ order.shipped_at }}</span>
                    </div>
                    <div v-if="order.delivered_at" class="flex justify-between">
                        <span class="text-onyx-500">Livrée le</span>
                        <span class="text-emerald-600 font-semibold">{{ order.delivered_at }}</span>
                    </div>
                </div>

                <!-- Lien facture -->
                <div v-if="order.invoice_id" class="pt-2 border-t border-cream-100">
                    <Link :href="route('account.invoice.pdf', order.invoice_id)"
                          target="_blank"
                          class="flex items-center gap-2 text-sm font-poppins text-cognac-600 hover:text-cognac-800 transition-colors font-medium">
                        <PhReceipt :size="16" />
                        Télécharger la facture
                    </Link>
                </div>
            </div>

            <!-- Notes client -->
            <div v-if="order.client_notes" class="order-section bg-white rounded-2xl shadow-card p-5 md:col-span-2">
                <h2 class="text-sm font-poppins font-semibold text-onyx-800 mb-2">Vos notes</h2>
                <p class="text-sm font-poppins text-onyx-600 leading-relaxed">{{ order.client_notes }}</p>
            </div>
        </div>
    </div>
</template>