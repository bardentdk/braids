<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhPlus, PhTrash, PhCheckCircle, PhX, PhClock,
    PhCalendarBlank, PhLock, PhLockOpen, PhPencilSimple,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    availabilities: Array,
    blocked:        Array,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

const dayNames   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi']
const dayOrder   = [1,2,3,4,5,6,0]   // Lun → Dim

// ── Créneau récurrent ─────────────────────────────────────────────
const showSlotModal = ref(false)
const slotForm = useForm({
    day_of_week:    1,
    start_time:     '09:00',
    end_time:       '18:00',
    slot_duration:  60,
    is_active:      true,
})

function submitSlot() {
    slotForm.post(route('admin.disponibilites.store'), {
        preserveScroll: true,
        onSuccess: () => { showSlotModal.value = false; slotForm.reset() },
    })
}

function deleteSlot(id) {
    if (!confirm('Supprimer ce créneau ?')) return
    router.delete(route('admin.disponibilites.destroy', id), { preserveScroll: true })
}

function toggleSlot(slot) {
    router.patch(route('admin.disponibilites.update', slot.id), { is_active: !slot.is_active }, { preserveScroll: true })
}

// ── Blocage date ──────────────────────────────────────────────────
const showBlockModal = ref(false)
const blockForm = useForm({
    date:       '',
    start_time: '',
    end_time:   '',
    reason:     '',
    full_day:   true,
})

function submitBlock() {
    blockForm.post(route('admin.disponibilites.block'), {
        preserveScroll: true,
        onSuccess: () => { showBlockModal.value = false; blockForm.reset() },
    })
}

function unblock(id) {
    if (!confirm('Débloquer cette plage ?')) return
    router.delete(route('admin.disponibilites.destroy', id), { preserveScroll: true })
}

const slotsByDay = computed(() => {
    const map = {}
    dayOrder.forEach(d => { map[d] = [] })
    ;(props.availabilities ?? []).forEach(s => {
        if (map[s.day_of_week] !== undefined) map[s.day_of_week].push(s)
    })
    return map
})
</script>

<template>
    <Head title="Disponibilités" />

    <!-- Modal créneau -->
    <Transition name="fade">
        <div v-if="showSlotModal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);"
             @click.self="showSlotModal = false">
            <div class="bg-white rounded-2xl shadow-luxury w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-poppins font-bold text-onyx-800">Nouveau créneau récurrent</h3>
                    <button @click="showSlotModal = false" class="text-onyx-400 hover:text-onyx-700"><PhX :size="20" /></button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Jour</label>
                        <select v-model="slotForm.day_of_week" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none">
                            <option v-for="(d, i) in dayNames" :key="i" :value="i">{{ d }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Début</label>
                            <input v-model="slotForm.start_time" type="time" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Fin</label>
                            <input v-model="slotForm.end_time" type="time" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Durée créneau</label>
                        <select v-model="slotForm.slot_duration" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none">
                            <option :value="30">30 min</option>
                            <option :value="45">45 min</option>
                            <option :value="60">1 heure</option>
                            <option :value="90">1h30</option>
                            <option :value="120">2 heures</option>
                            <option :value="180">3 heures</option>
                            <option :value="240">4 heures</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="slotForm.is_active" class="accent-cognac-500 w-4 h-4" />
                        <span class="text-sm font-poppins text-onyx-700">Actif immédiatement</span>
                    </label>
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showSlotModal = false" class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50">Annuler</button>
                    <button @click="submitSlot" :disabled="slotForm.processing"
                            class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60"
                            style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        {{ slotForm.processing ? '…' : 'Créer' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal blocage -->
    <Transition name="fade">
        <div v-if="showBlockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);"
             @click.self="showBlockModal = false">
            <div class="bg-white rounded-2xl shadow-luxury w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-poppins font-bold text-onyx-800">Bloquer une date</h3>
                    <button @click="showBlockModal = false" class="text-onyx-400 hover:text-onyx-700"><PhX :size="20" /></button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Date *</label>
                        <input v-model="blockForm.date" type="date" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-cream-200 hover:bg-cream-50">
                        <input type="checkbox" v-model="blockForm.full_day" class="accent-cognac-500 w-4 h-4" />
                        <span class="text-sm font-poppins text-onyx-700">Journée entière</span>
                    </label>
                    <div v-if="!blockForm.full_day" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">De</label>
                            <input v-model="blockForm.start_time" type="time" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">À</label>
                            <input v-model="blockForm.end_time" type="time" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Motif (optionnel)</label>
                        <input v-model="blockForm.reason" type="text" placeholder="Congés, formation…" class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button @click="showBlockModal = false" class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50">Annuler</button>
                    <button @click="submitBlock" :disabled="blockForm.processing || !blockForm.date"
                            class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60"
                            style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                        {{ blockForm.processing ? '…' : 'Bloquer' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <div class="p-6 space-y-6">

        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Disponibilités</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">Gérez vos créneaux récurrents et vos jours bloqués</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="showBlockModal = true"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90"
                        style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <PhLock :size="16" /> Bloquer une date
                </button>
                <button @click="showSlotModal = true"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90 hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhPlus :size="16" weight="bold" /> Nouveau créneau
                </button>
            </div>
        </div>

        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Créneaux récurrents -->
            <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between" style="border-bottom: 1px solid #f5ede4;">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2">
                        <PhClock :size="16" class="text-cognac-500" /> Créneaux récurrents
                    </h2>
                    <button @click="showSlotModal = true" class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium transition-colors">
                        + Ajouter
                    </button>
                </div>
                <div class="divide-y divide-cream-50">
                    <div v-for="dayIdx in dayOrder" :key="dayIdx" class="px-5 py-3.5">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-poppins font-semibold text-onyx-700 uppercase tracking-wide">{{ dayNames[dayIdx] }}</p>
                            <span v-if="!slotsByDay[dayIdx]?.length" class="text-xs font-poppins text-onyx-300 italic">Fermé</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="slot in slotsByDay[dayIdx]" :key="slot.id"
                                 class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-poppins font-medium transition-all"
                                 :style="slot.is_active
                                     ? 'background: rgba(196,149,106,0.1); color: #b07d52; border: 1px solid rgba(196,149,106,0.3);'
                                     : 'background: #f9f9f8; color: #9ca3af; border: 1px solid #f0ede9;'">
                                <PhClock :size="11" />
                                {{ slot.start_time }} – {{ slot.end_time }}
                                <span class="text-xs opacity-60">({{ slot.slot_duration }}min)</span>
                                <button @click="toggleSlot(slot)" class="hover:opacity-60 transition-opacity" :title="slot.is_active ? 'Désactiver' : 'Activer'">
                                    {{ slot.is_active ? '⏸' : '▶' }}
                                </button>
                                <button @click="deleteSlot(slot.id)" class="hover:text-red-500 transition-colors ml-0.5">
                                    <PhX :size="10" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dates bloquées -->
            <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between" style="border-bottom: 1px solid #f5ede4;">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2">
                        <PhLock :size="16" class="text-red-500" /> Dates bloquées
                    </h2>
                    <button @click="showBlockModal = true" class="text-xs font-poppins text-red-500 hover:text-red-700 font-medium transition-colors">
                        + Bloquer
                    </button>
                </div>
                <div v-if="blocked?.length" class="divide-y divide-cream-50">
                    <div v-for="b in blocked" :key="b.id" class="flex items-center justify-between px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(239,68,68,0.08);">
                                <PhCalendarBlank :size="16" class="text-red-400" />
                            </div>
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-800">{{ b.date }}</p>
                                <p class="text-xs font-poppins text-onyx-400">
                                    {{ b.full_day ? 'Journée entière' : `${b.start_time} – ${b.end_time}` }}
                                    <span v-if="b.reason" class="text-onyx-300"> · {{ b.reason }}</span>
                                </p>
                            </div>
                        </div>
                        <button @click="unblock(b.id)" class="flex items-center gap-1.5 text-xs font-poppins text-emerald-600 hover:text-emerald-800 font-medium transition-colors">
                            <PhLockOpen :size="13" /> Débloquer
                        </button>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-12">
                    <PhLockOpen :size="36" class="text-onyx-200 mb-3" />
                    <p class="text-xs font-poppins text-onyx-400">Aucune date bloquée pour le moment</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>