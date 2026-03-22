<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhPackage, PhPlus, PhMagnifyingGlass, PhFunnel, PhGridFour,
    PhListDashes, PhPencilSimple, PhTrash, PhEye, PhToggleLeft,
    PhToggleRight, PhStar, PhWarning, PhArrowUp, PhArrowDown,
    PhCheckCircle, PhXCircle, PhDotsThreeVertical, PhX,
    PhArrowsClockwise, PhTag,
} from '@phosphor-icons/vue'
import { markRaw } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    products:   Object,
    filters:    Object,
    categories: Array,
    stats:      Object,
})

// ── Vue grid/list ──────────────────────────────────────────────────
const viewMode = ref('grid')

// ── Filtres ────────────────────────────────────────────────────────
const search      = ref(props.filters?.search ?? '')
const categoryId  = ref(props.filters?.category_id ?? '')
const status      = ref(props.filters?.status ?? '')
const featured    = ref(props.filters?.featured ?? false)

let searchTimer = null
watch(search, (val) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})

watch([categoryId, status, featured], () => applyFilters())

function applyFilters() {
    router.get(route('admin.produits.index'), {
        search:      search.value || undefined,
        category_id: categoryId.value || undefined,
        status:      status.value || undefined,
        featured:    featured.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    search.value     = ''
    categoryId.value = ''
    status.value     = ''
    featured.value   = false
    router.get(route('admin.produits.index'))
}

const hasFilters = computed(() =>
    search.value || categoryId.value || status.value || featured.value
)

// ── Actions ────────────────────────────────────────────────────────
const deletingId  = ref(null)
const togglingId  = ref(null)

function toggleProduct(product) {
    togglingId.value = product.id
    router.post(route('admin.produits.toggle', product.id), {}, {
        preserveState: true,
        onFinish: () => { togglingId.value = null },
    })
}

function deleteProduct(product) {
    if (!confirm(`Supprimer "${product.name}" ? Cette action est irréversible.`)) return
    deletingId.value = product.id
    router.delete(route('admin.produits.destroy', product.id), {
        onFinish: () => { deletingId.value = null },
    })
}

// ── Formatage ──────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const breadcrumbs = [{ label: 'Boutique' }, { label: 'Produits' }]

// ── Dropdown action ouvert ─────────────────────────────────────────
const openDropdown = ref(null)
function toggleDropdown(id) {
    openDropdown.value = openDropdown.value === id ? null : id
}
function closeDropdowns() { openDropdown.value = null }

// Flash
const page = usePage()
const flash = computed(() => page.props.flash)
</script>

<template>
    <Head title="Produits" />

    <div class="p-6 space-y-6" @click="closeDropdowns">

        <!-- ── Header ──────────────────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Produits</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">
                    Gérez votre catalogue de produits
                </p>
            </div>
            <Link :href="route('admin.produits.create')"
                  class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                <PhPlus :size="17" weight="bold" />
                Nouveau produit
            </Link>
        </div>

        <!-- ── Flash ───────────────────────────────────────────────── -->
        <div v-if="flash?.success"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />
            {{ flash.success }}
        </div>
        <div v-if="flash?.error"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
            <PhXCircle :size="18" />
            {{ flash.error }}
        </div>

        <!-- ── Stats ───────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div v-for="(stat, key) in [
                { label: 'Total produits',  value: stats.total,        color: '#6366f1', bg: 'rgba(99,102,241,0.08)'  },
                { label: 'Actifs',          value: stats.active,       color: '#10b981', bg: 'rgba(16,185,129,0.08)'  },
                { label: 'Stock faible',    value: stats.low_stock,    color: '#f59e0b', bg: 'rgba(245,158,11,0.08)'  },
                { label: 'Rupture',         value: stats.out_of_stock, color: '#ef4444', bg: 'rgba(239,68,68,0.08)'   },
            ]" :key="key"
                 class="bg-white rounded-2xl p-4 shadow-card">
                <p class="text-2xl font-bold font-poppins text-onyx-800">{{ stat.value }}</p>
                <p class="text-xs font-poppins text-onyx-400 mt-1">{{ stat.label }}</p>
                <div class="mt-3 h-1 rounded-full" :style="{ background: stat.bg }">
                    <div class="h-full rounded-full" :style="{ background: stat.color, width: '60%' }" />
                </div>
            </div>
        </div>

        <!-- ── Barre de filtres ────────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-card p-4">
            <div class="flex flex-wrap items-center gap-3">

                <!-- Recherche -->
                <div class="relative flex-1 min-w-52">
                    <PhMagnifyingGlass :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                    <input v-model="search" type="text" placeholder="Nom, SKU, description..."
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400 transition-colors" />
                    <button v-if="search" @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-300 hover:text-onyx-600">
                        <PhX :size="14" />
                    </button>
                </div>

                <!-- Catégorie -->
                <select v-model="categoryId"
                        class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400 transition-colors">
                    <option value="">Toutes les catégories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>

                <!-- Statut -->
                <select v-model="status"
                        class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400 transition-colors">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                    <option value="low_stock">Stock faible</option>
                    <option value="out_of_stock">Rupture</option>
                </select>

                <!-- Featured toggle -->
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2.5 rounded-xl border border-cream-200 bg-cream-50 hover:border-cognac-300 transition-colors">
                    <input type="checkbox" v-model="featured" class="accent-cognac-500" />
                    <span class="text-sm font-poppins text-onyx-600">En vedette</span>
                </label>

                <!-- Reset -->
                <button v-if="hasFilters" @click="resetFilters"
                        class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-500 hover:text-cognac-600 hover:bg-cream-100 transition-colors border border-cream-200">
                    <PhArrowsClockwise :size="15" />
                    Réinitialiser
                </button>

                <!-- Spacer -->
                <div class="flex-1" />

                <!-- Vue toggle -->
                <div class="flex items-center gap-1 p-1 rounded-xl border border-cream-200 bg-cream-50">
                    <button @click="viewMode = 'grid'"
                            class="p-2 rounded-lg transition-all"
                            :class="viewMode === 'grid' ? 'bg-white shadow-sm text-cognac-600' : 'text-onyx-400 hover:text-onyx-600'">
                        <PhGridFour :size="17" />
                    </button>
                    <button @click="viewMode = 'list'"
                            class="p-2 rounded-lg transition-all"
                            :class="viewMode === 'list' ? 'bg-white shadow-sm text-cognac-600' : 'text-onyx-400 hover:text-onyx-600'">
                        <PhListDashes :size="17" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Résultats ───────────────────────────────────────────── -->
        <div>
            <p class="text-xs font-poppins text-onyx-400 mb-3">
                {{ products.total }} produit{{ products.total > 1 ? 's' : '' }} trouvé{{ products.total > 1 ? 's' : '' }}
            </p>

            <!-- Vue Grid -->
            <div v-if="viewMode === 'grid'"
                 class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                <div v-for="product in products.data" :key="product.id"
                     class="bg-white rounded-2xl shadow-card overflow-hidden group hover:shadow-float transition-all duration-300 hover:-translate-y-0.5">

                    <!-- Image -->
                    <div class="relative aspect-square overflow-hidden bg-cream-50">
                        <img :src="product.thumbnail_url" :alt="product.name"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />

                        <!-- Badges -->
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            <span v-if="product.is_featured"
                                  class="flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-poppins font-bold text-white"
                                  style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <PhStar :size="11" weight="fill" /> Vedette
                            </span>
                            <span v-if="product.is_on_sale"
                                  class="px-2 py-0.5 rounded-lg text-xs font-poppins font-bold text-white"
                                  style="background: #e14d6e;">
                                -{{ product.discount_percent }}%
                            </span>
                            <span v-if="!product.is_in_stock"
                                  class="px-2 py-0.5 rounded-lg text-xs font-poppins font-bold text-white"
                                  style="background: #6b7280;">
                                Rupture
                            </span>
                            <span v-else-if="product.is_low_stock"
                                  class="flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-poppins font-bold"
                                  style="background: rgba(245,158,11,0.15); color: #d97706;">
                                <PhWarning :size="11" /> Stock bas
                            </span>
                        </div>

                        <!-- Statut actif -->
                        <div class="absolute top-2 right-2">
                            <span class="w-2.5 h-2.5 rounded-full block"
                                  :style="{ background: product.is_active ? '#10b981' : '#9ca3af' }" />
                        </div>

                        <!-- Actions hover -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <Link :href="route('admin.produits.show', product.id)"
                                  class="w-9 h-9 bg-white rounded-xl flex items-center justify-center hover:bg-cream-50 transition-colors shadow-md"
                                  @click.stop>
                                <PhEye :size="17" class="text-onyx-700" />
                            </Link>
                            <Link :href="route('admin.produits.edit', product.id)"
                                  class="w-9 h-9 bg-white rounded-xl flex items-center justify-center hover:bg-cream-50 transition-colors shadow-md"
                                  @click.stop>
                                <PhPencilSimple :size="17" class="text-onyx-700" />
                            </Link>
                            <button @click.stop="deleteProduct(product)"
                                    class="w-9 h-9 bg-white rounded-xl flex items-center justify-center hover:bg-red-50 transition-colors shadow-md">
                                <PhTrash :size="17" class="text-red-500" />
                            </button>
                        </div>
                    </div>

                    <!-- Infos -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <h3 class="font-poppins font-semibold text-onyx-800 text-sm leading-tight line-clamp-1">
                                {{ product.name }}
                            </h3>
                        </div>
                        <p v-if="product.category?.name"
                           class="text-xs font-poppins text-onyx-400 mb-2 flex items-center gap-1">
                            <PhTag :size="11" />
                            {{ product.category.name }}
                        </p>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-base font-bold font-poppins text-onyx-800">
                                    {{ formatPrice(product.price) }}
                                </span>
                                <span v-if="product.compare_price"
                                      class="ml-1.5 text-xs font-poppins text-onyx-400 line-through">
                                    {{ formatPrice(product.compare_price) }}
                                </span>
                            </div>
                            <span class="text-xs font-poppins px-2 py-0.5 rounded-lg"
                                  :class="product.stock > 10
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : product.stock > 0
                                    ? 'bg-amber-50 text-amber-700'
                                    : 'bg-red-50 text-red-600'">
                                {{ product.stock }} en stock
                            </span>
                        </div>

                        <!-- Actions bas -->
                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-cream-100">
                            <button @click.stop="toggleProduct(product)"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-poppins font-medium transition-all"
                                    :class="product.is_active
                                        ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                        : 'bg-gray-50 text-gray-500 hover:bg-gray-100'">
                                <component :is="product.is_active ? markRaw(PhToggleRight) : markRaw(PhToggleLeft)" :size="15" />
                                {{ product.is_active ? 'Actif' : 'Inactif' }}
                            </button>
                            <Link :href="route('admin.produits.edit', product.id)"
                                  class="flex items-center justify-center w-8 h-8 rounded-lg bg-cream-50 hover:bg-cognac-50 text-onyx-500 hover:text-cognac-600 transition-colors">
                                <PhPencilSimple :size="15" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vue List -->
            <div v-else class="bg-white rounded-2xl shadow-card overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #f5ede4;">
                            <th class="text-left px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Produit</th>
                            <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden md:table-cell">Catégorie</th>
                            <th class="text-right px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Prix</th>
                            <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden lg:table-cell">Stock</th>
                            <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Statut</th>
                            <th class="text-right px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-50">
                        <tr v-for="product in products.data" :key="product.id"
                            class="hover:bg-cream-50/50 transition-colors group">
                            <!-- Produit -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <img :src="product.thumbnail_url" :alt="product.name"
                                         class="w-11 h-11 rounded-xl object-cover shrink-0" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold font-poppins text-onyx-800 truncate max-w-48">
                                            {{ product.name }}
                                        </p>
                                        <p v-if="product.sku" class="text-xs font-poppins text-onyx-400">
                                            SKU : {{ product.sku }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <!-- Catégorie -->
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <span class="text-xs font-poppins text-onyx-500 px-2 py-1 rounded-lg bg-cream-50">
                                    {{ product.category?.name ?? '—' }}
                                </span>
                            </td>
                            <!-- Prix -->
                            <td class="px-4 py-3.5 text-right">
                                <p class="text-sm font-bold font-poppins text-onyx-800">
                                    {{ formatPrice(product.price) }}
                                </p>
                                <p v-if="product.compare_price"
                                   class="text-xs font-poppins text-onyx-400 line-through">
                                    {{ formatPrice(product.compare_price) }}
                                </p>
                            </td>
                            <!-- Stock -->
                            <td class="px-4 py-3.5 text-center hidden lg:table-cell">
                                <span class="text-sm font-bold font-poppins px-2.5 py-1 rounded-lg"
                                      :class="product.stock > 10
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : product.stock > 0
                                        ? 'bg-amber-50 text-amber-700'
                                        : 'bg-red-50 text-red-600'">
                                    {{ product.stock }}
                                </span>
                            </td>
                            <!-- Statut -->
                            <td class="px-4 py-3.5 text-center">
                                <button @click="toggleProduct(product)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-poppins font-medium transition-all"
                                        :class="product.is_active
                                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                    <component :is="product.is_active ? markRaw(PhToggleRight) : markRaw(PhToggleLeft)" :size="14" />
                                    {{ product.is_active ? 'Actif' : 'Inactif' }}
                                </button>
                            </td>
                            <!-- Actions -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link :href="route('admin.produits.show', product.id)"
                                          class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-onyx-700 hover:bg-cream-100 transition-colors">
                                        <PhEye :size="16" />
                                    </Link>
                                    <Link :href="route('admin.produits.edit', product.id)"
                                          class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-cognac-600 hover:bg-cognac-50 transition-colors">
                                        <PhPencilSimple :size="16" />
                                    </Link>
                                    <button @click="deleteProduct(product)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <PhTrash :size="16" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state liste -->
                <div v-if="!products.data.length"
                     class="flex flex-col items-center justify-center py-16">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                         style="background: rgba(196,149,106,0.08);">
                        <PhPackage :size="32" class="text-onyx-300" />
                    </div>
                    <p class="text-sm font-poppins font-semibold text-onyx-600 mb-1">Aucun produit trouvé</p>
                    <p class="text-xs font-poppins text-onyx-400">Essayez de modifier vos filtres</p>
                </div>
            </div>

            <!-- Empty state grid -->
            <div v-if="viewMode === 'grid' && !products.data.length"
                 class="flex flex-col items-center justify-center py-20">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-5"
                     style="background: rgba(196,149,106,0.08);">
                    <PhPackage :size="40" class="text-onyx-300" />
                </div>
                <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucun produit trouvé</p>
                <p class="text-sm font-poppins text-onyx-400 mb-5">
                    {{ hasFilters ? 'Essayez de modifier vos filtres' : 'Commencez par créer votre premier produit' }}
                </p>
                <Link v-if="!hasFilters" :href="route('admin.produits.create')"
                      class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhPlus :size="17" weight="bold" />
                    Créer un produit
                </Link>
                <button v-else @click="resetFilters"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-cognac-600 border border-cognac-300 hover:bg-cognac-50 transition-colors">
                    <PhArrowsClockwise :size="15" />
                    Réinitialiser les filtres
                </button>
            </div>
        </div>

        <!-- ── Pagination ───────────────────────────────────────────── -->
        <div v-if="products.last_page > 1"
             class="flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-3.5">
            <p class="text-xs font-poppins text-onyx-500">
                Page {{ products.current_page }} sur {{ products.last_page }}
                · {{ products.total }} résultats
            </p>
            <div class="flex items-center gap-1.5">
                <Link v-if="products.prev_page_url"
                      :href="products.prev_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">
                    ← Précédent
                </Link>
                <Link v-if="products.next_page_url"
                      :href="products.next_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">
                    Suivant →
                </Link>
            </div>
        </div>

    </div>
</template>