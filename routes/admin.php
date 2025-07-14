<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes pour la zone d'administration (Zone Membre 1)
| Toutes les routes sont protégées par le middleware 'admin'
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart_data');
    
    // Profile Admin
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AdminController::class, 'updatePassword'])->name('profile.password');
    
    // Gestion des produits
    Route::resource('products', ProductController::class);
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk_delete');
    Route::put('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle_status');
    Route::get('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    
    // Gestion des catégories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [ProductController::class, 'categories'])->name('index');
        Route::post('/', [ProductController::class, 'storeCategory'])->name('store');
        Route::put('/{category}', [ProductController::class, 'updateCategory'])->name('update');
        Route::delete('/{category}', [ProductController::class, 'destroyCategory'])->name('destroy');
    });
    
    // Gestion des commandes
    Route::resource('orders', OrderController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update_status');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'generateInvoice'])->name('orders.invoice');
    Route::post('/orders/{order}/send-invoice', [OrderController::class, 'sendInvoice'])->name('orders.send_invoice');
    Route::get('/orders/{order}/tracking', [OrderController::class, 'tracking'])->name('orders.tracking');
    Route::put('/orders/{order}/tracking', [OrderController::class, 'updateTracking'])->name('orders.update_tracking');
    
    // Filtres et recherche des commandes
    Route::get('/orders/filter/status/{status}', [OrderController::class, 'filterByStatus'])->name('orders.filter.status');
    Route::get('/orders/filter/date/{date}', [OrderController::class, 'filterByDate'])->name('orders.filter.date');
    Route::get('/orders/search', [OrderController::class, 'search'])->name('orders.search');
    
    // Rapports de commandes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/orders', [OrderController::class, 'ordersReport'])->name('orders');
        Route::get('/orders/export', [OrderController::class, 'exportOrdersReport'])->name('orders.export');
        Route::get('/sales', [OrderController::class, 'salesReport'])->name('sales');
        Route::get('/sales/export', [OrderController::class, 'exportSalesReport'])->name('sales.export');
    });
    
    // Gestion des utilisateurs
    Route::resource('users', UserController::class);
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle_status');
    Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update_role');
    Route::post('/users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk_action');
    
    // Filtres utilisateurs
    Route::get('/users/filter/role/{role}', [UserController::class, 'filterByRole'])->name('users.filter.role');
    Route::get('/users/filter/status/{status}', [UserController::class, 'filterByStatus'])->name('users.filter.status');
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    
    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminController::class, 'notifications'])->name('index');
        Route::get('/check', [AdminController::class, 'checkNotifications'])->name('check');
        Route::put('/{notification}/read', [AdminController::class, 'markAsRead'])->name('read');
        Route::put('/read-all', [AdminController::class, 'markAllAsRead'])->name('read_all');
        Route::delete('/{notification}', [AdminController::class, 'deleteNotification'])->name('delete');
    });
    
    // Paramètres système
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AdminController::class, 'settings'])->name('index');
        Route::put('/general', [AdminController::class, 'updateGeneralSettings'])->name('general');
        Route::put('/email', [AdminController::class, 'updateEmailSettings'])->name('email');
        Route::put('/payment', [AdminController::class, 'updatePaymentSettings'])->name('payment');
        Route::put('/shipping', [AdminController::class, 'updateShippingSettings'])->name('shipping');
    });
    
    // Gestion des médias
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [AdminController::class, 'mediaLibrary'])->name('index');
        Route::post('/upload', [AdminController::class, 'uploadMedia'])->name('upload');
        Route::delete('/{media}', [AdminController::class, 'deleteMedia'])->name('delete');
        Route::get('/search', [AdminController::class, 'searchMedia'])->name('search');
    });
    
    // Logs et activités
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [AdminController::class, 'logs'])->name('index');
        Route::get('/activity', [AdminController::class, 'activityLog'])->name('activity');
        Route::get('/errors', [AdminController::class, 'errorLog'])->name('errors');
        Route::delete('/clear', [AdminController::class, 'clearLogs'])->name('clear');
    });
    
    // Sauvegarde et maintenance
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [AdminController::class, 'maintenance'])->name('index');
        Route::post('/backup', [AdminController::class, 'createBackup'])->name('backup');
        Route::get('/backup/download/{backup}', [AdminController::class, 'downloadBackup'])->name('backup.download');
        Route::delete('/backup/{backup}', [AdminController::class, 'deleteBackup'])->name('backup.delete');
        Route::post('/cache/clear', [AdminController::class, 'clearCache'])->name('cache.clear');
        Route::post('/optimize', [AdminController::class, 'optimize'])->name('optimize');
    });
    
    // API interne pour l'administration
    Route::prefix('api')->name('api.')->group(function () {
        // Statistiques en temps réel
        Route::get('/stats/realtime', [DashboardController::class, 'realtimeStats'])->name('stats.realtime');
        Route::get('/stats/period', [DashboardController::class, 'periodStats'])->name('stats.period');
        
        // Recherche globale
        Route::get('/search', [AdminController::class, 'globalSearch'])->name('search');
        
        // Vérifications système
        Route::get('/system/health', [AdminController::class, 'systemHealth'])->name('system.health');
        Route::get('/system/info', [AdminController::class, 'systemInfo'])->name('system.info');
        
        // Gestion des sessions
        Route::get('/sessions', [AdminController::class, 'activeSessions'])->name('sessions');
        Route::delete('/sessions/{session}', [AdminController::class, 'destroySession'])->name('sessions.destroy');
    });
    
    // Routes pour les webhooks et callbacks externes
Route::prefix('admin/webhooks')->name('admin.webhooks.')->group(function () {
    // Webhooks de paiement
    Route::post('/payment/stripe', [AdminController::class, 'stripeWebhook'])->name('payment.stripe');
    Route::post('/payment/paypal', [AdminController::class, 'paypalWebhook'])->name('payment.paypal');
    
    // Webhooks de livraison
    Route::post('/shipping/tracking', [AdminController::class, 'shippingWebhook'])->name('shipping.tracking');
    
    // Webhooks d'email
    Route::post('/email/status', [AdminController::class, 'emailWebhook'])->name('email.status');
});

// Routes d'export et d'import
Route::middleware(['auth', 'admin'])->prefix('admin/export')->name('admin.export.')->group(function () {
    Route::get('/products', [ProductController::class, 'exportProducts'])->name('products');
    Route::get('/orders', [OrderController::class, 'exportOrders'])->name('orders');
    Route::get('/users', [UserController::class, 'exportUsers'])->name('users');
    Route::get('/reports/sales', [OrderController::class, 'exportSalesReport'])->name('reports.sales');
    Route::get('/reports/inventory', [ProductController::class, 'exportInventoryReport'])->name('reports.inventory');
});

Route::middleware(['auth', 'admin'])->prefix('admin/import')->name('admin.import.')->group(function () {
    Route::post('/products', [ProductController::class, 'importProducts'])->name('products');
    Route::post('/users', [UserController::class, 'importUsers'])->name('users');
    Route::get('/template/{type}', [AdminController::class, 'downloadTemplate'])->name('template');
});

// Routes pour les actions en lot
Route::middleware(['auth', 'admin'])->prefix('admin/bulk')->name('admin.bulk.')->group(function () {
    Route::post('/products/update-category', [ProductController::class, 'bulkUpdateCategory'])->name('products.update_category');
    Route::post('/products/update-price', [ProductController::class, 'bulkUpdatePrice'])->name('products.update_price');
    Route::post('/products/update-stock', [ProductController::class, 'bulkUpdateStock'])->name('products.update_stock');
    Route::post('/orders/update-status', [OrderController::class, 'bulkUpdateStatus'])->name('orders.update_status');
    Route::post('/users/update-role', [UserController::class, 'bulkUpdateRole'])->name('users.update_role');
});

// Routes pour les tâches planifiées et automatisation
Route::middleware(['auth', 'admin'])->prefix('admin/automation')->name('admin.automation.')->group(function () {
    Route::get('/', [AdminController::class, 'automation'])->name('index');
    Route::post('/schedule-task', [AdminController::class, 'scheduleTask'])->name('schedule_task');
    Route::delete('/task/{task}', [AdminController::class, 'deleteTask'])->name('delete_task');
    Route::put('/task/{task}/toggle', [AdminController::class, 'toggleTask'])->name('toggle_task');
    
    // Règles automatiques
    Route::get('/rules', [AdminController::class, 'automationRules'])->name('rules');
    Route::post('/rules', [AdminController::class, 'createRule'])->name('rules.create');
    Route::put('/rules/{rule}', [AdminController::class, 'updateRule'])->name('rules.update');
    Route::delete('/rules/{rule}', [AdminController::class, 'deleteRule'])->name('rules.delete');
});

// Routes pour les communications
Route::middleware(['auth', 'admin'])->prefix('admin/communications')->name('admin.communications.')->group(function () {
    Route::get('/emails', [AdminController::class, 'emailTemplates'])->name('emails');
    Route::get('/emails/create', [AdminController::class, 'createEmailTemplate'])->name('emails.create');
    Route::post('/emails', [AdminController::class, 'storeEmailTemplate'])->name('emails.store');
    Route::get('/emails/{template}/edit', [AdminController::class, 'editEmailTemplate'])->name('emails.edit');
    Route::put('/emails/{template}', [AdminController::class, 'updateEmailTemplate'])->name('emails.update');
    Route::delete('/emails/{template}', [AdminController::class, 'deleteEmailTemplate'])->name('emails.delete');
    
    // Envoi d'emails en masse
    Route::get('/broadcast', [AdminController::class, 'broadcastForm'])->name('broadcast');
    Route::post('/broadcast', [AdminController::class, 'sendBroadcast'])->name('broadcast.send');
    Route::get('/broadcast/history', [AdminController::class, 'broadcastHistory'])->name('broadcast.history');
});

// Routes pour les analyses et statistiques avancées
Route::middleware(['auth', 'admin'])->prefix('admin/analytics')->name('admin.analytics.')->group(function () {
    Route::get('/', [AdminController::class, 'analytics'])->name('index');
    Route::get('/sales', [AdminController::class, 'salesAnalytics'])->name('sales');
    Route::get('/customers', [AdminController::class, 'customerAnalytics'])->name('customers');
    Route::get('/products', [AdminController::class, 'productAnalytics'])->name('products');
    Route::get('/traffic', [AdminController::class, 'trafficAnalytics'])->name('traffic');
    
    // API pour les données analytiques
    Route::get('/api/sales-data', [AdminController::class, 'getSalesData'])->name('api.sales_data');
    Route::get('/api/customer-data', [AdminController::class, 'getCustomerData'])->name('api.customer_data');
    Route::get('/api/product-data', [AdminController::class, 'getProductData'])->name('api.product_data');
});

// Routes pour la gestion des promotions et coupons
Route::middleware(['auth', 'admin'])->prefix('admin/promotions')->name('admin.promotions.')->group(function () {
    Route::get('/', [AdminController::class, 'promotions'])->name('index');
    Route::get('/create', [AdminController::class, 'createPromotion'])->name('create');
    Route::post('/', [AdminController::class, 'storePromotion'])->name('store');
    Route::get('/{promotion}/edit', [AdminController::class, 'editPromotion'])->name('edit');
    Route::put('/{promotion}', [AdminController::class, 'updatePromotion'])->name('update');
    Route::delete('/{promotion}', [AdminController::class, 'deletePromotion'])->name('delete');
    Route::put('/{promotion}/toggle', [AdminController::class, 'togglePromotion'])->name('toggle');
    
    // Coupons
    Route::get('/coupons', [AdminController::class, 'coupons'])->name('coupons');
    Route::post('/coupons', [AdminController::class, 'createCoupon'])->name('coupons.create');
    Route::get('/coupons/generate', [AdminController::class, 'generateCoupons'])->name('coupons.generate');
    Route::delete('/coupons/{coupon}', [AdminController::class, 'deleteCoupon'])->name('coupons.delete');
});

// Routes pour la gestion des stocks et inventaire
Route::middleware(['auth', 'admin'])->prefix('admin/inventory')->name('admin.inventory.')->group(function () {
    Route::get('/', [AdminController::class, 'inventory'])->name('index');
    Route::get('/low-stock', [AdminController::class, 'lowStock'])->name('low_stock');
    Route::get('/out-of-stock', [AdminController::class, 'outOfStock'])->name('out_of_stock');
    Route::get('/movements', [AdminController::class, 'stockMovements'])->name('movements');
    
    // Ajustements de stock
    Route::post('/adjust', [AdminController::class, 'adjustStock'])->name('adjust');
    Route::get('/adjust/history', [AdminController::class, 'adjustmentHistory'])->name('adjust.history');
    
    // Alertes de stock
    Route::get('/alerts', [AdminController::class, 'stockAlerts'])->name('alerts');
    Route::post('/alerts', [AdminController::class, 'createStockAlert'])->name('alerts.create');
    Route::delete('/alerts/{alert}', [AdminController::class, 'deleteStockAlert'])->name('alerts.delete');
});

// Routes pour la gestion des fournisseurs
Route::middleware(['auth', 'admin'])->prefix('admin/suppliers')->name('admin.suppliers.')->group(function () {
    Route::get('/', [AdminController::class, 'suppliers'])->name('index');
    Route::get('/create', [AdminController::class, 'createSupplier'])->name('create');
    Route::post('/', [AdminController::class, 'storeSupplier'])->name('store');
    Route::get('/{supplier}', [AdminController::class, 'showSupplier'])->name('show');
    Route::get('/{supplier}/edit', [AdminController::class, 'editSupplier'])->name('edit');
    Route::put('/{supplier}', [AdminController::class, 'updateSupplier'])->name('update');
    Route::delete('/{supplier}', [AdminController::class, 'deleteSupplier'])->name('delete');
    
    // Commandes fournisseurs
    Route::get('/{supplier}/orders', [AdminController::class, 'supplierOrders'])->name('orders');
    Route::post('/{supplier}/orders', [AdminController::class, 'createSupplierOrder'])->name('orders.create');
});

// Routes pour la gestion des attributs produits
Route::middleware(['auth', 'admin'])->prefix('admin/attributes')->name('admin.attributes.')->group(function () {
    Route::get('/', [AdminController::class, 'attributes'])->name('index');
    Route::post('/', [AdminController::class, 'createAttribute'])->name('create');
    Route::put('/{attribute}', [AdminController::class, 'updateAttribute'])->name('update');
    Route::delete('/{attribute}', [AdminController::class, 'deleteAttribute'])->name('delete');
    
    // Valeurs d'attributs
    Route::post('/{attribute}/values', [AdminController::class, 'createAttributeValue'])->name('values.create');
    Route::put('/values/{value}', [AdminController::class, 'updateAttributeValue'])->name('values.update');
    Route::delete('/values/{value}', [AdminController::class, 'deleteAttributeValue'])->name('values.delete');
});

// Routes pour la gestion des taxes
Route::middleware(['auth', 'admin'])->prefix('admin/taxes')->name('admin.taxes.')->group(function () {
    Route::get('/', [AdminController::class, 'taxes'])->name('index');
    Route::post('/', [AdminController::class, 'createTax'])->name('create');
    Route::put('/{tax}', [AdminController::class, 'updateTax'])->name('update');
    Route::delete('/{tax}', [AdminController::class, 'deleteTax'])->name('delete');
    
    // Zones de taxe
    Route::get('/zones', [AdminController::class, 'taxZones'])->name('zones');
    Route::post('/zones', [AdminController::class, 'createTaxZone'])->name('zones.create');
    Route::put('/zones/{zone}', [AdminController::class, 'updateTaxZone'])->name('zones.update');
    Route::delete('/zones/{zone}', [AdminController::class, 'deleteTaxZone'])->name('zones.delete');
});

// Routes pour la gestion des zones et frais de livraison
Route::middleware(['auth', 'admin'])->prefix('admin/shipping')->name('admin.shipping.')->group(function () {
    Route::get('/', [AdminController::class, 'shipping'])->name('index');
    Route::get('/zones', [AdminController::class, 'shippingZones'])->name('zones');
    Route::post('/zones', [AdminController::class, 'createShippingZone'])->name('zones.create');
    Route::put('/zones/{zone}', [AdminController::class, 'updateShippingZone'])->name('zones.update');
    Route::delete('/zones/{zone}', [AdminController::class, 'deleteShippingZone'])->name('zones.delete');
    
    // Méthodes de livraison
    Route::get('/methods', [AdminController::class, 'shippingMethods'])->name('methods');
    Route::post('/methods', [AdminController::class, 'createShippingMethod'])->name('methods.create');
    Route::put('/methods/{method}', [AdminController::class, 'updateShippingMethod'])->name('methods.update');
    Route::delete('/methods/{method}', [AdminController::class, 'deleteShippingMethod'])->name('methods.delete');
});

// Routes pour la gestion des pages CMS
Route::middleware(['auth', 'admin'])->prefix('admin/cms')->name('admin.cms.')->group(function () {
    Route::get('/pages', [AdminController::class, 'pages'])->name('pages');
    Route::get('/pages/create', [AdminController::class, 'createPage'])->name('pages.create');
    Route::post('/pages', [AdminController::class, 'storePage'])->name('pages.store');
    Route::get('/pages/{page}/edit', [AdminController::class, 'editPage'])->name('pages.edit');
    Route::put('/pages/{page}', [AdminController::class, 'updatePage'])->name('pages.update');
    Route::delete('/pages/{page}', [AdminController::class, 'deletePage'])->name('pages.delete');
    
    // Menus
    Route::get('/menus', [AdminController::class, 'menus'])->name('menus');
    Route::post('/menus', [AdminController::class, 'createMenu'])->name('menus.create');
    Route::put('/menus/{menu}', [AdminController::class, 'updateMenu'])->name('menus.update');
    Route::delete('/menus/{menu}', [AdminController::class, 'deleteMenu'])->name('menus.delete');
    
    // Sliders
    Route::get('/sliders', [AdminController::class, 'sliders'])->name('sliders');
    Route::post('/sliders', [AdminController::class, 'createSlider'])->name('sliders.create');
    Route::put('/sliders/{slider}', [AdminController::class, 'updateSlider'])->name('sliders.update');
    Route::delete('/sliders/{slider}', [AdminController::class, 'deleteSlider'])->name('sliders.delete');
});

// Routes pour la gestion SEO
Route::middleware(['auth', 'admin'])->prefix('admin/seo')->name('admin.seo.')->group(function () {
    Route::get('/', [AdminController::class, 'seo'])->name('index');
    Route::get('/meta-tags', [AdminController::class, 'metaTags'])->name('meta_tags');
    Route::post('/meta-tags', [AdminController::class, 'updateMetaTags'])->name('meta_tags.update');
    Route::get('/redirects', [AdminController::class, 'redirects'])->name('redirects');
    Route::post('/redirects', [AdminController::class, 'createRedirect'])->name('redirects.create');
    Route::delete('/redirects/{redirect}', [AdminController::class, 'deleteRedirect'])->name('redirects.delete');
    Route::get('/sitemap', [AdminController::class, 'sitemap'])->name('sitemap');
    Route::post('/sitemap/generate', [AdminController::class, 'generateSitemap'])->name('sitemap.generate');
});

// Routes pour les intégrations tierces
Route::middleware(['auth', 'admin'])->prefix('admin/integrations')->name('admin.integrations.')->group(function () {
    Route::get('/', [AdminController::class, 'integrations'])->name('index');
    Route::post('/google-analytics', [AdminController::class, 'setupGoogleAnalytics'])->name('google_analytics');
    Route::post('/facebook-pixel', [AdminController::class, 'setupFacebookPixel'])->name('facebook_pixel');
    Route::post('/mailchimp', [AdminController::class, 'setupMailchimp'])->name('mailchimp');
    Route::post('/stripe', [AdminController::class, 'setupStripe'])->name('stripe');
    Route::post('/paypal', [AdminController::class, 'setupPaypal'])->name('paypal');
});

// Route de fallback pour les erreurs 404 dans l'admin
Route::fallback(function () {
    return response()->view('admin.errors.404', [], 404);
})->middleware(['auth', 'admin']);

/*
|--------------------------------------------------------------------------
| Notes sur la sécurité
|--------------------------------------------------------------------------
|
| 1. Toutes les routes admin sont protégées par les middlewares 'auth' et 'admin'
| 2. Les tokens CSRF sont requis pour toutes les actions de modification
| 3. Les webhooks utilisent des signatures pour vérifier l'authenticité
| 4. Les exports sont limités aux administrateurs authentifiés
| 5. Les routes de développement ne sont disponibles qu'en local
|
*/ //pour les tests (uniquement en développement)
    if (app()->environment('local')) {
        Route::prefix('dev')->name('dev.')->group(function () {
            Route::get('/test-email', [AdminController::class, 'testEmail'])->name('test.email');
            Route::get('/test-notification', [AdminController::class, 'testNotification'])->name('test.notification');
            Route::get('/generate-fake-data', [AdminController::class, 'generateFakeData'])->name('generate.fake');
        });
    }
});

// Routes publiques liées à l'administration
Route::prefix('admin')->name('admin.')->group(function () {
    // Page de connexion admin
    Route::get('/login', [AdminController::class, 'loginForm'])->name('login.form');
    Route::post('/login', [AdminController::class, 'login'])->name('login');
    
    // Mot de passe oublié
    Route::get('/password/reset', [AdminController::class, 'passwordResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [AdminController::class, 'passwordReset'])->name('password.reset');
    
    // Déconnexion
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});

// Routes