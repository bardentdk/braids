<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhShoppingBag, PhArrowRight, PhFunnel, PhMagnifyingGlass,
    PhStar, PhPackage, PhTag, PhSortAscending, PhX,
    PhArrowsDownUp, PhSparkle, PhCheck, PhShoppingCart,
    PhHeart, PhCaretDown, PhArrowLeft, PhArrowRight as PhArrowRightIcon,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)
defineOptions({ layout: PublicLayout })

const props = defineProps({
    products:   Object,
    categories: Array,
    filters:    Object,
    settings:   Object,
})

// ── Filtres locaux ─────────────────────────────────────────────────
const search   = ref(props.filters?.search ?? '')
const category = ref(props.filters?.category ?? '')
const sort     = ref(props.filters?.sort ?? 'latest')
const featured = ref(props.filters?.featured ?? false)

let searchTimer = null

const sortOptions = [
    { value: 'latest',     label: 'Plus récents' },
    { value: 'popular',    label: 'Populaires' },
    { value: 'price_asc',  label: 'Prix croissant' },
    { value: 'price_desc', label: 'Prix décroissant' },
]

const activeFiltersCount = computed(() => {
    let count = 0
    if (category.value) count++
    if (search.value)   count++
    if (featured.value) count++
    return count
})

function applyFilters() {
    router.get(route('shop.index'), {
        search:   search.value   || undefined,
        category: category.value || undefined,
        sort:     sort.value !== 'latest' ? sort.value : undefined,
        featured: featured.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value   = ''
    category.value = ''
    sort.value     = 'latest'
    featured.value = false
    applyFilters()
}

watch(sort,     () => applyFilters())
watch(category, () => applyFilters())
watch(featured, () => applyFilters())
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 400)
})

// ── Wishlist locale ────────────────────────────────────────────────
const wishlist = ref(JSON.parse(localStorage.getItem('pb_wishlist') ?? '[]'))

function toggleWishlist(id) {
    const idx = wishlist.value.indexOf(id)
    if (idx > -1) wishlist.value.splice(idx, 1)
    else wishlist.value.push(id)
    localStorage.setItem('pb_wishlist', JSON.stringify(wishlist.value))
}

// ── Ajout panier ───────────────────────────────────────────────────
function addToCart(product) {
    router.post(route('cart.add'), {
        product_id: product.id,
        quantity:   1,
    }, { preserveScroll: true })
}

// ── Animations ─────────────────────────────────────────────────────
onMounted(() => {
    gsap.fromTo('.shop-hero-content',
        { opacity: 0, y: 40 },
        { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out' }
    )
    gsap.fromTo('.filter-bar',
        { opacity: 0, y: -10 },
        { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out', delay: 0.3 }
    )

    gsap.utils.toArray('.product-card').forEach((el, i) => {
        gsap.fromTo(el,
            { opacity: 0, y: 30, scale: 0.97 },
            {
                opacity: 1, y: 0, scale: 1,
                duration: 0.55,
                delay:    (i % 4) * 0.07,
                ease:     'power3.out',
                scrollTrigger: { trigger: el, start: 'top 90%', once: true },
            }
        )
    })
})

const formatPrice = (p) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(p)
</script>

<template>
    <Head title="Boutique — Produits & Extensions" />

    <!-- ── Hero ─────────────────────────────────────────── -->
    <section class="relative py-20 overflow-hidden"
             style="background: linear-gradient(135deg, #0d0d1a 0%, #1a1a2e 60%, #0f0f20 100%);">

        <div class="absolute inset-0 opacity-[0.12]"
             style="background:
                radial-gradient(circle at 15% 50%, #c4956a 0%, transparent 45%),
                radial-gradient(circle at 85% 30%, #d4af37 0%, transparent 45%);" />

        <!-- Grille déco -->
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image:
                linear-gradient(rgba(196,149,106,1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(196,149,106,1) 1px, transparent 1px);
                background-size: 56px 56px;" />

        <div class="container-brand relative z-10">
            <div class="shop-hero-content grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest font-poppins"
                         style="background: rgba(196,149,106,0.15); color: #c4956a;">
                        <PhShoppingBag :size="14" />
                        Boutique officielle
                    </div>
                    <h1 class="font-cormorant text-5xl md:text-6xl font-bold text-white leading-tight">
                        Des produits sélectionnés<br>
                        <span class="text-gradient-gold">avec soin</span>
                    </h1>
                    <p class="text-lg text-white/60 font-poppins leading-relaxed max-w-md">
                        Extensions premium, soins capillaires professionnels et accessoires soigneusement choisis pour sublimer vos tresses.
                    </p>
                    <div class="flex items-center gap-6">
                        <div v-for="info in [
                            { value: `${settings.free_shipping_at}€+`, label: 'Livraison offerte' },
                            { value: products.total,                  label: 'Produits disponibles' },
                            { value: '24h',                           label: 'Délai expédition' },
                        ]" :key="info.label" class="text-center">
                            <p class="font-cormorant text-2xl font-bold text-white">{{ info.value }}</p>
                            <p class="text-xs text-white/40 font-poppins">{{ info.label }}</p>
                        </div>
                    </div>
                </div>

                <!-- Floating products preview -->
                <div class="hidden lg:flex items-center justify-center gap-4 relative h-52">
                    <div class="float-product-1 glass-dark rounded-2xl p-4 shadow-luxury w-44"
                         style="animation: float 4s ease-in-out infinite;">
                        <div class="w-full h-24 rounded-xl flex items-center justify-center mb-3"
                             style="background: rgba(196,149,106,0.1);">
                            <PhPackage :size="32" class="text-cognac-400 opacity-60" />
                        </div>
                        <p class="text-xs font-semibold text-white font-poppins truncate">Extensions Kanekalon</p>
                        <p class="text-cognac-400 text-sm font-bold font-poppins mt-0.5">24,90€</p>
                    </div>
                    <div class="glass-dark rounded-2xl p-4 shadow-luxury w-44 mt-8"
                         style="animation: float 3.5s ease-in-out infinite 0.5s;">
                        <div class="w-full h-24 rounded-xl flex items-center justify-center mb-3"
                             style="background: rgba(212,175,55,0.1);">
                            <PhSparkle :size="32" class="text-gold-400 opacity-60" />
                        </div>
                        <p class="text-xs font-semibold text-white font-poppins truncate">Huile de Beurre de Karité</p>
                        <p class="text-cognac-400 text-sm font-bold font-poppins mt-0.5">18,00€</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Barre de filtres ──────────────────────────────── -->
    <div class="filter-bar sticky top-[72px] z-20 py-4"
         style="background: rgba(250,247,244,0.97); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(26,26,46,0.08);">
        <div class="container-brand">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4">

                <!-- Recherche -->
                <div class="relative flex-1 max-w-xs">
                    <PhMagnifyingGlass :size="16"
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                    <input v-model="search"
                           type="text"
                           placeholder="Rechercher un produit..."
                           class="input-brand pl-10 py-2.5 text-sm" />
                    <button v-if="search" @click="search = ''; applyFilters()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600">
                        <PhX :size="14" />
                    </button>
                </div>

                <!-- Catégories -->
                <div class="flex items-center gap-2 overflow-x-auto pb-0">
                    <button @click="category = ''; applyFilters()"
                            class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium font-poppins transition-all"
                            :class="!category ? 'text-white shadow-float' : 'text-onyx-500 bg-cream-200'"
                            :style="!category ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                        Tous
                    </button>
                    <button v-for="cat in categories" :key="cat.id"
                            @click="category = category === cat.slug ? '' : cat.slug"
                            class="shrink-0 flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium font-poppins transition-all border"
                            :class="category === cat.slug
                                ? 'text-white border-transparent shadow-float'
                                : 'text-onyx-500 border-transparent bg-cream-200 hover:border-cream-300'"
                            :style="category === cat.slug
                                ? `background: ${cat.color ?? 'linear-gradient(135deg, #c4956a, #b07d52)'};`
                                : ''">
                        <span class="w-2 h-2 rounded-full shrink-0"
                              :style="`background: ${cat.color}`" />
                        {{ cat.name }}
                        <span class="text-xs opacity-70">({{ cat.products_count }})</span>
                    </button>
                </div>

                <!-- Tri + Infos -->
                <div class="flex items-center gap-3 ml-auto shrink-0">

                    <!-- Featured toggle -->
                    <button @click="featured = !featured"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium font-poppins transition-all border"
                            :class="featured
                                ? 'text-white border-transparent shadow-float'
                                : 'text-onyx-500 border-cream-300 bg-white'"
                            :style="featured ? 'background: linear-gradient(135deg, #d4af37, #c4956a);' : ''">
                        <PhSparkle :size="14" :class="featured ? 'text-white' : 'text-gold-400'" />
                        Populaires
                    </button>

                    <!-- Tri -->
                    <div class="relative">
                        <select v-model="sort"
                                class="appearance-none bg-white border border-cream-300 rounded-xl text-sm font-poppins text-onyx-700 pl-9 pr-8 py-2 cursor-pointer outline-none transition-all hover:border-cognac-300 focus:border-cognac-400">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <PhArrowsDownUp :size="14"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                        <PhCaretDown :size="11"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                    </div>

                    <!-- Clear filters -->
                    <button v-if="activeFiltersCount > 0"
                            @click="clearFilters"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium font-poppins text-red-500 bg-red-50 hover:bg-red-100 transition-all">
                        <PhX :size="14" />
                        Effacer ({{ activeFiltersCount }})
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Grille produits ───────────────────────────────── -->
    <section class="py-12" style="background: #faf7f4;">
        <div class="container-brand">

            <!-- Compteur résultats -->
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-onyx-400 font-poppins">
                    <span class="font-semibold text-onyx-700">{{ products.total }}</span>
                    produit{{ products.total > 1 ? 's' : '' }} trouvé{{ products.total > 1 ? 's' : '' }}
                </p>
                <p v-if="settings.free_shipping_at"
                   class="text-xs text-onyx-400 font-poppins flex items-center gap-1.5">
                    <PhCheck :size="13" class="text-cognac-400" />
                    Livraison offerte dès {{ formatPrice(settings.free_shipping_at) }}
                </p>
            </div>

            <!-- Grille -->
            <div v-if="products.data?.length"
                 class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">

                <div v-for="product in products.data" :key="product.id"
                     class="product-card group bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-float transition-all duration-500 hover:-translate-y-1.5 flex flex-col">

                    <!-- Image -->
                    <div class="relative overflow-hidden" style="aspect-ratio: 1/1;">
                        <Link :href="route('shop.product', product.slug)">
                            <div class="w-full h-full transition-transform duration-700 group-hover:scale-108 flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #f5ede4, #faf7f4);">
                                <img v-if="product.thumbnail_url && !product.thumbnail_url.includes('placeholder')"
                                     :src="product.thumbnail_url"
                                     :alt="product.name"
                                     class="w-full h-full object-cover" />
                                <PhPackage v-else :size="52" class="text-cognac-300 opacity-50" />
                            </div>
                        </Link>

                        <!-- Badges overlay -->
                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            <span v-if="product.is_on_sale"
                                  class="px-2.5 py-1 rounded-xl text-xs font-bold font-poppins text-white"
                                  style="background: #e14d6e;">
                                -{{ product.discount_percent }}%
                            </span>
                            <span v-if="product.is_featured"
                                  class="px-2.5 py-1 rounded-xl text-xs font-bold font-poppins flex items-center gap-1"
                                  style="background: #d4af37; color: #1a1a2e;">
                                <PhSparkle :size="10" />
                                Top
                            </span>
                        </div>

                        <!-- Stock badge -->
                        <div v-if="!product.is_in_stock"
                             class="absolute inset-0 flex items-center justify-center"
                             style="background: rgba(13,13,26,0.55);">
                            <span class="px-4 py-2 rounded-xl text-sm font-semibold text-white font-poppins"
                                  style="background: rgba(26,26,46,0.9);">
                                Rupture de stock
                            </span>
                        </div>

                        <!-- Actions hover -->
                        <div class="absolute inset-0 flex items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-all duration-300"
                             :class="!product.is_in_stock ? 'hidden' : ''">
                            <div class="flex items-center gap-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                <button @click.prevent="addToCart(product)"
                                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold font-poppins text-white shadow-float transition-all hover:scale-105"
                                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                    <PhShoppingCart :size="16" weight="bold" />
                                    Ajouter
                                </button>
                            </div>
                        </div>

                        <!-- Wishlist -->
                        <button @click.prevent="toggleWishlist(product.id)"
                                class="absolute top-3 right-3 w-8 h-8 rounded-xl flex items-center justify-center transition-all hover:scale-110 shadow-float"
                                :class="wishlist.includes(product.id)
                                    ? 'text-rose-braids-500'
                                    : 'text-onyx-400'"
                                style="background: rgba(255,255,255,0.9);">
                            <PhHeart :size="16"
                                     :weight="wishlist.includes(product.id) ? 'fill' : 'regular'" />
                        </button>
                    </div>

                    <!-- Contenu -->
                    <div class="flex flex-col flex-1 p-4">
                        <!-- Catégorie -->
                        <p class="text-xs text-onyx-400 font-poppins mb-1.5 truncate">
                            {{ product.category?.name }}
                        </p>

                        <!-- Nom -->
                        <Link :href="route('shop.product', product.slug)"
                              class="font-semibold text-sm font-poppins text-onyx-800 hover:text-cognac-600 transition-colors line-clamp-2 leading-snug mb-auto">
                            {{ product.name }}
                        </Link>

                        <!-- Prix -->
                        <div class="flex items-end justify-between mt-3 pt-3 border-t border-cream-100">
                            <div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-lg font-bold font-cormorant"
                                          :class="product.is_on_sale ? 'text-rose-braids-600' : 'text-onyx-800'">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                    <span v-if="product.is_on_sale"
                                          class="text-xs text-onyx-400 line-through font-poppins">
                                        {{ formatPrice(product.compare_price) }}
                                    </span>
                                </div>
                                <!-- Stock faible -->
                                <p v-if="product.is_low_stock && product.stock"
                                   class="text-xs text-amber-600 font-poppins mt-0.5">
                                    Plus que {{ product.stock }} en stock
                                </p>
                            </div>

                            <!-- Bouton ajout rapide -->
                            <button v-if="product.is_in_stock"
                                    @click="addToCart(product)"
                                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-all hover:scale-110 shadow-card"
                                    style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                <PhShoppingCart :size="16" weight="bold" class="text-white" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-else class="py-24 text-center">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6"
                     style="background: rgba(196,149,106,0.1);">
                    <PhPackage :size="40" class="text-cognac-300" />
                </div>
                <h2 class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucun produit trouvé</h2>
                <p class="text-sm text-onyx-400 font-poppins mb-6">Essayez avec d'autres filtres.</p>
                <button @click="clearFilters" class="btn-secondary py-3 px-6">
                    <PhX :size="16" />
                    Effacer les filtres
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="products.last_page > 1"
                 class="flex items-center justify-center gap-2 mt-12">
                <Link v-if="products.prev_page_url" :href="products.prev_page_url"
                      class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-card hover:shadow-float transition-all hover:-translate-x-0.5">
                    <PhArrowLeft :size="18" class="text-onyx-600" />
                </Link>

                <div class="flex items-center gap-1">
                    <template v-for="page in products.links.slice(1, -1)" :key="page.label">
                        <Link v-if="page.url" :href="page.url"
                              class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-semibold font-poppins transition-all"
                              :class="page.active
                                  ? 'text-white shadow-float'
                                  : 'bg-white text-onyx-600 shadow-card hover:shadow-float'"
                              :style="page.active ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                            {{ page.label }}
                        </Link>
                        <span v-else class="w-10 h-10 flex items-center justify-center text-onyx-300 text-sm font-poppins">
                            ...
                        </span>
                    </template>
                </div>

                <Link v-if="products.next_page_url" :href="products.next_page_url"
                      class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-card hover:shadow-float transition-all hover:translate-x-0.5">
                    <PhArrowRightIcon :size="18" class="text-onyx-600" />
                </Link>
            </div>
        </div>
    </section>

    <!-- ── Bannière livraison ─────────────────────────────── -->
    <section class="py-10" style="background: #0d0d1a;">
        <div class="container-brand">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="item in [
                    { icon: PhPackage,  title: 'Livraison soignée',   sub: 'Emballage protecteur' },
                    { icon: PhCheck,    title: 'Produits certifiés',  sub: 'Qualité professionnelle' },
                    { icon: PhSparkle,  title: 'Sélection exclusive', sub: 'Choix expertisé' },
                    { icon: PhArrowRight, title: 'Retour facile',     sub: '14 jours pour changer' },
                ]" :key="item.title"
                     class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background: rgba(196,149,106,0.12);">
                        <component :is="item.icon" :size="20" class="text-cognac-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white font-poppins">{{ item.title }}</p>
                        <p class="text-xs text-white/40 font-poppins">{{ item.sub }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>