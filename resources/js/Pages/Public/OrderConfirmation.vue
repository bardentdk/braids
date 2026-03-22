<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhCheckCircle, PhShoppingBag, PhTruck, PhEnvelope,
    PhArrowRight, PhMapPin,
} from '@phosphor-icons/vue'

const props = defineProps({
    order: Object,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

onMounted(() => {
    gsap.fromTo('.confirm-check',
        { scale: 0, opacity: 0 },
        { scale: 1, opacity: 1, duration: 0.6, ease: 'back.out(1.7)', delay: 0.2 }
    )
    gsap.fromTo('.confirm-card',
        { opacity: 0, y: 24 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.1, ease: 'power2.out', delay: 0.5 }
    )
})
</script>

<template>
    <Head title="Commande confirmée" />

    <div class="min-h-screen" style="background: #faf7f4;">
        <div class="max-w-2xl mx-auto px-4 py-16 space-y-6">

            <!-- ── Succès ──────────────────────────────────────────── -->
            <div class="confirm-check flex justify-center">
                <div class="w-24 h-24 rounded-full flex items-center justify-center shadow-xl"
                     style="background: linear-gradient(135deg, #10b981, #059669);">
                    <PhCheckCircle :size="52" weight="fill" class="text-white" />
                </div>
            </div>

            <div class="confirm-card text-center space-y-1">
                <h1 class="font-cormorant text-4xl font-bold text-onyx-800">Merci pour votre commande !</h1>
                <p class="text-sm font-poppins text-onyx-500">
                    Votre commande <strong class="text-onyx-700">#{{ order.order_number }}</strong>
                    a bien été enregistrée.
                </p>
                <p class="text-xs font-poppins text-onyx-400">{{ order.created_at }}</p>
            </div>

            <!-- Email confirmation -->
            <div class="confirm-card flex items-center gap-3 px-5 py-4 rounded-2xl text-sm font-poppins"
                 style="background: rgba(196,149,106,0.08); border: 1px solid rgba(196,149,106,0.2);">
                <PhEnvelope :size="20" class="text-cognac-500 shrink-0" />
                <p class="text-onyx-700">
                    Un email de confirmation a été envoyé à
                    <strong>{{ order.client.email }}</strong>
                </p>
            </div>

            <!-- Récap articles -->
            <div class="confirm-card bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-cream-100">
                    <PhShoppingBag :size="18" class="text-cognac-500" />
                    <h2 class="font-poppins font-semibold text-onyx-800">Détail de la commande</h2>
                </div>

                <div class="divide-y divide-cream-50">
                    <div v-for="item in order.items" :key="item.product_name"
                         class="flex items-center gap-4 px-5 py-4">
                        <img v-if="item.thumbnail" :src="item.thumbnail"
                             class="w-12 h-12 rounded-xl object-cover shrink-0" />
                        <div v-else class="w-12 h-12 rounded-xl bg-cream-100 shrink-0" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-poppins font-semibold text-onyx-700">{{ item.product_name }}</p>
                            <p class="text-xs font-poppins text-onyx-400">× {{ item.quantity }}</p>
                        </div>
                        <span class="text-sm font-bold font-poppins text-onyx-800">
                            {{ formatPrice(item.total) }}
                        </span>
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
                        <span :class="order.shipping_amount === 0 ? 'text-emerald-600' : 'text-onyx-700'">
                            {{ order.shipping_amount === 0 ? 'Gratuite' : formatPrice(order.shipping_amount) }}
                        </span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-cream-100">
                        <span class="font-poppins font-bold text-onyx-800">Total payé</span>
                        <span class="text-xl font-bold font-poppins text-onyx-800">
                            {{ formatPrice(order.total) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Adresse livraison -->
            <div class="confirm-card bg-white rounded-2xl shadow-card p-5">
                <div class="flex items-center gap-3 mb-3">
                    <PhTruck :size="18" class="text-cognac-500" />
                    <h2 class="font-poppins font-semibold text-onyx-800">Adresse de livraison</h2>
                </div>
                <div class="text-sm font-poppins text-onyx-600 space-y-0.5">
                    <p class="font-semibold text-onyx-800">
                        {{ order.shipping_address?.first_name }} {{ order.shipping_address?.last_name }}
                    </p>
                    <p>{{ order.shipping_address?.address }}</p>
                    <p>{{ order.shipping_address?.postal_code }} {{ order.shipping_address?.city }}</p>
                    <p>{{ order.shipping_address?.country }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="confirm-card flex flex-col gap-3">
                <Link :href="route('shop.index')"
                      class="flex items-center justify-center gap-2 py-3.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    Continuer mes achats
                    <PhArrowRight :size="17" weight="bold" />
                </Link>
                <Link :href="route('home')"
                      class="flex items-center justify-center py-3 rounded-xl font-poppins text-sm font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                    Retour à l'accueil
                </Link>
            </div>

        </div>
    </div>
</template>