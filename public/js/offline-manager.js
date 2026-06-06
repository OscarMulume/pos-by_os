/**
 * POS Pro — Offline Manager
 * Gestion du mode hors-ligne avec IndexedDB + synchronisation automatique
 */
const OfflineDB = {
    DB_NAME: 'pos_offline_db',
    DB_VERSION: 1,
    db: null,

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onupgradeneeded = (e) => {
                const db = e.target.result;

                // Store pour les commandes en attente
                if (!db.objectStoreNames.contains('orders')) {
                    const orderStore = db.createObjectStore('orders', { keyPath: 'local_id', autoIncrement: true });
                    orderStore.createIndex('synced', 'synced', { unique: false });
                    orderStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Store pour le cache des produits
                if (!db.objectStoreNames.contains('products')) {
                    db.createObjectStore('products', { keyPath: 'id' });
                }

                // Store pour le cache des catégories
                if (!db.objectStoreNames.contains('categories')) {
                    db.createObjectStore('categories', { keyPath: 'id' });
                }

                // Store pour le cache des tables
                if (!db.objectStoreNames.contains('tables')) {
                    db.createObjectStore('tables', { keyPath: 'id' });
                }

                // Store pour la licence
                if (!db.objectStoreNames.contains('license')) {
                    db.createObjectStore('license', { keyPath: 'key' });
                }
            };

            request.onsuccess = (e) => {
                this.db = e.target.result;
                resolve(this.db);
            };

            request.onerror = (e) => reject(e.target.error);
        });
    },

    // Sauvegarder une commande hors-ligne
    async saveOrder(orderData) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('orders', 'readwrite');
            const store = tx.objectStore('orders');
            const data = {
                ...orderData,
                synced: false,
                created_at: new Date().toISOString(),
                retry_count: 0
            };
            const req = store.add(data);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },

    // Récupérer les commandes non synchronisées
    async getUnsyncedOrders() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('orders', 'readonly');
            const store = tx.objectStore('orders');
            const index = store.index('synced');
            const req = index.getAll(false);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },

    // Marquer une commande comme synchronisée
    async markOrderSynced(localId, serverId) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('orders', 'readwrite');
            const store = tx.objectStore('orders');
            const req = store.get(localId);
            req.onsuccess = () => {
                const data = req.result;
                if (data) {
                    data.synced = true;
                    data.server_id = serverId;
                    data.synced_at = new Date().toISOString();
                    store.put(data);
                }
                resolve();
            };
            req.onerror = () => reject(req.error);
        });
    },

    // Supprimer une commande synchronisée
    async deleteOrder(localId) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('orders', 'readwrite');
            const store = tx.objectStore('orders');
            const req = store.delete(localId);
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    },

    // Compter les commandes en attente
    async countUnsynced() {
        const orders = await this.getUnsyncedOrders();
        return orders.length;
    },

    // Cache des produits
    async cacheProducts(products) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('products', 'readwrite');
            const store = tx.objectStore('products');
            products.forEach(p => store.put(p));
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    // Récupérer les produits en cache
    async getCachedProducts() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('products', 'readonly');
            const store = tx.objectStore('products');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },

    // Cache des catégories
    async cacheCategories(categories) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('categories', 'readwrite');
            const store = tx.objectStore('categories');
            categories.forEach(c => store.put(c));
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    async getCachedCategories() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('categories', 'readonly');
            const store = tx.objectStore('categories');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },

    // Cache des tables
    async cacheTables(tables) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('tables', 'readwrite');
            const store = tx.objectStore('tables');
            tables.forEach(t => store.put(t));
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    async getCachedTables() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('tables', 'readonly');
            const store = tx.objectStore('tables');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },

    // Stockage de la licence
    async saveLicense(licenseData) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('license', 'readwrite');
            const store = tx.objectStore('license');
            store.put({ key: 'current', ...licenseData, saved_at: new Date().toISOString() });
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    async getLicense() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('license', 'readonly');
            const store = tx.objectStore('license');
            const req = store.get('current');
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    },
};

/**
 * Gestionnaire de synchronisation
 */
const SyncManager = {
    isSyncing: false,

    // Synchroniser les commandes en attente
    async syncOrders() {
        if (this.isSyncing || !navigator.onLine) return;
        this.isSyncing = true;

        try {
            const unsynced = await OfflineDB.getUnsyncedOrders();
            for (const order of unsynced) {
                try {
                    const response = await fetch('/pos/order', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify(order.data)
                    });

                    if (response.ok) {
                        const result = await response.json();
                        if (result.success) {
                            await OfflineDB.markOrderSynced(order.local_id, result.order_id);
                            // Supprimer après 24h
                            setTimeout(() => OfflineDB.deleteOrder(order.local_id), 86400000);
                        }
                    }
                } catch (e) {
                    console.error('Sync error for order', order.local_id, e);
                    // Incrémenter le compteur de retry
                    order.retry_count = (order.retry_count || 0) + 1;
                }
            }
        } finally {
            this.isSyncing = false;
        }
    },

    // Démarrer la synchronisation périodique
    startPeriodicSync() {
        // Sync toutes les 30 secondes si en ligne
        setInterval(() => {
            if (navigator.onLine) this.syncOrders();
        }, 30000);

        // Sync dès qu'on revient en ligne
        window.addEventListener('online', () => {
            console.log('Connexion rétablie — synchronisation...');
            this.syncOrders();
        });
    }
};

/**
 * Gestionnaire de licence hors-ligne
 */
const LicenseManager = {
    GRACE_PERIOD_DAYS: 7,

    // Vérifier la licence (fonctionne hors-ligne)
    async verify() {
        // D'abord essayer en ligne
        if (navigator.onLine) {
            try {
                const resp = await fetch('/api/license/verify', {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.valid) {
                        await OfflineDB.saveLicense(data.license);
                        return { valid: true, license: data.license };
                    }
                }
            } catch (e) {
                console.warn('License online check failed, falling back to offline');
            }
        }

        // Fallback : vérifier le jeton stocké localement
        const stored = await OfflineDB.getLicense();
        if (!stored || !stored.token) {
            return { valid: false, reason: 'Aucune licence trouvée' };
        }

        // Vérifier le jeton JWT
        const result = this.verifyToken(stored.token);
        if (!result.valid) {
            // Vérifier la période de grâce
            if (stored.offline_activated_at) {
                const offlineSince = new Date(stored.offline_activated_at);
                const daysOffline = (new Date() - offlineSince) / (1000 * 60 * 60 * 24);
                if (daysOffline > this.GRACE_PERIOD_DAYS) {
                    return { valid: false, reason: 'Période de grâce expirée. Connexion requise.' };
                }
                return { valid: true, license: stored, grace: true, daysLeft: Math.ceil(this.GRACE_PERIOD_DAYS - daysOffline) };
            }
            return result;
        }

        // Activer le mode hors-ligne si pas encore fait
        if (!stored.offline_activated_at) {
            stored.offline_activated_at = new Date().toISOString();
            await OfflineDB.saveLicense(stored);
        }

        return { valid: true, license: stored };
    },

    // Vérifier un jeton JWT signé
    verifyToken(token) {
        const parts = token.split('.');
        if (parts.length !== 2) return { valid: false, reason: 'Format invalide' };

        try {
            const payload = JSON.parse(atob(parts[0]));
            // Note: la vérification de signature côté client est limitée
            // La vraie vérification se fait côté serveur
            if (payload.exp && payload.exp < Date.now() / 1000) {
                return { valid: false, reason: 'Licence expirée' };
            }
            return { valid: true, data: payload };
        } catch (e) {
            return { valid: false, reason: 'Token invalide' };
        }
    }
};

// Initialisation automatique
document.addEventListener('DOMContentLoaded', async () => {
    await OfflineDB.init();
    SyncManager.startPeriodicSync();

    // Vérifier la licence au chargement
    const licenseCheck = await LicenseManager.verifyLocally();
    if (!licenseCheck.valid) {
        console.warn('Licence invalide:', licenseCheck.reason);
        // Afficher un avertissement mais ne pas bloquer immédiatement
        if (licenseCheck.grace_expired || licenseCheck.expired) {
            // Bloquer l'interface de vente
            document.body.classList.add('license-blocked');
        }
    }
});

// Enregistrer le service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW registered:', reg.scope))
            .catch(err => console.log('SW registration failed:', err));
    });
}
