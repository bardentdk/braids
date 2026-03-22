<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhArrowLeft, PhLock, PhTruck, PhCreditCard,
    PhCheckCircle, PhWarning, PhSpinnerGap, PhTag,
} from '@phosphor-icons/vue'

const props = defineProps({
    cart:             Object,
    prefill:          Object,
    payment_methods:  Array,
    settings:         Object,
})

const form = useForm({
    first_name:  props.prefill?.first_name  ?? '',
    last_name:   props.prefill?.last_name   ?? '',
    email:       props.prefill?.email       ?? '',
    phone:       props.prefill?.phone       ?? '',
    address:     props.prefill?.address     ?? '',
    city:        props.prefill?.city        ?? '',
    postal_code: props.prefill?.postal_code ?? '',
    country:     props.prefill?.country     ?? 'France',
    notes:       '',
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

function submit() {
    form.post(route('checkout.store'))
}

onMounted(() => {
    gsap.fromTo('.checkout-section',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.08, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head title="Finaliser ma commande" />

    <div class="min-h-screen" style="background: #faf7f4;">
        <div class="max-w-5xl mx-auto px-4 py-10">

            <!-- ── Header ────────────────────────────────────────── -->
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('cart.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                    <PhArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="font-cormorant text-4xl font-bold text-onyx-800">Finaliser ma commande</h1>
                    <p class="text-sm font-poppins text-onyx-500 mt-0.5 flex items-center gap-1.5">
                        <PhLock :size="14" class="text-emerald-500" />
                        Paiement sécurisé
                    </p>
                </div>
            </div>

            <!-- ── Erreurs ────────────────────────────────────────── -->
            <div v-if="form.hasErrors"
                 class="checkout-section flex items-start gap-3 px-4 py-3 rounded-xl mb-6 text-sm font-poppins"
                 style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
                <PhWarning :size="18" class="text-red-500 shrink-0 mt-0.5" />
                <div>
                    <p class="font-semibold text-red-700 mb-1">Veuillez corriger les erreurs :</p>
                    <ul class="list-disc list-inside text-red-600 text-xs space-y-0.5">
                        <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- ── Formulaire ─────────────────────────────── -->
                    <div class="lg:col-span-2 space-y-5">

                        <!-- Infos personnelles -->
                        <div class="checkout-section bg-white rounded-2xl shadow-card p-6 space-y-4">
                            <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                                Vos informations
                            </h2>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                        Prénom <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.first_name" type="text"
                                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                           :class="form.errors.first_name ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                    <p v-if="form.errors.first_name" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.first_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                        Nom <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.last_name" type="text"
                                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                           :class="form.errors.last_name ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                    <p v-if="form.errors.last_name" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.last_name }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                        Email <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.email" type="email"
                                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                           :class="form.errors.email ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                    <p v-if="form.errors.email" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                        Téléphone <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.phone" type="tel"
                                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                           :class="form.errors.phone ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                    <p v-if="form.errors.phone" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.phone }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Adresse livraison -->
                        <div class="checkout-section bg-white rounded-2xl shadow-card p-6 space-y-4">
                            <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                                <PhTruck :size="18" class="text-cognac-500" />
                                Adresse de livraison
                            </h2>

                            <div>
                                <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                    Adresse <span class="text-red-400">*</span>
                                </label>
                                <input v-model="form.address" type="text" placeholder="12 rue des Tresses"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="form.errors.address ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                <p v-if="form.errors.address" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.address }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                        Code postal <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.postal_code" type="text"
                                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                           :class="form.errors.postal_code ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                    <p v-if="form.errors.postal_code" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.postal_code }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                        Ville <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.city" type="text"
                                           class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                           :class="form.errors.city ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                    <p v-if="form.errors.city" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.city }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                    Pays
                                </label>
                                <input v-model="form.country" type="text"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="checkout-section bg-white rounded-2xl shadow-card p-6">
                            <h2 class="font-poppins font-semibold text-onyx-800 mb-4">
                                Notes (optionnel)
                            </h2>
                            <textarea v-model="form.notes" rows="3"
                                      placeholder="Instructions particulières pour votre commande..."
                                      class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-none" />
                        </div>
                    </div>

                    <!-- ── Récap ───────────────────────────────────── -->
                    <div class="checkout-section space-y-4">

                        <!-- Récap commande -->
                        <div class="bg-white rounded-2xl shadow-card p-5 space-y-4 sticky top-6">
                            <h2 class="font-poppins font-semibold text-onyx-800">Votre commande</h2>

                            <!-- Items -->
                            <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                                <div v-for="item in cart.items" :key="item.id"
                                     class="flex items-center gap-3">
                                    <img :src="item.product.thumbnail_url" :alt="item.product.name"
                                         class="w-12 h-12 rounded-xl object-cover shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-poppins font-semibold text-onyx-700 line-clamp-1">
                                            {{ item.product.name }}
                                        </p>
                                        <p class="text-xs font-poppins text-onyx-400">
                                            × {{ item.quantity }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-bold font-poppins text-onyx-800 shrink-0">
                                        {{ formatPrice(item.line_total) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Coupon -->
                            <div v-if="cart.coupon"
                                 class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-poppins"
                                 style="background: rgba(196,149,106,0.08); border: 1px solid rgba(196,149,106,0.2);">
                                <PhTag :size="13" class="text-cognac-500" />
                                <span class="text-cognac-700 font-semibold">{{ cart.coupon.code }}</span>
                                <span class="text-onyx-400 ml-auto">-{{ formatPrice(cart.discount_amount) }}</span>
                            </div>

                            <!-- Totaux -->
                            <div class="space-y-2 pt-3 border-t border-cream-100">
                                <div class="flex justify-between text-sm font-poppins">
                                    <span class="text-onyx-500">Sous-total</span>
                                    <span class="text-onyx-700">{{ formatPrice(cart.subtotal) }}</span>
                                </div>
                                <div v-if="cart.discount_amount > 0" class="flex justify-between text-sm font-poppins">
                                    <span class="text-emerald-600">Réduction</span>
                                    <span class="text-emerald-600 font-semibold">-{{ formatPrice(cart.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm font-poppins">
                                    <span class="text-onyx-500">Livraison</span>
                                    <span :class="cart.shipping === 0 ? 'text-emerald-600 font-semibold' : 'text-onyx-700'">
                                        {{ cart.shipping === 0 ? 'Gratuite' : formatPrice(cart.shipping) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm font-poppins">
                                    <span class="text-onyx-500">TVA ({{ cart.tax_rate }}%)</span>
                                    <span class="text-onyx-700">{{ formatPrice(cart.tax_amount) }}</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-cream-100">
                                    <span class="font-poppins font-bold text-onyx-800">Total</span>
                                    <span class="text-xl font-bold font-poppins text-onyx-800">
                                        {{ formatPrice(cart.total) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Bouton passer commande -->
                            <button type="submit" :disabled="form.processing"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 disabled:opacity-60 disabled:translate-y-0 shadow-md"
                                    style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                <PhSpinnerGap v-if="form.processing" :size="18" class="animate-spin" />
                                <PhLock v-else :size="18" />
                                {{ form.processing ? 'Traitement…' : 'Confirmer et payer' }}
                            </button>

                            <p class="text-xs font-poppins text-onyx-400 text-center flex items-center justify-center gap-1.5">
                                <PhCheckCircle :size="13" class="text-emerald-500" />
                                Paiement sécurisé SSL
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>