<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhShoppingCart, PhTrash, PhMinus, PhPlus, PhTag,
    PhArrowRight, PhPackage, PhX, PhCheckCircle, PhWarning,
    PhArrowLeft, PhTruck,
} from '@phosphor-icons/vue'

const props = defineProps({
    cart:     Object,
    settings: Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Mise à jour quantité ───────────────────────────────────────────
const updatingId = ref(null)

function updateQty(item, delta) {
    const newQty = item.quantity + delta
    if (newQty < 1) return
    if (newQty > 99) return

    updatingId.value = item.id
    router.patch(route('cart.update', item.id), { quantity: newQty }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { updatingId.value = null },
    })
}

function setQty(item, event) {
    const val = parseInt(event.target.value)
    if (!val || val < 1 || val > 99) return
    updatingId.value = item.id
    router.patch(route('cart.update', item.id), { quantity: val }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { updatingId.value = null },
    })
}

// ── Suppression ────────────────────────────────────────────────────
const removingId = ref(null)

function removeItem(item) {
    removingId.value = item.id
    router.delete(route('cart.remove', item.id), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { removingId.value = null },
    })
}

// ── Coupon ─────────────────────────────────────────────────────────
const couponForm = useForm({ code: '' })
const showCoupon = ref(false)

function applyCoupon() {
    couponForm.post(route('cart.coupon'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            couponForm.reset()
            showCoupon.value = false
        },
    })
}

// ── Format ─────────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

// ── Barre de progression livraison gratuite ────────────────────────
const freeShippingProgress = computed(() => {
    if (!props.settings?.free_shipping_at) return 100
    const pct = (props.cart.subtotal / props.settings.free_shipping_at) * 100
    return Math.min(100, pct)
})

const remainingForFreeShipping = computed(() => {
    if (!props.settings?.free_shipping_at) return 0
    return Math.max(0, props.settings.free_shipping_at - props.cart.subtotal)
})

onMounted(() => {
    gsap.fromTo('.cart-item',
        { opacity: 0, x: -20 },
        { opacity: 1, x: 0, duration: 0.4, stagger: 0.07, ease: 'power2.out' }
    )
    gsap.fromTo('.cart-summary',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out', delay: 0.2 }
    )
})
</script>

<template>
    <Head title="Mon panier" />

    <div class="min-h-screen" style="background: #faf7f4;">
        <div class="max-w-6xl mx-auto px-4 py-10 space-y-6">

            <!-- ── Header ────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-cormorant text-4xl font-bold text-onyx-800">Mon panier</h1>
                    <p class="text-sm font-poppins text-onyx-500 mt-1">
                        {{ cart.items_count }} article{{ cart.items_count > 1 ? 's' : '' }}
                    </p>
                </div>
                <Link :href="route('shop.index')"
                      class="flex items-center gap-2 text-sm font-poppins text-onyx-500 hover:text-cognac-600 transition-colors">
                    <PhArrowLeft :size="16" />
                    Continuer mes achats
                </Link>
            </div>

            <!-- ── Flash ─────────────────────────────────────────── -->
            <div v-if="flash?.success"
                 class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
                 style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
                <PhCheckCircle :size="18" />{{ flash.success }}
            </div>
            <div v-if="flash?.error"
                 class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
                 style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
                <PhWarning :size="18" />{{ flash.error }}
            </div>

            <!-- Panier vide -->
            <div v-if="!cart.items?.length"
                 class="flex flex-col items-center justify-center py-24 bg-white rounded-2xl shadow-card">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-5"
                     style="background: rgba(196,149,106,0.08);">
                    <PhShoppingCart :size="40" class="text-onyx-300" />
                </div>
                <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Votre panier est vide</p>
                <p class="text-sm font-poppins text-onyx-400 mb-6">Découvrez nos produits et ajoutez-en à votre panier</p>
                <Link :href="route('shop.index')"
                      class="flex items-center gap-2 px-6 py-3 rounded-xl font-poppins text-sm font-semibold text-white"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhPackage :size="17" />
                    Voir la boutique
                </Link>
            </div>

            <!-- Contenu panier -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ── Articles ───────────────────────────────────── -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Barre livraison gratuite -->
                    <div class="bg-white rounded-2xl shadow-card p-4">
                        <div v-if="remainingForFreeShipping > 0"
                             class="flex items-center gap-3 mb-2">
                            <PhTruck :size="18" class="text-cognac-500 shrink-0" />
                            <p class="text-xs font-poppins text-onyx-600">
                                Plus que <strong class="text-cognac-600">{{ formatPrice(remainingForFreeShipping) }}</strong>
                                pour bénéficier de la livraison gratuite !
                            </p>
                        </div>
                        <div v-else class="flex items-center gap-3 mb-2">
                            <PhCheckCircle :size="18" class="text-emerald-500 shrink-0" />
                            <p class="text-xs font-poppins text-emerald-700 font-semibold">
                                Vous bénéficiez de la livraison gratuite 🎉
                            </p>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background: #f5ede4;">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="background: linear-gradient(90deg, #c4956a, #d4af37);"
                                 :style="{ width: freeShippingProgress + '%' }" />
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                        <div class="cart-item" v-for="(item, i) in cart.items" :key="item.id"
                             :style="i < cart.items.length - 1 ? 'border-bottom: 1px solid #f5ede4;' : ''">
                            <div class="flex items-center gap-4 p-5">

                                <!-- Image -->
                                <Link :href="route('shop.product', item.product.slug)"
                                      class="w-20 h-20 rounded-xl overflow-hidden shrink-0 bg-cream-50">
                                    <img :src="item.product.thumbnail_url" :alt="item.product.name"
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                                </Link>

                                <!-- Infos -->
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('shop.product', item.product.slug)"
                                          class="font-poppins font-semibold text-onyx-800 text-sm hover:text-cognac-600 transition-colors line-clamp-1">
                                        {{ item.product.name }}
                                    </Link>

                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-sm font-bold font-poppins text-onyx-800">
                                            {{ formatPrice(item.product.price) }}
                                        </span>
                                        <span v-if="item.product.is_on_sale"
                                              class="text-xs font-poppins text-onyx-400 line-through">
                                            {{ formatPrice(item.product.compare_price) }}
                                        </span>
                                    </div>

                                    <!-- Stock warning -->
                                    <p v-if="item.product.track_stock && item.product.stock <= 3"
                                       class="text-xs font-poppins text-amber-600 mt-1 flex items-center gap-1">
                                        <PhWarning :size="12" />
                                        Plus que {{ item.product.stock }} en stock
                                    </p>
                                </div>

                                <!-- Quantité -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="updateQty(item, -1)"
                                            :disabled="item.quantity <= 1 || updatingId === item.id"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center border border-cream-200 text-onyx-500 hover:bg-cream-100 disabled:opacity-40 transition-all">
                                        <PhMinus :size="13" />
                                    </button>
                                    <input type="number" :value="item.quantity"
                                           @change="setQty(item, $event)"
                                           min="1" max="99"
                                           class="w-12 h-8 text-center text-sm font-poppins font-semibold text-onyx-800 border border-cream-200 rounded-lg focus:outline-none focus:border-cognac-400" />
                                    <button @click="updateQty(item, +1)"
                                            :disabled="updatingId === item.id"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center border border-cream-200 text-onyx-500 hover:bg-cream-100 disabled:opacity-40 transition-all">
                                        <PhPlus :size="13" />
                                    </button>
                                </div>

                                <!-- Total ligne -->
                                <div class="text-right shrink-0 min-w-16">
                                    <p class="text-sm font-bold font-poppins text-onyx-800">
                                        {{ formatPrice(item.line_total) }}
                                    </p>
                                </div>

                                <!-- Supprimer -->
                                <button @click="removeItem(item)"
                                        :disabled="removingId === item.id"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-300 hover:text-red-500 hover:bg-red-50 transition-all disabled:opacity-40">
                                    <PhTrash :size="16" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Coupon -->
                    <div class="bg-white rounded-2xl shadow-card p-4">
                        <div v-if="cart.coupon" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <PhTag :size="16" class="text-cognac-500" />
                                <span class="text-sm font-poppins font-semibold text-cognac-700">
                                    Code "{{ cart.coupon.code }}" appliqué
                                </span>
                                <span class="text-xs font-poppins text-onyx-400">
                                    — {{ formatPrice(cart.discount_amount) }} de réduction
                                </span>
                            </div>
                        </div>

                        <div v-else>
                            <button v-if="!showCoupon"
                                    @click="showCoupon = true"
                                    class="flex items-center gap-2 text-sm font-poppins text-cognac-600 hover:text-cognac-800 transition-colors">
                                <PhTag :size="16" />
                                J'ai un code promo
                            </button>

                            <div v-else class="flex items-center gap-2">
                                <input v-model="couponForm.code" type="text"
                                       placeholder="CODE PROMO"
                                       @keydown.enter="applyCoupon"
                                       class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins uppercase tracking-wider bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                                <button @click="applyCoupon"
                                        :disabled="couponForm.processing || !couponForm.code"
                                        class="px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60 transition-all hover:opacity-90"
                                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                    Appliquer
                                </button>
                                <button @click="showCoupon = false; couponForm.reset()"
                                        class="w-9 h-9 rounded-xl flex items-center justify-center text-onyx-400 hover:bg-cream-100 transition-colors">
                                    <PhX :size="16" />
                                </button>
                            </div>
                            <p v-if="couponForm.errors.code" class="text-xs text-red-500 font-poppins mt-1">
                                {{ couponForm.errors.code }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ── Récap commande ──────────────────────────────── -->
                <div class="cart-summary space-y-4">
                    <div class="bg-white rounded-2xl shadow-card p-5 space-y-4 sticky top-6">
                        <h2 class="font-poppins font-semibold text-onyx-800">Récapitulatif</h2>

                        <div class="space-y-2.5">
                            <div class="flex justify-between text-sm font-poppins">
                                <span class="text-onyx-500">Sous-total</span>
                                <span class="text-onyx-700 font-medium">{{ formatPrice(cart.subtotal) }}</span>
                            </div>
                            <div v-if="cart.discount_amount > 0" class="flex justify-between text-sm font-poppins">
                                <span class="text-emerald-600">Réduction</span>
                                <span class="text-emerald-600 font-semibold">-{{ formatPrice(cart.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-poppins">
                                <span class="text-onyx-500">Livraison</span>
                                <span class="font-medium" :class="cart.shipping === 0 ? 'text-emerald-600' : 'text-onyx-700'">
                                    {{ cart.shipping === 0 ? 'Gratuite' : formatPrice(cart.shipping) }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-cream-100 flex justify-between">
                            <span class="font-poppins font-bold text-onyx-800">Total</span>
                            <span class="text-xl font-bold font-poppins text-onyx-800">
                                {{ formatPrice(cart.total) }}
                            </span>
                        </div>

                        <Link :href="route('checkout.index')"
                              class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                              style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            Commander
                            <PhArrowRight :size="17" weight="bold" />
                        </Link>

                        <p class="text-xs font-poppins text-onyx-400 text-center flex items-center justify-center gap-1.5">
                            <PhCheckCircle :size="13" class="text-emerald-500" />
                            Paiement 100% sécurisé
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>