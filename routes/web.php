<?php

use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════════
// ── WEBHOOKS — HORS CSRF (déclarés en tout premier, sans middleware) ──
// ══════════════════════════════════════════════════════════════════════
Route::post('/webhooks/stripe', [\App\Http\Controllers\Public\PaymentController::class, 'stripeWebhook'])
     ->name('webhooks.stripe');
Route::post('/webhooks/paypal', [\App\Http\Controllers\Public\PaymentController::class, 'paypalWebhook'])
     ->name('webhooks.paypal');

// ══════════════════════════════════════════════════════════════════════
// ── AUTH ──────────────────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════════
Route::middleware('guest.only')->group(function () {
    Route::get('/connexion',            [\App\Http\Controllers\Auth\LoginController::class, 'show'])->name('login');
    Route::post('/connexion',           [\App\Http\Controllers\Auth\LoginController::class, 'store'])->name('login.store');
    Route::get('/inscription',          [\App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
    Route::post('/inscription',         [\App\Http\Controllers\Auth\RegisterController::class, 'store'])->name('register.store');
    Route::get('/mot-de-passe-oublie',  [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'send'])->name('password.email');
    Route::get('/reinitialiser/{token}',[\App\Http\Controllers\Auth\ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reinitialiser',       [\App\Http\Controllers\Auth\ResetPasswordController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [\App\Http\Controllers\Auth\LogoutController::class, 'destroy'])->name('logout');

    Route::get('/email/verification',          [\App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verifier/{id}/{hash}',  [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/email/renvoyer',             [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])->name('verification.send');

    Route::get('/profil',                [\App\Http\Controllers\Auth\ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profil',              [\App\Http\Controllers\Auth\ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profil/mot-de-passe', [\App\Http\Controllers\Auth\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profil',             [\App\Http\Controllers\Auth\ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ══════════════════════════════════════════════════════════════════════
// ── ADMIN ─────────────────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════════
Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.admin'])->group(function () {

    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // ── Blog IA — endpoints statiques AVANT resource ──────────────
    Route::post('blog/ai/generate',       [\App\Http\Controllers\Admin\BlogController::class, 'aiGenerate'])->name('blog.ai.generate');
    Route::post('blog/ai/improve',        [\App\Http\Controllers\Admin\BlogController::class, 'aiImprove'])->name('blog.ai.improve');
    Route::post('blog/ai/suggest-topics', [\App\Http\Controllers\Admin\BlogController::class, 'aiSuggestTopics'])->name('blog.ai.suggest-topics');
    Route::post('blog/ai/titles',         [\App\Http\Controllers\Admin\BlogController::class, 'aiTitles'])->name('blog.ai.titles');

    // ── Blog — actions spécifiques AVANT resource ─────────────────
    Route::patch('blog/{blog}/toggle-status',   [\App\Http\Controllers\Admin\BlogController::class, 'toggleStatus'])->name('blog.toggle-status');
    Route::patch('blog/{blog}/toggle-featured', [\App\Http\Controllers\Admin\BlogController::class, 'toggleFeatured'])->name('blog.toggle-featured');

    // ── Blog CRUD ─────────────────────────────────────────────────
    Route::resource('blog', \App\Http\Controllers\Admin\BlogController::class);

    // ── Blog catégories ───────────────────────────────────────────
    Route::resource('blog-categories', \App\Http\Controllers\Admin\BlogCategoryController::class)
         ->except(['show', 'create', 'edit']);

    // ── Clients ───────────────────────────────────────────────────
    Route::get('clients/{client}/historique', [\App\Http\Controllers\Admin\ClientController::class, 'history'])->name('clients.history');
    Route::patch('clients/{client}/vip',      [\App\Http\Controllers\Admin\ClientController::class, 'toggleVip'])->name('clients.toggle-vip');
    Route::resource('clients', \App\Http\Controllers\Admin\ClientController::class);

    // ── Catégories produits ───────────────────────────────────────
    Route::resource('categories', \App\Http\Controllers\Admin\ProductCategoryController::class);

    // ── Produits (spécifiques AVANT resource) ─────────────────────
    Route::post('produits/{produit}/toggle',                  [\App\Http\Controllers\Admin\ProductController::class, 'toggle'])->name('produits.toggle');
    Route::post('produits/{product}/images',                  [\App\Http\Controllers\Admin\ProductController::class, 'uploadImages'])->name('produits.images.upload');
    Route::delete('produits/{product}/images/{image}',        [\App\Http\Controllers\Admin\ProductController::class, 'deleteImage'])->name('produits.images.delete');
    Route::patch('produits/{product}/images/{image}/primary', [\App\Http\Controllers\Admin\ProductController::class, 'setPrimaryImage'])->name('produits.images.primary');
    Route::resource('produits', \App\Http\Controllers\Admin\ProductController::class);

    // ── Services (spécifique AVANT resource) ──────────────────────
    Route::patch('services/{service}/toggle', [\App\Http\Controllers\Admin\ServiceController::class, 'toggle'])->name('services.toggle');
    Route::resource('services', \App\Http\Controllers\Admin\ServiceController::class);

    // ── Disponibilités (statiques AVANT resource) ─────────────────
    Route::get('disponibilites/creneaux/{date}', [\App\Http\Controllers\Admin\AvailabilityController::class, 'slotsForDate'])->name('disponibilites.slots');
    Route::post('disponibilites/bloc',           [\App\Http\Controllers\Admin\AvailabilityController::class, 'block'])->name('disponibilites.block');
    Route::resource('disponibilites', \App\Http\Controllers\Admin\AvailabilityController::class);

    // ── Rendez-vous (calendar AVANT resource) ─────────────────────
    Route::get('agenda', [\App\Http\Controllers\Admin\AppointmentController::class, 'calendar'])->name('rendez-vous.calendar');
    Route::resource('rendez-vous', \App\Http\Controllers\Admin\AppointmentController::class);
    Route::patch('rendez-vous/{appointment}/confirmer', [\App\Http\Controllers\Admin\AppointmentController::class, 'confirm'])->name('rendez-vous.confirm');
    Route::patch('rendez-vous/{appointment}/annuler',   [\App\Http\Controllers\Admin\AppointmentController::class, 'cancel'])->name('rendez-vous.cancel');
    Route::patch('rendez-vous/{appointment}/terminer',  [\App\Http\Controllers\Admin\AppointmentController::class, 'complete'])->name('rendez-vous.complete');
    Route::post('rendez-vous/{appointment}/rappel',     [\App\Http\Controllers\Admin\AppointmentController::class, 'sendReminder'])->name('rendez-vous.reminder');

    // ── Commandes (spécifiques AVANT resource) ────────────────────
    Route::patch('commandes/{order}/statut', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('commandes.status');
    Route::post('commandes/{order}/facture', [\App\Http\Controllers\Admin\OrderController::class, 'generateInvoice'])->name('commandes.invoice');
    Route::resource('commandes', \App\Http\Controllers\Admin\OrderController::class)->except(['create', 'store']);

    // ── Factures (spécifiques AVANT resource) ─────────────────────
    Route::get('factures/{invoice}/pdf',       [\App\Http\Controllers\Admin\InvoiceController::class, 'pdf'])->name('factures.pdf');
    Route::post('factures/{invoice}/envoyer',  [\App\Http\Controllers\Admin\InvoiceController::class, 'send'])->name('factures.send');
    Route::patch('factures/{invoice}/payer',   [\App\Http\Controllers\Admin\InvoiceController::class, 'markPaid'])->name('factures.paid');
    Route::post('factures/{invoice}/paiement', [\App\Http\Controllers\Admin\InvoiceController::class, 'addPayment'])->name('factures.payment');
    Route::resource('factures', \App\Http\Controllers\Admin\InvoiceController::class);

    // ── Paiements ─────────────────────────────────────────────────
    Route::get('paiements',           [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('paiements.index');
    Route::get('paiements/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('paiements.show');

    // ── Coupons (spécifique AVANT resource) ───────────────────────
    Route::patch('coupons/{coupon}/toggle', [\App\Http\Controllers\Admin\CouponController::class, 'toggle'])->name('coupons.toggle');
    Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);

    // ── Avis (spécifiques AVANT resource) ─────────────────────────
    Route::patch('avis/{review}/approuver',       [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('avis.approve');
    Route::patch('avis/{review}/mettre-en-avant', [\App\Http\Controllers\Admin\ReviewController::class, 'feature'])->name('avis.feature');
    Route::post('avis/{review}/repondre',         [\App\Http\Controllers\Admin\ReviewController::class, 'reply'])->name('avis.reply');
    Route::resource('avis', \App\Http\Controllers\Admin\ReviewController::class)->except(['create', 'store']);

    // ── Galerie (statique + spécifiques AVANT resource) ───────────
    Route::post('galerie/ordre',                           [\App\Http\Controllers\Admin\GalleryController::class, 'reorder'])->name('galerie.reorder');
    Route::patch('galerie/{galleryImage}/toggle',          [\App\Http\Controllers\Admin\GalleryController::class, 'toggle'])->name('galerie.toggle');
    Route::patch('galerie/{galleryImage}/mettre-en-avant', [\App\Http\Controllers\Admin\GalleryController::class, 'feature'])->name('galerie.feature');
    Route::resource('galerie', \App\Http\Controllers\Admin\GalleryController::class);

    // ── Rapports (statiques AVANT index) ──────────────────────────
    Route::get('rapports/ventes',        [\App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('rapports.sales');
    Route::get('rapports/rendez-vous',   [\App\Http\Controllers\Admin\ReportController::class, 'appointments'])->name('rapports.appointments');
    Route::get('rapports/clients',       [\App\Http\Controllers\Admin\ReportController::class, 'clients'])->name('rapports.clients');
    Route::get('rapports/produits',      [\App\Http\Controllers\Admin\ReportController::class, 'products'])->name('rapports.products');
    Route::get('rapports/export/{type}', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('rapports.export');
    Route::get('rapports',               [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('rapports.index');

    // ── Paramètres ────────────────────────────────────────────────
    Route::patch('parametres/{group}', [\App\Http\Controllers\Admin\SettingController::class, 'updateGroup'])->name('parametres.group');
    Route::patch('parametres',         [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('parametres.update');
    Route::get('parametres',           [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('parametres.index');

    // ── Notifications (tout-lire AVANT {id}) ──────────────────────
    Route::patch('notifications/tout-lire',    [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('notifications',                [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{id}/lire',    [\App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('notifications/{id}',        [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// ══════════════════════════════════════════════════════════════════════
// ── VITRINE PUBLIQUE ──────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════════
Route::get('/', [\App\Http\Controllers\Public\HomeController::class, 'index'])->name('home');

// ── Services & Réservation ────────────────────────────────────────────
Route::get('/services',                             [\App\Http\Controllers\Public\BookingController::class, 'services'])->name('booking.services');
Route::get('/reservation/{service:slug}',           [\App\Http\Controllers\Public\BookingController::class, 'show'])->name('booking.show');
Route::post('/reservation/{service:slug}/creneaux', [\App\Http\Controllers\Public\BookingController::class, 'slots'])->name('booking.slots');
Route::post('/reservation/{service:slug}/reserver', [\App\Http\Controllers\Public\BookingController::class, 'store'])->name('booking.store');

// ── Boutique ──────────────────────────────────────────────────────────
Route::get('/boutique',                [\App\Http\Controllers\Public\ShopController::class, 'index'])->name('shop.index');
Route::get('/boutique/{product:slug}', [\App\Http\Controllers\Public\ProductController::class, 'show'])->name('shop.product');

// ── Galerie ───────────────────────────────────────────────────────────
Route::get('/galerie', [\App\Http\Controllers\Public\GalleryController::class, 'index'])->name('gallery.index');

// ── Contact ───────────────────────────────────────────────────────────
Route::get('/contact',  [\App\Http\Controllers\Public\ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [\App\Http\Controllers\Public\ContactController::class, 'send'])->name('contact.send');

// ── Blog ─────────────────────────────────────────────────────────────
// ⚠️ /blog/categorie/{slug} AVANT /blog/{slug} pour éviter le conflit
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/',                    [\App\Http\Controllers\Public\BlogController::class, 'index'])->name('index');
    Route::get('/categorie/{slug}',    [\App\Http\Controllers\Public\BlogController::class, 'category'])->name('category');
    Route::get('/{slug}',             [\App\Http\Controllers\Public\BlogController::class, 'show'])->name('show');
});

// ── Paiement — résultats (sans auth, retour PayPal/Stripe) ───────────
// ⚠️ Routes statiques AVANT le groupe auth contenant {type}/{id}
Route::get('/paiement/{type}/{id}/succes',          [\App\Http\Controllers\Public\PaymentController::class, 'success'])->name('payment.success');
Route::get('/paiement/{type}/{id}/annule',          [\App\Http\Controllers\Public\PaymentController::class, 'cancel'])->name('payment.cancel');
Route::get('/paiement/paypal/capture/{type}/{id}',  [\App\Http\Controllers\Public\PaymentController::class, 'paypalCapture'])->name('payment.paypal.capture');

// ══════════════════════════════════════════════════════════════════════
// ── ZONE PROTÉGÉE (auth requis) ───────────────────────────────────────
// ══════════════════════════════════════════════════════════════════════
Route::middleware('auth')->group(function () {

    // ── Espace client ─────────────────────────────────────────────
    Route::prefix('mon-espace')->name('account.')->group(function () {
        Route::get('/',                       [\App\Http\Controllers\Public\AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/commandes',              [\App\Http\Controllers\Public\AccountController::class, 'orders'])->name('orders');
        Route::get('/commandes/{order}',      [\App\Http\Controllers\Public\AccountController::class, 'orderShow'])->name('order');
        Route::get('/rendez-vous',            [\App\Http\Controllers\Public\AccountController::class, 'appointments'])->name('appointments');
        Route::get('/factures',               [\App\Http\Controllers\Public\AccountController::class, 'invoices'])->name('invoices');
        Route::get('/factures/{invoice}/pdf', [\App\Http\Controllers\Public\AccountController::class, 'downloadInvoice'])->name('invoice.pdf');
    });

    // ── Paiement — actions Stripe/PayPal ──────────────────────────
    // ⚠️ Routes statiques AVANT la route paramétrique {type}/{id}
    Route::post('/paiement/stripe/intent',  [\App\Http\Controllers\Public\PaymentController::class, 'stripeCreateIntent'])->name('payment.stripe.intent');
    Route::post('/paiement/stripe/confirm', [\App\Http\Controllers\Public\PaymentController::class, 'stripeConfirm'])->name('payment.stripe.confirm');
    Route::post('/paiement/paypal/create',  [\App\Http\Controllers\Public\PaymentController::class, 'paypalCreate'])->name('payment.paypal.create');
    Route::get('/paiement/{type}/{id}',     [\App\Http\Controllers\Public\PaymentController::class, 'show'])->name('payment.show');

    // ── Panier ────────────────────────────────────────────────────
    Route::post('/panier/ajouter',  [\App\Http\Controllers\Public\CartController::class, 'add'])->name('cart.add');
    Route::post('/panier/coupon',   [\App\Http\Controllers\Public\CartController::class, 'applyCoupon'])->name('cart.coupon');
    Route::patch('/panier/{item}',  [\App\Http\Controllers\Public\CartController::class, 'update'])->name('cart.update');
    Route::delete('/panier/{item}', [\App\Http\Controllers\Public\CartController::class, 'remove'])->name('cart.remove');
    Route::get('/panier',           [\App\Http\Controllers\Public\CartController::class, 'index'])->name('cart.index');

    // ── Commande / Checkout ───────────────────────────────────────
    Route::get('/commande/confirmation/{order}', [\App\Http\Controllers\Public\CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
    Route::post('/commande',                     [\App\Http\Controllers\Public\CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/commande',                      [\App\Http\Controllers\Public\CheckoutController::class, 'index'])->name('checkout.index');
});