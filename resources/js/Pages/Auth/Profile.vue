<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhUser, PhEnvelope, PhPhone, PhLock, PhCamera, PhFloppyDisk,
    PhSpinnerGap, PhWarning, PhCheckCircle, PhTrash, PhEye, PhEyeSlash,
    PhShieldCheck, PhSignOut, PhArrowLeft,
} from '@phosphor-icons/vue'

const props = defineProps({
    user:   Object,
    client: Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Onglet actif ───────────────────────────────────────────────────
const activeTab = ref('infos')
const tabs = [
    { key: 'infos',    label: 'Mes informations' },
    { key: 'password', label: 'Mot de passe' },
    { key: 'danger',   label: 'Compte' },
]

// ── Avatar preview ─────────────────────────────────────────────────
const avatarPreview = ref(props.user?.avatar ?? null)

function onAvatarChange(e) {
    const file = e.target.files[0]
    if (!file) return
    profileForm.avatar = file
    const reader = new FileReader()
    reader.onload = (ev) => { avatarPreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

// ── Formulaire infos ───────────────────────────────────────────────
const profileForm = useForm({
    name:        props.user?.name         ?? '',
    email:       props.user?.email        ?? '',
    phone:       props.client?.phone      ?? '',
    address:     props.client?.address    ?? '',
    city:        props.client?.city       ?? '',
    postal_code: props.client?.postal_code ?? '',
    avatar:      null,
})

function updateProfile() {
    profileForm.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
    })
}

// ── Formulaire mot de passe ────────────────────────────────────────
const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
})

const showCurrentPwd = ref(false)
const showNewPwd     = ref(false)
const showConfirmPwd = ref(false)

function updatePassword() {
    passwordForm.patch(route('profile.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}

// Indicateur force mot de passe
const passwordStrength = computed(() => {
    const pwd = passwordForm.password
    if (!pwd) return { score: 0, label: '', color: '' }
    let score = 0
    if (pwd.length >= 8)              score++
    if (/[A-Z]/.test(pwd))           score++
    if (/[0-9]/.test(pwd))           score++
    if (/[^A-Za-z0-9]/.test(pwd))   score++
    const map = [
        { score: 0, label: '',           color: '' },
        { score: 1, label: 'Faible',     color: '#ef4444' },
        { score: 2, label: 'Moyen',      color: '#f59e0b' },
        { score: 3, label: 'Bon',        color: '#3b82f6' },
        { score: 4, label: 'Excellent',  color: '#10b981' },
    ]
    return map[score]
})

// ── Suppression compte ─────────────────────────────────────────────
const deleteForm    = useForm({ password: '' })
const showDeletePwd = ref(false)
const confirmDelete = ref(false)

function deleteAccount() {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
    })
}

// ── Déconnexion ────────────────────────────────────────────────────
function logout() {
    router.post(route('logout'))
}

// ── Initiales avatar ───────────────────────────────────────────────
const initials = computed(() => {
    const name = props.user?.name ?? ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

onMounted(() => {
    gsap.fromTo('.profile-section',
        { opacity: 0, y: 18 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.08, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head title="Mon profil" />

    <div class="min-h-screen" style="background: #faf7f4;">
        <div class="max-w-3xl mx-auto px-4 py-10 space-y-6">

            <!-- ── Header ────────────────────────────────────────── -->
            <div class="profile-section flex items-center justify-between">
                <div>
                    <h1 class="font-cormorant text-4xl font-bold text-onyx-800">Mon profil</h1>
                    <p class="text-sm font-poppins text-onyx-500 mt-0.5">
                        Gérez vos informations personnelles
                    </p>
                </div>
                <button @click="logout"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-medium text-onyx-500 border border-cream-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all">
                    <PhSignOut :size="16" />
                    Déconnexion
                </button>
            </div>

            <!-- ── Flash ─────────────────────────────────────────── -->
            <div v-if="flash?.success"
                 class="profile-section flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
                 style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
                <PhCheckCircle :size="18" />{{ flash.success }}
            </div>

            <!-- ── Carte avatar + nom ─────────────────────────────── -->
            <div class="profile-section bg-white rounded-2xl shadow-card p-6">
                <div class="flex items-center gap-5">

                    <!-- Avatar -->
                    <div class="relative shrink-0">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden">
                            <img v-if="avatarPreview" :src="avatarPreview" :alt="user?.name"
                                 class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-white text-xl font-bold font-poppins"
                                 style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                                {{ initials }}
                            </div>
                        </div>
                        <!-- Bouton changer avatar -->
                        <label class="absolute -bottom-2 -right-2 w-8 h-8 rounded-xl flex items-center justify-center cursor-pointer shadow-md transition-all hover:scale-110"
                               style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhCamera :size="15" class="text-white" />
                            <input type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                        </label>
                    </div>

                    <div>
                        <h2 class="font-poppins font-bold text-onyx-800 text-lg">{{ user?.name }}</h2>
                        <p class="text-sm font-poppins text-onyx-400">{{ user?.email }}</p>
                        <span class="inline-flex items-center gap-1.5 mt-2 text-xs font-poppins font-semibold px-2.5 py-1 rounded-lg"
                              style="background: rgba(196,149,106,0.12); color: #b07d52;">
                            <PhShieldCheck :size="12" />
                            Compte vérifié
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Tabs ───────────────────────────────────────────── -->
            <div class="profile-section flex items-center gap-1 p-1 bg-white rounded-2xl shadow-card">
                <button v-for="tab in tabs" :key="tab.key"
                        @click="activeTab = tab.key"
                        class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium transition-all"
                        :class="activeTab === tab.key
                            ? 'bg-onyx-800 text-white shadow-sm'
                            : 'text-onyx-500 hover:text-onyx-700 hover:bg-cream-50'">
                    {{ tab.label }}
                </button>
            </div>

            <!-- ══════════════════════════════════════════════════ -->
            <!-- TAB : Mes informations -->
            <!-- ══════════════════════════════════════════════════ -->
            <div v-show="activeTab === 'infos'" class="space-y-5">

                <form @submit.prevent="updateProfile">
                    <div class="profile-section bg-white rounded-2xl shadow-card p-6 space-y-5">
                        <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                            <PhUser :size="18" class="text-cognac-500" />
                            Informations générales
                        </h2>

                        <!-- Nom complet -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Nom complet <span class="text-red-400">*</span>
                            </label>
                            <input v-model="profileForm.name" type="text"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                   :class="profileForm.errors.name ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            <p v-if="profileForm.errors.name" class="text-xs text-red-500 font-poppins mt-1">
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Email <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <PhEnvelope :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                                <input v-model="profileForm.email" type="email"
                                       class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="profileForm.errors.email ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            </div>
                            <p v-if="profileForm.errors.email" class="text-xs text-red-500 font-poppins mt-1">
                                {{ profileForm.errors.email }}
                            </p>
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Téléphone
                            </label>
                            <div class="relative">
                                <PhPhone :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                                <input v-model="profileForm.phone" type="tel"
                                       class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <!-- Adresse -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Adresse
                            </label>
                            <input v-model="profileForm.address" type="text"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                    Code postal
                                </label>
                                <input v-model="profileForm.postal_code" type="text"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                            <div>
                                <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                    Ville
                                </label>
                                <input v-model="profileForm.city" type="text"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-2">
                            <p v-if="profileForm.isDirty" class="text-xs font-poppins text-amber-600 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                                Modifications non enregistrées
                            </p>
                            <div class="ml-auto">
                                <button type="submit" :disabled="profileForm.processing"
                                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 disabled:opacity-60 disabled:translate-y-0"
                                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                    <PhSpinnerGap v-if="profileForm.processing" :size="17" class="animate-spin" />
                                    <PhFloppyDisk v-else :size="17" />
                                    {{ profileForm.processing ? 'Enregistrement…' : 'Sauvegarder' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ══════════════════════════════════════════════════ -->
            <!-- TAB : Mot de passe -->
            <!-- ══════════════════════════════════════════════════ -->
            <div v-show="activeTab === 'password'">

                <form @submit.prevent="updatePassword">
                    <div class="profile-section bg-white rounded-2xl shadow-card p-6 space-y-5">
                        <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                            <PhLock :size="18" class="text-cognac-500" />
                            Changer le mot de passe
                        </h2>

                        <!-- Mot de passe actuel -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Mot de passe actuel <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input v-model="passwordForm.current_password"
                                       :type="showCurrentPwd ? 'text' : 'password'"
                                       class="w-full px-3.5 pr-11 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="passwordForm.errors.current_password ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                <button type="button" @click="showCurrentPwd = !showCurrentPwd"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600 transition-colors">
                                    <component :is="showCurrentPwd ? PhEyeSlash : PhEye" :size="16" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-red-500 font-poppins mt-1">
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>

                        <!-- Nouveau mot de passe -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Nouveau mot de passe <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input v-model="passwordForm.password"
                                       :type="showNewPwd ? 'text' : 'password'"
                                       class="w-full px-3.5 pr-11 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="passwordForm.errors.password ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                <button type="button" @click="showNewPwd = !showNewPwd"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600 transition-colors">
                                    <component :is="showNewPwd ? PhEyeSlash : PhEye" :size="16" />
                                </button>
                            </div>

                            <!-- Indicateur force -->
                            <div v-if="passwordForm.password" class="mt-2 space-y-1">
                                <div class="flex gap-1">
                                    <div v-for="i in 4" :key="i"
                                         class="flex-1 h-1.5 rounded-full transition-all duration-300"
                                         :style="{
                                             background: i <= passwordStrength.score
                                                 ? passwordStrength.color
                                                 : '#e5d5c5'
                                         }" />
                                </div>
                                <p class="text-xs font-poppins" :style="{ color: passwordStrength.color }">
                                    Force : {{ passwordStrength.label }}
                                </p>
                            </div>
                            <p v-if="passwordForm.errors.password" class="text-xs text-red-500 font-poppins mt-1">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <!-- Confirmation -->
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Confirmer le mot de passe <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input v-model="passwordForm.password_confirmation"
                                       :type="showConfirmPwd ? 'text' : 'password'"
                                       class="w-full px-3.5 pr-11 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="passwordForm.errors.password_confirmation ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                <button type="button" @click="showConfirmPwd = !showConfirmPwd"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600 transition-colors">
                                    <component :is="showConfirmPwd ? PhEyeSlash : PhEye" :size="16" />
                                </button>
                            </div>

                            <!-- Correspondance -->
                            <p v-if="passwordForm.password && passwordForm.password_confirmation"
                               class="text-xs font-poppins mt-1 flex items-center gap-1"
                               :class="passwordForm.password === passwordForm.password_confirmation
                                   ? 'text-emerald-600' : 'text-red-500'">
                                <component :is="passwordForm.password === passwordForm.password_confirmation
                                    ? PhCheckCircle : PhWarning" :size="13" />
                                {{ passwordForm.password === passwordForm.password_confirmation
                                    ? 'Les mots de passe correspondent'
                                    : 'Les mots de passe ne correspondent pas' }}
                            </p>
                            <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-red-500 font-poppins mt-1">
                                {{ passwordForm.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Info sécurité -->
                        <div class="flex items-start gap-3 px-4 py-3 rounded-xl text-xs font-poppins"
                             style="background: rgba(196,149,106,0.06); border: 1px solid rgba(196,149,106,0.2);">
                            <PhShieldCheck :size="16" class="text-cognac-500 shrink-0 mt-0.5" />
                            <p class="text-onyx-600">
                                Choisissez un mot de passe d'au moins <strong>8 caractères</strong>,
                                avec des majuscules, des chiffres et des caractères spéciaux.
                            </p>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit" :disabled="passwordForm.processing"
                                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 disabled:opacity-60 disabled:translate-y-0"
                                    style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                <PhSpinnerGap v-if="passwordForm.processing" :size="17" class="animate-spin" />
                                <PhLock v-else :size="17" />
                                {{ passwordForm.processing ? 'Mise à jour…' : 'Mettre à jour' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ══════════════════════════════════════════════════ -->
            <!-- TAB : Compte (danger zone) -->
            <!-- ══════════════════════════════════════════════════ -->
            <div v-show="activeTab === 'danger'">
                <div class="profile-section bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800">Gestion du compte</h2>

                    <!-- Déconnexion -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-cream-200">
                        <div>
                            <p class="text-sm font-poppins font-semibold text-onyx-700">Se déconnecter</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">
                                Fermer votre session sur cet appareil
                            </p>
                        </div>
                        <button @click="logout"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                            <PhSignOut :size="16" />
                            Déconnexion
                        </button>
                    </div>

                    <!-- Suppression compte -->
                    <div class="p-4 rounded-xl border border-red-200"
                         style="background: rgba(239,68,68,0.02);">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-red-700">
                                    Supprimer mon compte
                                </p>
                                <p class="text-xs font-poppins text-onyx-400 mt-0.5">
                                    Cette action est irréversible. Toutes vos données seront supprimées.
                                </p>
                            </div>
                            <button v-if="!confirmDelete"
                                    @click="confirmDelete = true"
                                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-medium text-red-600 border border-red-200 hover:bg-red-50 transition-colors">
                                <PhTrash :size="16" />
                                Supprimer
                            </button>
                        </div>

                        <!-- Confirmation suppression -->
                        <div v-if="confirmDelete" class="space-y-3">
                            <div class="flex items-start gap-2 text-xs font-poppins text-red-600">
                                <PhWarning :size="15" class="shrink-0 mt-0.5" />
                                <span>Entrez votre mot de passe pour confirmer la suppression définitive de votre compte.</span>
                            </div>
                            <div class="relative">
                                <input v-model="deleteForm.password"
                                       :type="showDeletePwd ? 'text' : 'password'"
                                       placeholder="Votre mot de passe"
                                       class="w-full px-3.5 pr-11 py-2.5 rounded-xl text-sm font-poppins bg-white border border-red-200 focus:border-red-400 focus:outline-none transition-colors" />
                                <button type="button" @click="showDeletePwd = !showDeletePwd"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600">
                                    <component :is="showDeletePwd ? PhEyeSlash : PhEye" :size="16" />
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <button @click="confirmDelete = false; deleteForm.reset()"
                                        class="flex-1 py-2 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                                    Annuler
                                </button>
                                <button @click="deleteAccount"
                                        :disabled="!deleteForm.password || deleteForm.processing"
                                        class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-poppins font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                                    <PhSpinnerGap v-if="deleteForm.processing" :size="16" class="animate-spin" />
                                    <PhTrash v-else :size="16" />
                                    Confirmer la suppression
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>