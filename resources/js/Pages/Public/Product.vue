<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhShoppingCart, PhArrowLeft, PhArrowRight, PhStar,
    PhStarHalf, PhCheck, PhMinus, PhPlus, PhHeart,
    PhShare, PhPackage, PhShieldCheck, PhArrowsClockwise,
    PhTruck, PhStar as PhStarIcon, PhBridge, PhTag,
    PhQuotes, PhSparkle, PhChatsCircle,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)
defineOptions({ layout: PublicLayout })

const props = defineProps({
    product:  Object,
    reviews:  Array,
    related:  Array,
})

// ── Galerie images ─────────────────────────────────────────────────
const activeImageIdx = ref(0)
const activeImage    = computed(() =>
    props.product.images?.[activeImageIdx.value] ?? null
)

function prevImage() {
    activeImageIdx.value = activeImageIdx.value === 0
        ? props.product.images.length - 1
        : activeImageIdx.value - 1
}
function nextImage() {
    activeImageIdx.value = (activeImageIdx.value + 1) % props.product.images.length
}

// ── Quantité ───────────────────────────────────────────────────────
const quantity = ref(1)
const maxQty   = computed(() =>
    props.product.track_stock ? props.product.stock : 99
)

function decrement() { if (quantity.value > 1) quantity.value-- }
function increment() { if (quantity.value < maxQty.value) quantity.value++ }

// ── Wishlist ───────────────────────────────────────────────────────
const wishlist  = ref(JSON.parse(localStorage.getItem('pb_wishlist') ?? '[]'))
const inWishlist = computed(() => wishlist.value.includes(props.product.id))

function toggleWishlist() {
    const idx = wishlist.value.indexOf(props.product.id)
    if (idx > -1) wishlist.value.splice(idx, 1)
    else wishlist.value.push(props.product.id)
    localStorage.setItem('pb_wishlist', JSON.stringify(wishlist.value))
}

// ── Ajout panier ───────────────────────────────────────────────────
const addingToCart = ref(false)

function addToCart() {
    addingToCart.value = true
    router.post(route('cart.add'), {
        product_id: props.product.id,
        quantity:   quantity.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Animation bouton
            gsap.timeline()
                .to('.add-to-cart-btn', { scale: 0.97, duration: 0.1 })
                .to('.add-to-cart-btn', { scale: 1, duration: 0.3, ease: 'elastic.out(1.5,0.5)' })
        },
        onFinish: () => { addingToCart.value = false },
    })
}

// ── Onglets description ────────────────────────────────────────────
const activeTab = ref('description')
const tabs = computed(() => {
    const t = [{ id: 'description', label: 'Description' }]
    if (props.product.attributes && Object.keys(props.product.attributes).length)
        t.push({ id: 'details', label: 'Caractéristiques' })
    if (props.reviews.length)
        t.push({ id: 'reviews', label: `Avis (${props.reviews.length})` })
    return t
})

// ── Stars helper ──────────────────────────────────────────────────
function renderStars(rating) {
    return Array.from({ length: 5 }, (_, i) => ({
        full:  i + 1 <= Math.floor(rating),
        half:  i + 1 === Math.ceil(rating) && !Number.isInteger(rating),
        empty: i + 1 > Math.ceil(rating),
    }))
}

// ── Animations ─────────────────────────────────────────────────────
onMounted(() => {
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })
    tl.fromTo('.product-gallery', { opacity: 0, x: -30 }, { opacity: 1, x: 0, duration: 0.8 })
      .fromTo('.product-info',    { opacity: 0, x: 30 },  { opacity: 1, x: 0, duration: 0.8 }, '-=0.6')

    gsap.utils.toArray('.related-card').forEach((el, i) => {
        gsap.fromTo(el,
            { opacity: 0, y: 24 },
            { opacity: 1, y: 0, duration: 0.5, delay: i * 0.1, ease: 'power2.out',
              scrollTrigger: { trigger: el, start: 'top 88%', once: true } }
        )
    })
})

const formatPrice = (p) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(p)
</script>

<template>
    <Head :title="`${product.name} — Boutique Patricia Braids`" />

    <!-- Breadcrumb -->
    <div class="py-4 border-b border-cream-200" style="background: white;">
        <div class="container-brand">
            <nav class="flex items-center gap-2 text-sm font-poppins text-onyx-400">
                <Link :href="route('home')" class="hover:text-onyx-600 transition-colors">Accueil</Link>
                <PhArrowRight :size="12" />
                <Link :href="route('shop.index')" class="hover:text-onyx-600 transition-colors">Boutique</Link>
                <PhArrowRight :size="12" />
                <Link v-if="product.category?.slug"
                      :href="route('shop.index', { category: product.category.slug })"
                      class="hover:text-onyx-600 transition-colors">
                    {{ product.category.name }}
                </Link>
                <PhArrowRight v-if="product.category" :size="12" />
                <span class="text-onyx-700 font-medium truncate max-w-48">{{ product.name }}</span>
            </nav>
        </div>
    </div>

    <!-- ── Fiche produit ─────────────────────────────────── -->
    <section class="py-12" style="background: white;">
        <div class="container-brand">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-start">

                <!-- ── Galerie ───────────────────────────── -->
                <div class="product-gallery lg:sticky lg:top-24 space-y-4">

                    <!-- Image principale -->
                    <div class="relative rounded-3xl overflow-hidden group"
                         style="aspect-ratio: 1/1; background: #f9f7f4;">

                        <!-- Image ou placeholder -->
                        <div v-if="activeImage"
                             class="w-full h-full bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                             :style="`background-image: url('${activeImage.url}')`" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <PhPackage :size="80" class="text-cognac-300 opacity-40" />
                        </div>

                        <!-- Badges -->
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <span v-if="product.is_on_sale"
                                  class="px-3 py-1.5 rounded-xl text-sm font-bold font-poppins text-white"
                                  style="background: #e14d6e;">
                                -{{ product.discount_percent }}%
                            </span>
                            <span v-if="product.is_featured"
                                  class="px-3 py-1.5 rounded-xl text-xs font-bold font-poppins flex items-center gap-1.5"
                                  style="background: #d4af37; color: #1a1a2e;">
                                <PhSparkle :size="12" />
                                Populaire
                            </span>
                        </div>

                        <!-- Navigation images -->
                        <div v-if="product.images?.length > 1"
                             class="absolute inset-x-4 top-1/2 -translate-y-1/2 flex items-center justify-between pointer-events-none">
                            <button @click="prevImage"
                                    class="pointer-events-auto w-10 h-10 rounded-xl flex items-center justify-center shadow-float transition-all hover:scale-105"
                                    style="background: rgba(255,255,255,0.92);">
                                <PhArrowLeft :size="18" class="text-onyx-700" />
                            </button>
                            <button @click="nextImage"
                                    class="pointer-events-auto w-10 h-10 rounded-xl flex items-center justify-center shadow-float transition-all hover:scale-105"
                                    style="background: rgba(255,255,255,0.92);">
                                <PhArrowRight :size="18" class="text-onyx-700" />
                            </button>
                        </div>

                        <!-- Bouton wishlist -->
                        <button @click="toggleWishlist"
                                class="absolute top-4 right-4 w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110 shadow-float"
                                :class="inWishlist ? 'text-rose-braids-500' : 'text-onyx-400'"
                                style="background: rgba(255,255,255,0.92);">
                            <PhHeart :size="20" :weight="inWishlist ? 'fill' : 'regular'" />
                        </button>

                        <!-- Dot indicators -->
                        <div v-if="product.images?.length > 1"
                             class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-1.5">
                            <button v-for="(img, i) in product.images" :key="img.id"
                                    @click="activeImageIdx = i"
                                    class="rounded-full transition-all duration-200"
                                    :class="activeImageIdx === i ? 'w-6 h-2' : 'w-2 h-2'"
                                    :style="activeImageIdx === i
                                        ? 'background: #c4956a;'
                                        : 'background: rgba(255,255,255,0.5);'" />
                        </div>
                    </div>

                    <!-- Miniatures -->
                    <div v-if="product.images?.length > 1"
                         class="grid grid-cols-5 gap-2">
                        <button v-for="(img, i) in product.images.slice(0, 5)" :key="img.id"
                                @click="activeImageIdx = i"
                                class="rounded-2xl overflow-hidden transition-all duration-200 border-2"
                                :class="activeImageIdx === i ? 'border-cognac-400' : 'border-transparent hover:border-cream-300'"
                                style="aspect-ratio: 1/1; background: #f9f7f4;">
                            <img :src="img.url" :alt="img.alt"
                                 class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- ── Informations produit ──────────────── -->
                <div class="product-info space-y-6">

                    <!-- Catégorie + Référence -->
                    <div class="flex items-center justify-between">
                        <Link v-if="product.category"
                              :href="route('shop.index', { category: product.category.slug })"
                              class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest font-poppins transition-colors hover:text-cognac-700"
                              style="color: #c4956a;">
                            <PhTag :size="13" />
                            {{ product.category.name }}
                        </Link>
                        <span v-if="product.sku"
                              class="text-xs text-onyx-300 font-poppins">
                            Réf. {{ product.sku }}
                        </span>
                    </div>

                    <!-- Nom -->
                    <div>
                        <h1 class="font-cormorant text-3xl md:text-4xl font-bold text-onyx-800 leading-tight">
                            {{ product.name }}
                        </h1>

                        <!-- Note globale -->
                        <div v-if="product.reviews_count > 0"
                             class="flex items-center gap-3 mt-3">
                            <div class="flex gap-0.5">
                                <template v-for="(star, i) in renderStars(product.avg_rating)" :key="i">
                                    <PhStar v-if="star.full"     :size="16" weight="fill" class="text-gold-400" />
                                    <PhStarHalf v-if="star.half" :size="16" weight="fill" class="text-gold-400" />
                                    <PhStar v-if="star.empty"    :size="16" class="text-cream-300" />
                                </template>
                            </div>
                            <span class="text-sm text-onyx-500 font-poppins">
                                {{ product.avg_rating }}/5 · {{ product.reviews_count }} avis
                            </span>
                            <button @click="activeTab = 'reviews'"
                                    class="text-sm text-cognac-600 hover:text-cognac-800 font-poppins font-medium transition-colors">
                                Lire les avis
                            </button>
                        </div>
                    </div>

                    <!-- Prix -->
                    <div class="flex items-end gap-3 py-4 px-5 rounded-2xl"
                         style="background: #faf7f4; border: 1px solid #edddd0;">
                        <span class="font-cormorant text-4xl font-bold"
                              :class="product.is_on_sale ? 'text-rose-braids-600' : 'text-onyx-800'">
                            {{ formatPrice(product.price) }}
                        </span>
                        <div v-if="product.is_on_sale" class="pb-1">
                            <span class="text-lg text-onyx-400 line-through font-poppins">
                                {{ formatPrice(product.compare_price) }}
                            </span>
                            <span class="ml-2 text-sm font-bold px-2 py-0.5 rounded-lg text-white"
                                  style="background: #e14d6e;">
                                -{{ product.discount_percent }}%
                            </span>
                        </div>
                    </div>

                    <!-- Description courte -->
                    <p v-if="product.short_description"
                       class="text-base text-onyx-500 font-poppins leading-relaxed">
                        {{ product.short_description }}
                    </p>

                    <!-- Tags -->
                    <div v-if="product.tags?.length" class="flex flex-wrap gap-2">
                        <span v-for="tag in product.tags" :key="tag"
                              class="px-3 py-1 rounded-xl text-xs font-poppins font-medium"
                              style="background: rgba(196,149,106,0.1); color: #b07d52;">
                            {{ tag }}
                        </span>
                    </div>

                    <!-- Stock indicator -->
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full"
                             :class="{
                                 'bg-emerald-400': product.is_in_stock && !product.is_low_stock,
                                 'bg-amber-400':   product.is_low_stock,
                                 'bg-red-400':     !product.is_in_stock,
                             }" />
                        <span class="text-sm font-poppins"
                              :class="{
                                  'text-emerald-700': product.is_in_stock && !product.is_low_stock,
                                  'text-amber-700':   product.is_low_stock,
                                  'text-red-600':     !product.is_in_stock,
                              }">
                            <span v-if="product.is_in_stock && !product.is_low_stock">En stock</span>
                            <span v-else-if="product.is_low_stock">Plus que {{ product.stock }} disponible{{ product.stock > 1 ? 's' : '' }}</span>
                            <span v-else>Rupture de stock</span>
                        </span>
                    </div>

                    <!-- Sélecteur quantité + Ajout panier -->
                    <div class="space-y-4">

                        <!-- Quantité -->
                        <div class="flex items-center gap-4">
                            <label class="text-xs font-semibold text-onyx-600 uppercase tracking-widest font-poppins">
                                Quantité
                            </label>
                            <div class="flex items-center gap-1 rounded-xl overflow-hidden border-2 border-cream-200">
                                <button @click="decrement"
                                        :disabled="quantity <= 1"
                                        class="w-10 h-10 flex items-center justify-center transition-all hover:bg-cream-100 disabled:opacity-40">
                                    <PhMinus :size="16" class="text-onyx-600" />
                                </button>
                                <span class="w-12 text-center text-base font-bold font-poppins text-onyx-800">
                                    {{ quantity }}
                                </span>
                                <button @click="increment"
                                        :disabled="quantity >= maxQty"
                                        class="w-10 h-10 flex items-center justify-center transition-all hover:bg-cream-100 disabled:opacity-40">
                                    <PhPlus :size="16" class="text-onyx-600" />
                                </button>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="flex gap-3">
                            <button @click="addToCart"
                                    :disabled="!product.is_in_stock || addingToCart"
                                    class="add-to-cart-btn btn-primary flex-1 justify-center py-4 text-base disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="!addingToCart" class="flex items-center gap-2.5 relative z-10">
                                    <PhShoppingCart :size="20" weight="bold" />
                                    Ajouter au panier
                                </span>
                                <span v-else class="flex items-center gap-2 relative z-10">
                                    <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Ajout...
                                </span>
                            </button>

                            <button @click="toggleWishlist"
                                    class="w-14 rounded-2xl flex items-center justify-center border-2 transition-all hover:scale-105"
                                    :class="inWishlist
                                        ? 'border-rose-braids-300 bg-rose-braids-50'
                                        : 'border-cream-200 bg-white hover:border-cognac-200'"
                                    style="height: 56px;">
                                <PhHeart :size="22"
                                         :class="inWishlist ? 'text-rose-braids-500' : 'text-onyx-400'"
                                         :weight="inWishlist ? 'fill' : 'regular'" />
                            </button>
                        </div>
                    </div>

                    <!-- Rassurance livraison -->
                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="item in [
                            { icon: PhTruck,           text: 'Livraison soignée',   sub: 'Emballage protecteur' },
                            { icon: PhArrowsClockwise, text: 'Retour 14 jours',     sub: 'Satisfait ou remboursé' },
                            { icon: PhShieldCheck,     text: 'Paiement sécurisé',   sub: '100% protégé' },
                            { icon: PhCheck,           text: 'Produit certifié',    sub: 'Qualité professionnelle' },
                        ]" :key="item.text"
                             class="flex items-center gap-2.5 p-3 rounded-xl"
                             style="background: #faf7f4; border: 1px solid #edddd0;">
                            <component :is="item.icon" :size="16" class="text-cognac-500 shrink-0" />
                            <div>
                                <p class="text-xs font-semibold font-poppins text-onyx-700">{{ item.text }}</p>
                                <p class="text-xs text-onyx-400 font-poppins">{{ item.sub }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Onglets ────────────────────────────────────────── -->
    <section class="py-12" style="background: #faf7f4;">
        <div class="container-brand">

            <!-- Tabs nav -->
            <div class="flex items-center gap-1 mb-8 border-b border-cream-200">
                <button v-for="tab in tabs" :key="tab.id"
                        @click="activeTab = tab.id"
                        class="px-6 py-3 text-sm font-semibold font-poppins transition-all relative"
                        :class="activeTab === tab.id
                            ? 'text-cognac-700'
                            : 'text-onyx-400 hover:text-onyx-700'">
                    {{ tab.label }}
                    <div v-if="activeTab === tab.id"
                         class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full"
                         style="background: #c4956a;" />
                </button>
            </div>

            <!-- Tab : Description -->
            <div v-if="activeTab === 'description'"
                 class="max-w-3xl prose prose-sm"
                 style="font-family: 'Poppins', sans-serif; color: #3e3e5e; line-height: 1.8;">
                <div v-if="product.description" v-html="product.description" />
                <div v-else>
                    <p>{{ product.short_description }}</p>
                </div>
            </div>

            <!-- Tab : Caractéristiques -->
            <div v-if="activeTab === 'details'" class="max-w-2xl">
                <div class="bg-white rounded-2xl overflow-hidden shadow-card">
                    <div v-for="(value, key) in product.attributes" :key="key"
                         class="flex items-center justify-between px-6 py-4 border-b border-cream-100 last:border-b-0 hover:bg-cream-50 transition-colors">
                        <span class="text-sm font-semibold text-onyx-600 font-poppins capitalize">{{ key }}</span>
                        <span class="text-sm text-onyx-700 font-poppins">{{ value }}</span>
                    </div>
                    <div v-if="product.weight"
                         class="flex items-center justify-between px-6 py-4 border-b border-cream-100 hover:bg-cream-50">
                        <span class="text-sm font-semibold text-onyx-600 font-poppins">Poids</span>
                        <span class="text-sm text-onyx-700 font-poppins">{{ product.weight }} kg</span>
                    </div>
                    <div v-if="product.sku"
                         class="flex items-center justify-between px-6 py-4 hover:bg-cream-50">
                        <span class="text-sm font-semibold text-onyx-600 font-poppins">Référence</span>
                        <span class="text-sm text-onyx-700 font-poppins font-mono">{{ product.sku }}</span>
                    </div>
                </div>
            </div>

            <!-- Tab : Avis -->
            <div v-if="activeTab === 'reviews'" class="max-w-3xl space-y-5">

                <!-- Résumé note -->
                <div v-if="reviews.length"
                     class="flex items-center gap-8 p-6 bg-white rounded-2xl shadow-card">
                    <div class="text-center shrink-0">
                        <p class="font-cormorant text-6xl font-bold text-onyx-800">{{ product.avg_rating }}</p>
                        <div class="flex justify-center gap-0.5 my-1.5">
                            <template v-for="(star, i) in renderStars(product.avg_rating)" :key="i">
                                <PhStar v-if="star.full"     :size="18" weight="fill" class="text-gold-400" />
                                <PhStarHalf v-if="star.half" :size="18" weight="fill" class="text-gold-400" />
                                <PhStar v-if="star.empty"    :size="18" class="text-cream-200" />
                            </template>
                        </div>
                        <p class="text-xs text-onyx-400 font-poppins">{{ reviews.length }} avis</p>
                    </div>

                    <!-- Barres par note -->
                    <div class="flex-1 space-y-1.5">
                        <div v-for="n in [5,4,3,2,1]" :key="n" class="flex items-center gap-3">
                            <div class="flex items-center gap-0.5 w-14 justify-end">
                                <span class="text-xs text-onyx-500 font-poppins">{{ n }}</span>
                                <PhStar :size="11" weight="fill" class="text-gold-400" />
                            </div>
                            <div class="flex-1 h-2 rounded-full overflow-hidden" style="background: #f5ede4;">
                                <div class="h-full rounded-full transition-all duration-700"
                                     style="background: #d4af37;"
                                     :style="{
                                         width: reviews.length
                                             ? (reviews.filter(r => r.rating === n).length / reviews.length * 100) + '%'
                                             : '0%'
                                     }" />
                            </div>
                            <span class="text-xs text-onyx-400 font-poppins w-6">
                                {{ reviews.filter(r => r.rating === n).length }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Liste avis -->
                <div v-for="review in reviews" :key="review.id"
                     class="bg-white rounded-2xl p-6 shadow-card">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div class="flex items-center gap-3">
                            <img :src="review.client.avatar_url" :alt="review.client.name"
                                 class="w-10 h-10 rounded-full object-cover" />
                            <div>
                                <p class="text-sm font-semibold font-poppins text-onyx-800">
                                    {{ review.client.name }}
                                </p>
                                <p class="text-xs text-onyx-400 font-poppins">{{ review.date }}</p>
                            </div>
                        </div>
                        <div class="flex gap-0.5 shrink-0">
                            <template v-for="(star, i) in renderStars(review.rating)" :key="i">
                                <PhStar v-if="star.full"     :size="14" weight="fill" class="text-gold-400" />
                                <PhStarHalf v-if="star.half" :size="14" weight="fill" class="text-gold-400" />
                                <PhStar v-if="star.empty"    :size="14" class="text-cream-200" />
                            </template>
                        </div>
                    </div>

                    <p v-if="review.title" class="font-semibold text-sm font-poppins text-onyx-800 mb-1.5">
                        {{ review.title }}
                    </p>
                    <p class="text-sm text-onyx-500 font-poppins leading-relaxed">{{ review.comment }}</p>

                    <!-- Réponse admin -->
                    <div v-if="review.has_reply"
                         class="mt-4 p-4 rounded-xl"
                         style="background: rgba(196,149,106,0.07); border-left: 3px solid #c4956a;">
                        <div class="flex items-center gap-2 mb-2">
                            <PhChatsCircle :size="14" class="text-cognac-500" />
                            <span class="text-xs font-semibold font-poppins text-cognac-700">Réponse de Patricia</span>
                        </div>
                        <p class="text-sm text-onyx-600 font-poppins leading-relaxed">{{ review.admin_reply }}</p>
                    </div>
                </div>

                <!-- Pas d'avis -->
                <div v-if="!reviews.length" class="text-center py-12">
                    <PhStarIcon :size="40" class="text-onyx-200 mx-auto mb-3" />
                    <p class="text-onyx-400 font-poppins text-sm">Aucun avis pour le moment.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Produits similaires ───────────────────────────── -->
    <section v-if="related.length" class="py-16" style="background: white;">
        <div class="container-brand">
            <div class="flex items-center justify-between mb-8">
                <h2 class="font-cormorant text-3xl font-bold text-onyx-800">
                    Vous pourriez aussi aimer
                </h2>
                <Link :href="route('shop.index', { category: product.category?.slug })"
                      class="text-sm font-poppins text-cognac-600 hover:text-cognac-800 font-medium transition-colors flex items-center gap-1.5">
                    Voir plus
                    <PhArrowRight :size="14" />
                </Link>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <div v-for="p in related" :key="p.id"
                     class="related-card group bg-cream-50 rounded-2xl overflow-hidden hover:shadow-float transition-all duration-400 hover:-translate-y-1">
                    <Link :href="route('shop.product', p.slug)">
                        <div class="relative overflow-hidden" style="aspect-ratio: 1/1;">
                            <div class="w-full h-full flex items-center justify-center transition-transform duration-500 group-hover:scale-105"
                                 style="background: linear-gradient(135deg, #f5ede4, #faf7f4);">
                                <img v-if="p.thumbnail_url && !p.thumbnail_url.includes('placeholder')"
                                     :src="p.thumbnail_url" :alt="p.name"
                                     class="w-full h-full object-cover" />
                                <PhPackage v-else :size="36" class="text-cognac-300 opacity-40" />
                            </div>
                            <span v-if="p.is_on_sale"
                                  class="absolute top-3 left-3 px-2 py-1 rounded-lg text-xs font-bold text-white"
                                  style="background: #e14d6e;">
                                Promo
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-semibold font-poppins text-onyx-800 line-clamp-2 leading-snug group-hover:text-cognac-600 transition-colors">
                                {{ p.name }}
                            </p>
                            <div class="flex items-baseline gap-2 mt-2">
                                <span class="font-cormorant text-lg font-bold text-onyx-800">
                                    {{ formatPrice(p.price) }}
                                </span>
                                <span v-if="p.is_on_sale"
                                      class="text-xs text-onyx-400 line-through font-poppins">
                                    {{ formatPrice(p.compare_price) }}
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>