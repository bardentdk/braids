<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhPencilSimple, PhTrash, PhToggleRight, PhToggleLeft,
    PhStar, PhPackage, PhCurrencyEur, PhTag, PhWarning, PhCheckCircle,
    PhShoppingBag, PhEye, PhCalendarBlank, PhPercent, PhArrowSquareOut,
    PhImage, PhX,
} from '@phosphor-icons/vue'
import { markRaw } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    product: Object,
})

const breadcrumbs = [
    { label: 'Boutique' },
    { label: 'Produits', route: 'admin.produits.index' },
    { label: props.product.name },
]

// ── Galerie images ─────────────────────────────────────────────────
const activeImage = ref(props.product.thumbnail_url)
const lightboxOpen = ref(false)
const lightboxSrc  = ref(null)

function setActiveImage(src) { activeImage.value = src }
function openLightbox(src) { lightboxSrc.value = src; lightboxOpen.value = true }
function closeLightbox() { lightboxOpen.value = false }

// ── Actions ────────────────────────────────────────────────────────
const toggling = ref(false)

function toggleProduct() {
    toggling.value = true
    router.post(route('admin.produits.toggle', props.product.id), {}, {
        preserveState: false,
        onFinish: () => { toggling.value = false },
    })
}

function deleteProduct() {
    if (!confirm(`Supprimer définitivement "${props.product.name}" ?`)) return
    router.delete(route('admin.produits.destroy', props.product.id))
}

// ── Formatage ──────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

function formatNumber(val) {
    return new Intl.NumberFormat('fr-FR').format(val ?? 0)
}

// ── Stars rating ───────────────────────────────────────────────────
const avgRating = computed(() => props.product.avg_rating ?? 0)

onMounted(() => {
    gsap.fromTo('.product-card',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.06, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="product.name" />

    <!-- Lightbox -->
    <Transition name="fade">
        <div v-if="lightboxOpen"
             class="fixed inset-0 z-50 flex items-center justify-center"
             style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);"
             @click.self="closeLightbox">
            <button @click="closeLightbox"
                    class="absolute top-5 right-5 w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                <PhX :size="20" />
            </button>
            <img :src="lightboxSrc" class="max-w-3xl max-h-screen rounded-2xl object-contain p-6" />
        </div>
    </Transition>

    <div class="p-6 space-y-6">

        <!-- ── Header ──────────────────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.produits.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 text-onyx-500 hover:text-onyx-800 hover:bg-cream-100 transition-colors bg-white shadow-card">
                    <PhArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="font-cormorant text-3xl font-bold text-onyx-800">{{ product.name }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span v-if="product.sku" class="text-xs font-poppins text-onyx-400">
                            SKU : {{ product.sku }}
                        </span>
                        <span v-if="product.category?.name"
                              class="flex items-center gap-1 text-xs font-poppins px-2 py-0.5 rounded-lg"
                              style="background: rgba(196,149,106,0.1); color: #b07d52;">
                            <PhTag :size="11" />
                            {{ product.category.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button @click="toggleProduct" :disabled="toggling"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border transition-all"
                        :class="product.is_active
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'">
                    <component :is="product.is_active ? markRaw(PhToggleRight) : markRaw(PhToggleLeft)" :size="18" />
                    {{ product.is_active ? 'Actif' : 'Inactif' }}
                </button>

                <Link :href="route('admin.produits.edit', product.id)"
                      class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white hover:bg-cream-50 text-onyx-700 transition-colors shadow-card">
                    <PhPencilSimple :size="16" />
                    Modifier
                </Link>

                <button @click="deleteProduct"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-red-200 bg-white hover:bg-red-50 text-red-600 transition-colors">
                    <PhTrash :size="16" />
                </button>
            </div>
        </div>

        <!-- ── Contenu principal ────────────────────────────────────── -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Colonne gauche : Médias + Description -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Galerie -->
                <div class="product-card bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="p-5">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                            <PhImage :size="16" class="text-cognac-500" />
                            Médias
                        </h2>

                        <!-- Image principale -->
                        <div class="relative aspect-video rounded-xl overflow-hidden bg-cream-50 cursor-pointer group mb-3"
                             @click="openLightbox(activeImage)">
                            <img :src="activeImage" :alt="product.name"
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500" />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md">
                                    <PhEye :size="18" class="text-onyx-700" />
                                </div>
                            </div>
                        </div>

                        <!-- Thumbnails -->
                        <div v-if="product.images?.length" class="flex gap-2 flex-wrap">
                            <!-- Thumbnail principale -->
                            <button @click="setActiveImage(product.thumbnail_url)"
                                    class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all"
                                    :class="activeImage === product.thumbnail_url
                                        ? 'border-cognac-400 scale-105'
                                        : 'border-transparent hover:border-cream-300'">
                                <img :src="product.thumbnail_url" class="w-full h-full object-cover" />
                            </button>
                            <!-- Images supplémentaires -->
                            <button v-for="img in product.images" :key="img.id"
                                    @click="setActiveImage(img.url)"
                                    class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all"
                                    :class="activeImage === img.url
                                        ? 'border-cognac-400 scale-105'
                                        : 'border-transparent hover:border-cream-300'">
                                <img :src="img.url" class="w-full h-full object-cover" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="product-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4">Description</h2>

                    <div v-if="product.short_description"
                         class="px-4 py-3 rounded-xl mb-4 text-sm font-poppins text-onyx-700 leading-relaxed"
                         style="background: rgba(196,149,106,0.06); border-left: 3px solid #c4956a;">
                        {{ product.short_description }}
                    </div>

                    <div v-if="product.description"
                         class="text-sm font-poppins text-onyx-600 leading-relaxed whitespace-pre-wrap">
                        {{ product.description }}
                    </div>

                    <p v-if="!product.short_description && !product.description"
                       class="text-sm font-poppins text-onyx-400 italic">
                        Aucune description renseignée
                    </p>
                </div>

                <!-- Avis clients -->
                <div v-if="product.reviews?.length"
                     class="product-card bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4"
                         style="border-bottom: 1px solid #f5ede4;">
                        <div class="flex items-center gap-3">
                            <PhStar :size="16" weight="fill" class="text-amber-400" />
                            <h2 class="font-poppins font-semibold text-onyx-800 text-sm">
                                Avis clients
                            </h2>
                            <span class="text-xs font-poppins px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-semibold">
                                {{ avgRating?.toFixed(1) }}/5
                            </span>
                        </div>
                        <span class="text-xs font-poppins text-onyx-400">
                            {{ product.reviews.length }} avis
                        </span>
                    </div>
                    <div class="divide-y divide-cream-50">
                        <div v-for="review in product.reviews" :key="review.id"
                             class="px-5 py-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="text-sm font-poppins font-semibold text-onyx-700">
                                        {{ review.client.name }}
                                    </p>
                                    <div class="flex items-center gap-0.5 mt-0.5">
                                        <PhStar v-for="i in 5" :key="i"
                                                :size="13" :weight="i <= review.rating ? 'fill' : 'regular'"
                                                :class="i <= review.rating ? 'text-amber-400' : 'text-onyx-200'" />
                                    </div>
                                </div>
                                <span class="text-xs font-poppins text-onyx-400">{{ review.date }}</span>
                            </div>
                            <p v-if="review.title" class="text-sm font-poppins font-semibold text-onyx-700 mb-1">
                                {{ review.title }}
                            </p>
                            <p class="text-sm font-poppins text-onyx-500 leading-relaxed">
                                {{ review.comment }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne droite : Infos & Stats -->
            <div class="space-y-5">

                <!-- Prix -->
                <div class="product-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhCurrencyEur :size="16" class="text-cognac-500" />
                        Tarification
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Prix de vente</span>
                            <span class="text-xl font-bold font-poppins text-onyx-800">
                                {{ formatPrice(product.price) }}
                            </span>
                        </div>
                        <div v-if="product.compare_price" class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Prix barré</span>
                            <span class="text-sm font-poppins text-onyx-400 line-through">
                                {{ formatPrice(product.compare_price) }}
                            </span>
                        </div>
                        <div v-if="product.cost_price" class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Prix d'achat</span>
                            <span class="text-sm font-poppins text-onyx-600">
                                {{ formatPrice(product.cost_price) }}
                            </span>
                        </div>
                        <div v-if="product.margin" class="flex items-center justify-between pt-2 border-t border-cream-100">
                            <span class="text-xs font-poppins text-onyx-500 flex items-center gap-1">
                                <PhPercent :size="12" />
                                Marge
                            </span>
                            <span class="text-sm font-bold font-poppins"
                                  :class="product.margin >= 50 ? 'text-emerald-600' : product.margin >= 20 ? 'text-amber-600' : 'text-red-600'">
                                {{ product.margin }}%
                            </span>
                        </div>
                        <div v-if="product.is_on_sale"
                             class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-poppins font-semibold text-white"
                             style="background: linear-gradient(135deg, #e14d6e, #c43060);">
                            <PhPercent :size="14" />
                            Promotion active — {{ product.discount_percent }}% de réduction
                        </div>
                    </div>
                </div>

                <!-- Stock -->
                <div class="product-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhPackage :size="16" class="text-cognac-500" />
                        Stock
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Quantité disponible</span>
                            <span class="text-2xl font-bold font-poppins"
                                  :class="product.stock > 10 ? 'text-emerald-600' : product.stock > 0 ? 'text-amber-600' : 'text-red-600'">
                                {{ formatNumber(product.stock) }}
                            </span>
                        </div>

                        <!-- Barre stock -->
                        <div class="h-2 rounded-full overflow-hidden bg-cream-100">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :style="{
                                    width: Math.min(100, (product.stock / Math.max(product.low_stock_alert * 2, 1)) * 100) + '%',
                                    background: product.stock > 10 ? '#10b981' : product.stock > 0 ? '#f59e0b' : '#ef4444'
                                 }" />
                        </div>

                        <div class="flex items-center justify-between text-xs font-poppins text-onyx-400">
                            <span>Seuil d'alerte : {{ product.low_stock_alert }}</span>
                            <span class="flex items-center gap-1"
                                  :class="product.is_in_stock ? 'text-emerald-600' : 'text-red-500'">
                                <component :is="product.is_in_stock ? markRaw(PhCheckCircle) : markRaw(PhWarning)" :size="13" />
                                {{ product.is_in_stock ? 'En stock' : 'Rupture' }}
                            </span>
                        </div>

                        <div class="pt-2 border-t border-cream-100 space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-poppins">
                                <span class="text-onyx-400">Suivi du stock</span>
                                <span :class="product.track_stock ? 'text-emerald-600' : 'text-onyx-400'">
                                    {{ product.track_stock ? '✓ Activé' : '✗ Désactivé' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-poppins">
                                <span class="text-onyx-400">Commandes en rupture</span>
                                <span :class="product.allow_backorder ? 'text-amber-600' : 'text-onyx-400'">
                                    {{ product.allow_backorder ? '✓ Autorisé' : '✗ Bloqué' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats ventes -->
                <div class="product-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhShoppingBag :size="16" class="text-cognac-500" />
                        Performances
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">{{ formatNumber(product.sales_count) }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">Ventes</p>
                        </div>
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">{{ formatNumber(product.views_count) }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">Vues</p>
                        </div>
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-amber-500">{{ avgRating?.toFixed(1) ?? '—' }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">Note moy.</p>
                        </div>
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">{{ product.reviews?.length ?? 0 }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">Avis</p>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div v-if="product.tags?.length" class="product-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-3 flex items-center gap-2">
                        <PhTag :size="16" class="text-cognac-500" />
                        Tags
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="tag in product.tags" :key="tag"
                              class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-poppins font-medium text-white"
                              style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhTag :size="11" />
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <!-- Dimensions & poids -->
                <div v-if="product.weight || product.dimensions"
                     class="product-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-3">Expédition</h2>
                    <div class="space-y-2 text-xs font-poppins">
                        <div v-if="product.weight" class="flex justify-between">
                            <span class="text-onyx-400">Poids</span>
                            <span class="text-onyx-700 font-semibold">{{ product.weight }} kg</span>
                        </div>
                        <div v-if="product.dimensions?.length" class="flex justify-between">
                            <span class="text-onyx-400">Dimensions (L×l×H)</span>
                            <span class="text-onyx-700 font-semibold">
                                {{ product.dimensions.length }}×{{ product.dimensions.width }}×{{ product.dimensions.height }} cm
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Voir sur la vitrine -->
                <a :href="`/boutique/${product.slug}`" target="_blank"
                   class="product-card flex items-center justify-center gap-2 w-full py-3 rounded-2xl border border-cream-200 bg-white text-sm font-poppins font-medium text-onyx-600 hover:text-cognac-600 hover:border-cognac-300 hover:bg-cognac-50 transition-all shadow-card">
                    <PhArrowSquareOut :size="16" />
                    Voir sur la vitrine
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>