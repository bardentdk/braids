<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhBridge, PhUser, PhEnvelope, PhLockKey,
    PhEye, PhEyeSlash, PhPhone, PhArrowRight,
    PhArrowLeft, PhCheckCircle,
} from '@phosphor-icons/vue'

const form = useForm({
    first_name: '',
    last_name:  '',
    email:      '',
    phone:      '',
    password:   '',
    password_confirmation: '',
    newsletter: false,
    terms:      false,
})

const showPassword  = ref(false)
const showConfirm   = ref(false)
const formRef       = ref(null)
const step          = ref(1)

const passwordStrength = computed(() => {
    const p = form.password
    if (!p) return 0
    let score = 0
    if (p.length >= 8)  score++
    if (/[A-Z]/.test(p)) score++
    if (/[0-9]/.test(p)) score++
    if (/[^A-Za-z0-9]/.test(p)) score++
    return score
})

const strengthLabel = computed(() => {
    const labels = ['', 'Faible', 'Moyen', 'Bon', 'Excellent']
    return labels[passwordStrength.value] || ''
})

const strengthColor = computed(() => {
    const colors = ['', '#ef4444', '#f97316', '#3b82f6', '#10b981']
    return colors[passwordStrength.value] || ''
})

onMounted(() => {
    gsap.fromTo(formRef.value,
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }
    )
    gsap.fromTo('.auth-field',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.1, ease: 'power3.out', delay: 0.3 }
    )
})

function submit() {
    form.post(route('register.store'))
}

import { computed } from 'vue'
</script>

<template>
    <Head title="Créer un compte" />

    <div class="min-h-screen bg-pattern-braids bg-cream-100 flex items-center justify-center p-6">

        <div ref="formRef" class="w-full max-w-lg">

            <!-- Card -->
            <div class="glass-card rounded-3xl shadow-luxury p-10">

                <!-- Logo -->
                <div class="auth-field flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                            <PhBridge :size="22" weight="bold" class="text-white" />
                        </div>
                        <span class="font-cormorant text-xl font-semibold text-onyx-800">Patricia Braids</span>
                    </div>
                    <Link
                        :href="route('login')"
                        class="flex items-center gap-1.5 text-xs text-onyx-500 hover:text-cognac-600 transition-colors font-poppins"
                    >
                        <PhArrowLeft :size="14" />
                        Connexion
                    </Link>
                </div>

                <!-- Titre -->
                <div class="auth-field mb-8">
                    <h2 class="font-cormorant text-3xl font-bold text-onyx-800">
                        Rejoindre l'univers Patricia
                    </h2>
                    <p class="mt-1.5 text-sm text-onyx-500 font-poppins">
                        Créez votre compte client pour réserver et commander
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Nom / Prénom -->
                    <div class="auth-field grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                                Prénom
                            </label>
                            <div class="relative">
                                <PhUser :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    placeholder="Amina"
                                    autocomplete="given-name"
                                    class="input-brand pl-10 text-sm"
                                    :class="{ 'border-red-400': form.errors.first_name }"
                                />
                            </div>
                            <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-500">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                                Nom
                            </label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                placeholder="Diallo"
                                autocomplete="family-name"
                                class="input-brand text-sm"
                                :class="{ 'border-red-400': form.errors.last_name }"
                            />
                            <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-500">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="auth-field">
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                            Email
                        </label>
                        <div class="relative">
                            <PhEnvelope :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="amina@email.com"
                                autocomplete="email"
                                class="input-brand pl-10 text-sm"
                                :class="{ 'border-red-400': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Téléphone -->
                    <div class="auth-field">
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                            Téléphone
                            <span class="normal-case font-normal text-onyx-400 ml-1">(optionnel)</span>
                        </label>
                        <div class="relative">
                            <PhPhone :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                            <input
                                v-model="form.phone"
                                type="tel"
                                placeholder="+33 6 12 34 56 78"
                                autocomplete="tel"
                                class="input-brand pl-10 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="auth-field">
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                            Mot de passe
                        </label>
                        <div class="relative">
                            <PhLockKey :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Minimum 8 caractères"
                                class="input-brand pl-10 pr-11 text-sm"
                                :class="{ 'border-red-400': form.errors.password }"
                            />
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600 transition-colors">
                                <PhEye v-if="!showPassword" :size="16" />
                                <PhEyeSlash v-else :size="16" />
                            </button>
                        </div>

                        <!-- Strength bar -->
                        <div v-if="form.password" class="mt-2 space-y-1">
                            <div class="flex gap-1">
                                <div v-for="i in 4" :key="i"
                                     class="h-1 flex-1 rounded-full transition-all duration-300"
                                     :style="{ backgroundColor: i <= passwordStrength ? strengthColor : '#e5e7eb' }" />
                            </div>
                            <p class="text-xs font-poppins" :style="{ color: strengthColor }">{{ strengthLabel }}</p>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirmation -->
                    <div class="auth-field">
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                            Confirmation du mot de passe
                        </label>
                        <div class="relative">
                            <PhLockKey :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                            <input
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Répétez le mot de passe"
                                class="input-brand pl-10 pr-11 text-sm"
                            />
                            <button type="button" @click="showConfirm = !showConfirm"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600 transition-colors">
                                <PhEye v-if="!showConfirm" :size="16" />
                                <PhEyeSlash v-else :size="16" />
                            </button>
                        </div>
                        <!-- Match indicator -->
                        <div v-if="form.password_confirmation && form.password"
                             class="mt-1.5 flex items-center gap-1.5">
                            <PhCheckCircle
                                :size="14"
                                :class="form.password === form.password_confirmation ? 'text-emerald-500' : 'text-red-400'"
                            />
                            <span class="text-xs font-poppins"
                                  :class="form.password === form.password_confirmation ? 'text-emerald-600' : 'text-red-500'">
                                {{ form.password === form.password_confirmation ? 'Les mots de passe correspondent' : 'Ne correspondent pas' }}
                            </span>
                        </div>
                    </div>

                    <!-- Newsletter -->
                    <div class="auth-field flex items-start gap-3">
                        <button type="button" @click="form.newsletter = !form.newsletter"
                                class="w-5 h-5 mt-0.5 rounded flex items-center justify-center border-2 transition-all duration-200 shrink-0"
                                :class="form.newsletter ? 'border-cognac-500 bg-cognac-500' : 'border-cream-400 bg-white hover:border-cognac-300'">
                            <svg v-if="form.newsletter" viewBox="0 0 12 12" fill="none" class="w-3 h-3">
                                <path d="M2 6L5 9L10 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <span class="text-sm text-onyx-600 font-poppins leading-relaxed cursor-pointer"
                              @click="form.newsletter = !form.newsletter">
                            Je souhaite recevoir les offres exclusives et nouveautés de Patricia Braids
                        </span>
                    </div>

                    <!-- CGU -->
                    <div class="auth-field flex items-start gap-3">
                        <button type="button" @click="form.terms = !form.terms"
                                class="w-5 h-5 mt-0.5 rounded flex items-center justify-center border-2 transition-all duration-200 shrink-0"
                                :class="form.terms ? 'border-cognac-500 bg-cognac-500' : 'border-cream-400 bg-white hover:border-cognac-300 '">
                            <svg v-if="form.terms" viewBox="0 0 12 12" fill="none" class="w-3 h-3">
                                <path d="M2 6L5 9L10 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <span class="text-sm text-onyx-600 font-poppins leading-relaxed cursor-pointer"
                              @click="form.terms = !form.terms">
                            J'accepte les
                            <a href="#" class="text-cognac-600 hover:underline">conditions d'utilisation</a>
                            et la
                            <a href="#" class="text-cognac-600 hover:underline">politique de confidentialité</a>
                        </span>
                    </div>
                    <p v-if="form.errors.terms" class="text-xs text-red-500">{{ form.errors.terms }}</p>

                    <!-- Submit -->
                    <div class="auth-field pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.terms"
                            class="btn-primary w-full justify-center py-4 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!form.processing" class="flex items-center gap-2 relative z-10">
                                Créer mon compte
                                <PhArrowRight :size="18" weight="bold" />
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Création en cours...
                            </span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</template>