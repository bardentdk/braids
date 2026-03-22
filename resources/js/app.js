import './bootstrap'
import '../css/app.css'
import '@fontsource/poppins/300.css'
import '@fontsource/poppins/400.css'
import '@fontsource/poppins/500.css'
import '@fontsource/poppins/600.css'
import '@fontsource/poppins/700.css'
import '@fontsource/cormorant-garamond/400.css'
import '@fontsource/cormorant-garamond/500.css'
import '@fontsource/cormorant-garamond/600.css'
import '@fontsource/cormorant-garamond/700.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'

const appName = import.meta.env.VITE_APP_NAME || 'Patricia Braids'

/**
 * Helper route() — remplace le Ziggy natif
 * Gère :
 *   route('admin.services.show', 5)             → /admin/services/5
 *   route('admin.services.show', { service: 5 }) → /admin/services/5
 *   route('admin.services.images.delete', { product: 1, image: 3 })
 *   route().current('admin.dashboard')           → true/false
 */
function makeRouteHelper(ziggy) {
    const routes  = ziggy?.routes  ?? {}
    const baseUrl = (ziggy?.url    ?? '').replace(/\/$/, '')

    function buildUrl(name, params) {
        if (!routes[name]) {
            console.warn(`[route] Route "${name}" introuvable.`)
            return '#'
        }

        let uri = routes[name].uri

        if (params !== undefined && params !== null) {
            if (typeof params === 'object' && !Array.isArray(params)) {
                // Objet → on substitue chaque clé
                Object.entries(params).forEach(([key, value]) => {
                    uri = uri.replace(`{${key}}`,  encodeURIComponent(value))
                    uri = uri.replace(`{${key}?}`, encodeURIComponent(value))
                })
            } else {
                // Scalaire (number, string) → on remplace le PREMIER param trouvé
                uri = uri.replace(/\{[^}?]+\??}/, encodeURIComponent(params))
            }
        }

        // Supprime les segments optionnels non remplis
        uri = uri.replace(/\/\{[^}]+\?\}/g, '')

        return baseUrl + '/' + uri.replace(/^\//, '')
    }

    // Méthode .current() pour isActive dans Sidebar
    buildUrl.current = (name) => {
        if (!name || !routes[name]) return false
        try {
            // Construit un pattern regex depuis l'URI
            let uriPattern = routes[name].uri
                .replace(/\{[^}]+\?\}/g, '(?:/[^/]+)?')  // param optionnel
                .replace(/\{[^}]+\}/g,   '[^/]+')          // param requis
                .replace(/\//g,           '\\/')
            const regex = new RegExp(`^/${uriPattern}$`)
            return regex.test(window.location.pathname)
        } catch {
            return false
        }
    }

    return buildUrl
}

createInertiaApp({
    title: (title) => `${title} — ${appName}`,

    resolve: (name) => 
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        const pinia = createPinia()
        const ziggy = props.initialPage.props.ziggy ?? { routes: {}, url: '' }

        const routeHelper = makeRouteHelper(ziggy)

        // Expose globalement (templates Vue + JS)
        window.route  = routeHelper
        window._ziggy = ziggy

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)

        // Disponible dans tous les templates via this.route() ou route()
        app.config.globalProperties.route = routeHelper

        app.mount(el)
        return app
    },

    progress: { color: '#c4956a' },
})