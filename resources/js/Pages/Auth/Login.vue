<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhBridge,
    PhEnvelope,
    PhLockKey,
    PhEye,
    PhEyeSlash,
    PhArrowRight,
    PhSparkle,
    PhShieldCheck,
} from '@phosphor-icons/vue'

defineProps({ status: String })

const form = useForm({
    email:    '',
    password: '',
    remember: false,
})

const showPassword = ref(false)
const formRef      = ref(null)
const leftRef      = ref(null)

onMounted(() => {
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })

    tl.fromTo(leftRef.value,
        { opacity: 0, x: -60 },
        { opacity: 1, x: 0, duration: 1 }
    )
    .fromTo(formRef.value,
        { opacity: 0, x: 60 },
        { opacity: 1, x: 0, duration: 1 },
        '-=0.7'
    )
    .fromTo('.auth-field',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.12 },
        '-=0.5'
    )

    // Orbes animées côté gauche
    gsap.to('.orb-1', {
        y: -20, x: 10,
        duration: 4,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
    })
    gsap.to('.orb-2', {
        y: 15, x: -8,
        duration: 5,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: 1,
    })
    gsap.to('.orb-3', {
        y: -12, x: 15,
        duration: 3.5,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: 0.5,
    })
})

function submit() {
    form.post(route('login.store'), {
        onError: () => {
            gsap.fromTo(formRef.value,
                { x: -8 },
                { x: 0, duration: 0.5, ease: 'elastic.out(1, 0.3)' }
            )
        },
    })
}
</script>

<template>
    <Head title="Connexion" />

    <div class="min-h-screen flex">

        <!-- ── Côté gauche — Branding ──────────────── -->
        <div
            ref="leftRef"
            class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-luxury flex-col justify-between p-12"
        >
            <!-- Orbes décoratives -->
            <div class="orb-1 absolute top-1/4 left-1/4 w-72 h-72 rounded-full opacity-20"
                 style="background: radial-gradient(circle, #c4956a, transparent);" />
            <div class="orb-2 absolute bottom-1/3 right-1/4 w-56 h-56 rounded-full opacity-15"
                 style="background: radial-gradient(circle, #d4af37, transparent);" />
            <div class="orb-3 absolute top-2/3 left-1/3 w-40 h-40 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #e14d6e, transparent);" />

            <!-- Grille décorative -->
            <div class="absolute inset-0 opacity-5"
                 style="background-image: linear-gradient(#c4956a 1px, transparent 1px), linear-gradient(90deg, #c4956a 1px, transparent 1px); background-size: 48px 48px;" />

            <!-- Header logo -->
            <div class="relative z-10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                    <PhBridge :size="22" weight="bold" class="text-white" />
                </div>
                <span class="font-cormorant text-2xl font-semibold text-white tracking-wide">
                    Patricia Braids
                </span>
            </div>

            <!-- Contenu central -->
            <div class="relative z-10 space-y-8">
                <div>
                    <h1 class="font-cormorant text-5xl font-bold text-white leading-tight">
                        L'art des tresses,<br>
                        <span class="text-gradient-gold">sublimé pour vous</span>
                    </h1>
                    <p class="mt-4 text-lg font-poppins text-white/60 leading-relaxed max-w-sm">
                        Gérez votre studio, vos rendez-vous et vos clients depuis une interface pensée pour vous.
                    </p>
                </div>

                <!-- Features list -->
                <div class="space-y-3">
                    <div v-for="feat in [
                        { icon: 'PhCalendarCheck', text: 'Agenda & réservations en temps réel' },
                        { icon: 'PhShoppingBag',   text: 'Boutique et gestion des produits' },
                        { icon: 'PhChartLineUp',   text: 'Rapports financiers détaillés' },
                    ]" :key="feat.text"
                         class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                             style="background: rgba(196,149,106,0.2); border: 1px solid rgba(196,149,106,0.3);">
                            <PhShieldCheck :size="16" class="text-cognac-400" />
                        </div>
                        <span class="text-white/70 text-sm font-poppins">{{ feat.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10">
                <p class="text-white/30 text-xs font-poppins">
                    &copy; {{ new Date().getFullYear() }} Patricia Braids — Plateforme pro
                </p>
            </div>
        </div>

        <!-- ── Côté droit — Formulaire ─────────────── -->
        <div class="flex-1 flex items-center justify-center p-8 bg-cream-100">

            <div ref="formRef" class="w-full max-w-md">

                <!-- Logo mobile -->
                <div class="lg:hidden flex items-center gap-3 mb-10">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                        <PhBridge :size="22" weight="bold" class="text-white" />
                    </div>
                    <span class="font-cormorant text-2xl font-semibold text-onyx-800">Patricia Braids</span>
                </div>

                <!-- En-tête -->
                <div class="auth-field mb-8">
                    <h2 class="font-cormorant text-4xl font-bold text-onyx-800">
                        Bon retour
                    </h2>
                    <p class="mt-2 text-sm font-poppins text-onyx-500">
                        Connectez-vous à votre espace de gestion
                    </p>
                </div>

                <!-- Status message -->
                <div v-if="status"
                     class="auth-field mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-3">
                    <PhShieldCheck :size="18" class="text-emerald-600 shrink-0" />
                    <p class="text-sm text-emerald-700 font-poppins">{{ status }}</p>
                </div>

                <!-- Formulaire -->
                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Email -->
                    <div class="auth-field">
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                            Adresse email
                        </label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <PhEnvelope :size="18" class="text-onyx-400" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="contact@patricia-braids.com"
                                class="input-brand pl-11"
                                :class="{ 'border-red-400 bg-red-50': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="auth-field">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-onyx-600 uppercase tracking-widest">
                                Mot de passe
                            </label>
                            <Link
                                :href="route('password.request')"
                                class="text-xs text-cognac-500 hover:text-cognac-700 font-medium transition-colors"
                            >
                                Mot de passe oublié ?
                            </Link>
                        </div>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <PhLockKey :size="18" class="text-onyx-400" />
                            </div>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="input-brand pl-11 pr-12"
                                :class="{ 'border-red-400 bg-red-50': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600 transition-colors"
                            >
                                <PhEye v-if="!showPassword" :size="18" />
                                <PhEyeSlash v-else :size="18" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember me -->
                    <div class="auth-field flex items-center gap-3">
                        <button
                            type="button"
                            @click="form.remember = !form.remember"
                            class="w-5 h-5 rounded flex items-center justify-center border-2 transition-all duration-200 shrink-0"
                            :class="form.remember
                                ? 'border-cognac-500 bg-cognac-500'
                                : 'border-cream-400 bg-white hover:border-cognac-300'"
                        >
                            <svg v-if="form.remember" viewBox="0 0 12 12" fill="none" class="w-3 h-3">
                                <path d="M2 6L5 9L10 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <span class="text-sm text-onyx-600 font-poppins cursor-pointer select-none"
                              @click="form.remember = !form.remember">
                            Rester connecté(e)
                        </span>
                    </div>

                    <!-- Submit -->
                    <div class="auth-field pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn-primary w-full justify-center py-4 text-sm relative overflow-hidden"
                        >
                            <span v-if="!form.processing" class="flex items-center gap-2 relative z-10">
                                Se connecter
                                <PhArrowRight :size="18" weight="bold" />
                            </span>
                            <span v-else class="flex items-center gap-2 relative z-10">
                                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Connexion en cours...
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Separator -->
                <div class="auth-field my-8 flex items-center gap-4">
                    <div class="flex-1 h-px bg-cream-300" />
                    <span class="text-xs text-onyx-400 font-poppins">Pas encore de compte ?</span>
                    <div class="flex-1 h-px bg-cream-300" />
                </div>

                <!-- Register link -->
                <div class="auth-field">
                    <Link
                        :href="route('register')"
                        class="btn-secondary w-full justify-center py-4 text-sm"
                    >
                        <PhSparkle :size="18" />
                        Créer un compte client
                    </Link>
                </div>

            </div>
        </div>

    </div>
</template>