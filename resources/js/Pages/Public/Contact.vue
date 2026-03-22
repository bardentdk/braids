<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhEnvelope, PhPhone, PhMapPin, PhInstagramLogo,
    PhTiktokLogo, PhPaperPlaneTilt, PhUser, PhNote,
    PhTag, PhArrowRight, PhCalendarCheck, PhCheck,
} from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({ settings: Object })

const form = useForm({
    name:    '',
    email:   '',
    phone:   '',
    subject: '',
    message: '',
})

const formRef = ref(null)

onMounted(() => {
    gsap.fromTo('.contact-hero', { opacity: 0, y: 40 }, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out' })
    gsap.fromTo('.contact-form', { opacity: 0, x: 40 }, { opacity: 1, x: 0, duration: 0.8, ease: 'power3.out', delay: 0.2 })
    gsap.fromTo('.contact-info', { opacity: 0, x: -40 }, { opacity: 1, x: 0, duration: 0.8, ease: 'power3.out', delay: 0.1 })
})

function submit() {
    form.post(route('contact.send'), {
        onSuccess: () => form.reset(),
    })
}

const subjects = [
    'Demande d\'information',
    'Tarifs & prestations',
    'Réservation',
    'Produits & boutique',
    'Partenariat',
    'Autre',
]
</script>

<template>
    <Head title="Nous Contacter" />

    <!-- Hero -->
    <section class="relative py-24" style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">
        <div class="absolute inset-0 opacity-15"
             style="background: radial-gradient(circle at 60% 40%, #c4956a, transparent 50%);" />

        <div class="container-brand contact-hero relative z-10 max-w-xl">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-5 text-xs font-semibold uppercase tracking-widest font-poppins"
                 style="background: rgba(196,149,106,0.15); color: #c4956a;">
                <PhEnvelope :size="14" />
                Contact
            </div>
            <h1 class="font-cormorant text-5xl md:text-6xl font-bold text-white mb-4">
                Parlons de votre projet
            </h1>
            <p class="text-lg text-white/60 font-poppins leading-relaxed">
                Une question ? Un renseignement ? Patricia vous répond sous 24h.
            </p>
        </div>
    </section>

    <!-- Corps -->
    <section class="py-20" style="background: #faf7f4;">
        <div class="container-brand">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">

                <!-- Infos de contact -->
                <div class="contact-info lg:col-span-2 space-y-6">
                    <div>
                        <h2 class="font-cormorant text-3xl font-bold text-onyx-800 mb-2">Informations</h2>
                        <p class="text-sm text-onyx-400 font-poppins leading-relaxed">
                            Contactez-nous directement ou remplissez le formulaire, nous vous répondrons rapidement.
                        </p>
                    </div>

                    <!-- Coordonnées -->
                    <div class="space-y-4">
                        <a :href="`tel:${settings.site_phone}`"
                           class="group flex items-center gap-4 p-4 bg-white rounded-2xl shadow-card hover:shadow-float transition-all duration-300">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all group-hover:scale-110"
                                 style="background: rgba(196,149,106,0.1);">
                                <PhPhone :size="22" class="text-cognac-500" />
                            </div>
                            <div>
                                <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest font-semibold">Téléphone</p>
                                <p class="text-sm font-semibold text-onyx-800 font-poppins mt-0.5">{{ settings.site_phone }}</p>
                            </div>
                            <PhArrowRight :size="16" class="ml-auto text-onyx-300 group-hover:text-cognac-500 transition-colors" />
                        </a>

                        <a :href="`mailto:${settings.site_email}`"
                           class="group flex items-center gap-4 p-4 bg-white rounded-2xl shadow-card hover:shadow-float transition-all duration-300">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all group-hover:scale-110"
                                 style="background: rgba(196,149,106,0.1);">
                                <PhEnvelope :size="22" class="text-cognac-500" />
                            </div>
                            <div>
                                <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest font-semibold">Email</p>
                                <p class="text-sm font-semibold text-onyx-800 font-poppins mt-0.5">{{ settings.site_email }}</p>
                            </div>
                            <PhArrowRight :size="16" class="ml-auto text-onyx-300 group-hover:text-cognac-500 transition-colors" />
                        </a>

                        <div class="flex items-center gap-4 p-4 bg-white rounded-2xl shadow-card">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                                 style="background: rgba(196,149,106,0.1);">
                                <PhMapPin :size="22" class="text-cognac-500" />
                            </div>
                            <div>
                                <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest font-semibold">Localisation</p>
                                <p class="text-sm font-semibold text-onyx-800 font-poppins mt-0.5">{{ settings.site_address }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Réseaux -->
                    <div class="space-y-3">
                        <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest font-semibold">Réseaux sociaux</p>
                        <div class="flex items-center gap-3">
                            <a v-if="settings.social_instagram" :href="settings.social_instagram" target="_blank"
                               class="flex items-center gap-2.5 px-4 py-2.5 bg-white rounded-xl shadow-card hover:shadow-float transition-all text-sm font-poppins font-medium text-onyx-700 hover:text-pink-600">
                                <PhInstagramLogo :size="18" />
                                Instagram
                            </a>
                            <a v-if="settings.social_tiktok" :href="settings.social_tiktok" target="_blank"
                               class="flex items-center gap-2.5 px-4 py-2.5 bg-white rounded-xl shadow-card hover:shadow-float transition-all text-sm font-poppins font-medium text-onyx-700 hover:text-onyx-900">
                                <PhTiktokLogo :size="18" />
                                TikTok
                            </a>
                        </div>
                    </div>

                    <!-- RDV CTA -->
                    <div class="p-5 rounded-2xl" style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">
                        <p class="text-sm font-semibold text-white font-poppins mb-1">Prête à réserver ?</p>
                        <p class="text-xs text-white/50 font-poppins mb-3">Prenez rendez-vous directement en ligne, c'est simple et rapide.</p>
                        <a :href="route('booking.services')"
                           class="inline-flex items-center gap-2 text-sm font-semibold font-poppins"
                           style="color: #c4956a;">
                            <PhCalendarCheck :size="16" />
                            Réserver une séance
                            <PhArrowRight :size="14" />
                        </a>
                    </div>
                </div>

                <!-- Formulaire -->
                <div class="contact-form lg:col-span-3">
                    <div class="bg-white rounded-3xl p-8 shadow-luxury">
                        <h2 class="font-cormorant text-2xl font-bold text-onyx-800 mb-6">Envoyez un message</h2>

                        <form @submit.prevent="submit" class="space-y-5">

                            <!-- Nom + Email -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Nom complet</label>
                                    <div class="relative flex">
                                        <PhUser :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none right-5" />
                                        <input v-model="form.name" type="text" placeholder="Amina Diallo"
                                               class="input-brand pl-10 text-sm"
                                               :class="{ 'border-red-400': form.errors.name }" />
                                    </div>
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Email</label>
                                    <div class="relative">
                                        <PhEnvelope :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                        <input v-model="form.email" type="email" placeholder="email@exemple.com"
                                               class="input-brand pl-10 text-sm"
                                               :class="{ 'border-red-400': form.errors.email }" />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                                </div>
                            </div>

                            <!-- Téléphone -->
                            <div>
                                <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">
                                    Téléphone
                                    <span class="normal-case font-normal text-onyx-400 ml-1">(optionnel)</span>
                                </label>
                                <div class="relative">
                                    <PhPhone :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                    <input v-model="form.phone" type="tel" placeholder="+33 6 12 34 56 78"
                                           class="input-brand pl-10 text-sm" />
                                </div>
                            </div>

                            <!-- Sujet -->
                            <div>
                                <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Sujet</label>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <button v-for="subj in subjects" :key="subj"
                                            type="button"
                                            @click="form.subject = form.subject === subj ? '' : subj"
                                            class="px-3 py-1.5 rounded-xl text-xs font-medium font-poppins transition-all border"
                                            :class="form.subject === subj
                                                ? 'text-white border-cognac-400'
                                                : 'border-cream-300 text-onyx-500 hover:border-cognac-300'"
                                            :style="form.subject === subj ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                                        {{ subj }}
                                    </button>
                                </div>
                                <div class="relative">
                                    <PhTag :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                    <input v-model="form.subject" type="text" placeholder="Ou précisez votre sujet"
                                           class="input-brand pl-10 text-sm"
                                           :class="{ 'border-red-400': form.errors.subject }" />
                                </div>
                                <p v-if="form.errors.subject" class="mt-1 text-xs text-red-500">{{ form.errors.subject }}</p>
                            </div>

                            <!-- Message -->
                            <div>
                                <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Message</label>
                                <div class="relative">
                                    <PhNote :size="16" class="absolute left-3.5 top-3.5 text-onyx-400 pointer-events-none" />
                                    <textarea v-model="form.message"
                                              rows="5"
                                              placeholder="Décrivez votre demande..."
                                              class="input-brand pl-10 pt-3 text-sm resize-none"
                                              :class="{ 'border-red-400': form.errors.message }" />
                                </div>
                                <div class="flex items-center justify-between mt-1.5">
                                    <p v-if="form.errors.message" class="text-xs text-red-500">{{ form.errors.message }}</p>
                                    <p class="text-xs text-onyx-400 font-poppins ml-auto">{{ form.message.length }}/2000</p>
                                </div>
                            </div>

                            <!-- Submit -->
                            <button type="submit" :disabled="form.processing"
                                    class="btn-primary w-full justify-center py-4">
                                <span v-if="!form.processing" class="flex items-center gap-2 relative z-10">
                                    <PhPaperPlaneTilt :size="18" weight="bold" />
                                    Envoyer le message
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Envoi...
                                </span>
                            </button>

                            <!-- Rassurance -->
                            <div class="flex items-center gap-2 text-xs text-onyx-400 font-poppins justify-center">
                                <PhCheck :size="13" class="text-cognac-400" />
                                Réponse garantie sous 24h ouvrées
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>