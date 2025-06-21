/**
 * Storage Management Module
 * Handles localStorage, sessionStorage, IndexedDB, and cloud storage
 */

class StorageManager {
    constructor() {
        this.dbName = 'CarGoDB';
        this.dbVersion = 1;
        this.db = null;
        this.isOnline = navigator.onLine;
        this.syncQueue = [];
        this.init();
    }

    async init() {
        await this.initIndexedDB();
        this.setupEventListeners();
        this.startSyncProcess();
    }

    // IndexedDB Management
    async initIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => {
                console.error('IndexedDB initialization failed');
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('IndexedDB initialized successfully');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                this.createObjectStores(db);
            };
        });
    }

    createObjectStores(db) {
        // Users store
        if (!db.objectStoreNames.contains('users')) {
            const userStore = db.createObjectStore('users', { keyPath: 'id' });
            userStore.createIndex('email', 'email', { unique: true });
        }

        // Bookings store
        if (!db.objectStoreNames.contains('bookings')) {
            const bookingStore = db.createObjectStore('bookings', { keyPath: 'id' });
            bookingStore.createIndex('userId', 'userId', { unique: false });
            bookingStore.createIndex('status', 'status', { unique: false });
            bookingStore.createIndex('date', 'startDate', { unique: false });
        }

        // Vehicles store
        if (!db.objectStoreNames.contains('vehicles')) {
            const vehicleStore = db.createObjectStore('vehicles', { keyPath: 'id' });
            vehicleStore.createIndex('category', 'category', { unique: false });
            vehicleStore.createIndex('location', 'location', { unique: false });
            vehicleStore.createIndex('available', 'available', { unique: false });
        }

        // Payments store
        if (!db.objectStoreNames.contains('payments')) {
            const paymentStore = db.createObjectStore('payments', { keyPath: 'id' });
            paymentStore.createIndex('userId', 'userId', { unique: false });
            paymentStore.createIndex('bookingId', 'bookingId', { unique: false });
            paymentStore.createIndex('status', 'status', { unique: false });
        }

        // Cache store for API responses
        if (!db.objectStoreNames.contains('cache')) {
            const cacheStore = db.createObjectStore('cache', { keyPath: 'key' });
            cacheStore.createIndex('timestamp', 'timestamp', { unique: false });
        }

        // Sync queue for offline operations
        if (!db.objectStoreNames.contains('syncQueue')) {
            const syncStore = db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
            syncStore.createIndex('timestamp', 'timestamp', { unique: false });
            syncStore.createIndex('action', 'action', { unique: false });
        }
    }

    // Generic CRUD Operations
    async save(storeName, data) {
        try {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            
            // Add timestamp
            data.updatedAt = new Date().toISOString();
            if (!data.createdAt) {
                data.createdAt = data.updatedAt;
            }

            const request = store.put(data);
            
            return new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(data);
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.error(`Error saving to ${storeName}:`, error);
            throw error;
        }
    }

    async get(storeName, id) {
        try {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(id);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.error(`Error getting from ${storeName}:`, error);
            throw error;
        }
    }

    async getAll(storeName, filters = {}) {
        try {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    let results = request.result;
                    
                    // Apply filters
                    if (Object.keys(filters).length > 0) {
                        results = results.filter(item => {
                            return Object.entries(filters).every(([key, value]) => {
                                if (Array.isArray(value)) {
                                    return value.includes(item[key]);
                                }
                                return item[key] === value;
                            });
                        });
                    }
                    
                    resolve(results);
                };
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.error(`Error getting all from ${storeName}:`, error);
            throw error;
        }
    }

    async delete(storeName, id) {
        try {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(id);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(true);
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.error(`Error deleting from ${storeName}:`, error);
            throw error;
        }
    }

    async clear(storeName) {
        try {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            return new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(true);
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            console.error(`Error clearing ${storeName}:`, error);
            throw error;
        }
    }

    // Specialized Methods
    async saveUser(userData) {
        return await this.save('users', userData);
    }

    async getUser(userId) {
        return await this.get('users', userId);
    }

    async saveBooking(bookingData) {
        const booking = await this.save('bookings', bookingData);
        
        // Add to sync queue if offline
        if (!this.isOnline) {
            await this.addToSyncQueue('booking', 'save', booking);
        }
        
        return booking;
    }

    async getUserBookings(userId) {
        return await this.getAll('bookings', { userId });
    }

    async saveVehicle(vehicleData) {
        return await this.save('vehicles', vehicleData);
    }

    async getAvailableVehicles(filters = {}) {
        const allFilters = { ...filters, available: true };
        return await this.getAll('vehicles', allFilters);
    }

    async savePayment(paymentData) {
        return await this.save('payments', paymentData);
    }

    async getUserPayments(userId) {
        return await this.getAll('payments', { userId });
    }

    // Cache Management
    async cacheData(key, data, ttl = 3600000) { // Default 1 hour TTL
        const cacheEntry = {
            key,
            data,
            timestamp: Date.now(),
            ttl
        };
        
        return await this.save('cache', cacheEntry);
    }

    async getCachedData(key) {
        try {
            const cacheEntry = await this.get('cache', key);
            
            if (!cacheEntry) {
                return null;
            }

            // Check if cache is expired
            if (Date.now() - cacheEntry.timestamp > cacheEntry.ttl) {
                await this.delete('cache', key);
                return null;
            }

            return cacheEntry.data;
        } catch (error) {
            console.error('Error getting cached data:', error);
            return null;
        }
    }

    async clearExpiredCache() {
        try {
            const allCache = await this.getAll('cache');
            const now = Date.now();
            
            for (const entry of allCache) {
                if (now - entry.timestamp > entry.ttl) {
                    await this.delete('cache', entry.key);
                }
            }
        } catch (error) {
            console.error('Error clearing expired cache:', error);
        }
    }

    // Offline Sync Management
    async addToSyncQueue(type, action, data) {
        const syncItem = {
            type,
            action,
            data,
            timestamp: Date.now(),
            retryCount: 0
        };

        return await this.save('syncQueue', syncItem);
    }

    async processSyncQueue() {
        if (!this.isOnline) {
            return;
        }

        try {
            const syncItems = await this.getAll('syncQueue');
            
            for (const item of syncItems) {
                try {
                    await this.syncItem(item);
                    await this.delete('syncQueue', item.id);
                } catch (error) {
                    console.error('Sync item failed:', error);
                    
                    // Increment retry count
                    item.retryCount++;
                    
                    // Remove item if too many retries
                    if (item.retryCount > 3) {
                        await this.delete('syncQueue', item.id);
                    } else {
                        await this.save('syncQueue', item);
                    }
                }
            }
        } catch (error) {
            console.error('Error processing sync queue:', error);
        }
    }

    async syncItem(item) {
        const { type, action, data } = item;
        
        switch (type) {
            case 'booking':
                if (action === 'save') {
                    await window.apiManager.post('/bookings', data);
                } else if (action === 'update') {
                    await window.apiManager.put(`/bookings/${data.id}`, data);
                } else if (action === 'delete') {
                    await window.apiManager.delete(`/bookings/${data.id}`);
                }
                break;
                
            case 'payment':
                if (action === 'save') {
                    await window.apiManager.post('/payments', data);
                }
                break;
                
            case 'user':
                if (action === 'update') {
                    await window.apiManager.put(`/users/${data.id}`, data);
                }
                break;
                
            default:
                console.warn('Unknown sync item type:', type);
        }
    }

    startSyncProcess() {
        // Sync every 30 seconds when online
        setInterval(() => {
            if (this.isOnline) {
                this.processSyncQueue();
            }
        }, 30000);

        // Clear expired cache every hour
        setInterval(() => {
            this.clearExpiredCache();
        }, 3600000);
    }

    // Local Storage Helpers
    setLocal(key, value) {
        try {
            localStorage.setItem(`cargo_${key}`, JSON.stringify(value));
        } catch (error) {
            console.error('Error setting localStorage:', error);
        }
    }

    getLocal(key, defaultValue = null) {
        try {
            const value = localStorage.getItem(`cargo_${key}`);
            return value ? JSON.parse(value) : defaultValue;
        } catch (error) {
            console.error('Error getting localStorage:', error);
            return defaultValue;
        }
    }

    removeLocal(key) {
        try {
            localStorage.removeItem(`cargo_${key}`);
        } catch (error) {
            console.error('Error removing localStorage:', error);
        }
    }

    // Session Storage Helpers
    setSession(key, value) {
        try {
            sessionStorage.setItem(`cargo_${key}`, JSON.stringify(value));
        } catch (error) {
            console.error('Error setting sessionStorage:', error);
        }
    }

    getSession(key, defaultValue = null) {
        try {
            const value = sessionStorage.getItem(`cargo_${key}`);
            return value ? JSON.parse(value) : defaultValue;
        } catch (error) {
            console.error('Error getting sessionStorage:', error);
            return defaultValue;
        }
    }

    removeSession(key) {
        try {
            sessionStorage.removeItem(`cargo_${key}`);
        } catch (error) {
            console.error('Error removing sessionStorage:', error);
        }
    }

    // Data Export/Import
    async exportData(storeName) {
        try {
            const data = await this.getAll(storeName);
            return {
                storeName,
                data,
                exportDate: new Date().toISOString(),
                version: this.dbVersion
            };
        } catch (error) {
            console.error('Error exporting data:', error);
            throw error;
        }
    }

    async importData(exportData) {
        try {
            const { storeName, data } = exportData;
            
            // Clear existing data
            await this.clear(storeName);
            
            // Import new data
            for (const item of data) {
                await this.save(storeName, item);
            }
            
            return true;
        } catch (error) {
            console.error('Error importing data:', error);
            throw error;
        }
    }

    async backup() {
        try {
            const backup = {
                version: this.dbVersion,
                timestamp: new Date().toISOString(),
                stores: {}
            };

            const storeNames = ['users', 'bookings', 'vehicles', 'payments'];
            
            for (const storeName of storeNames) {
                backup.stores[storeName] = await this.exportData(storeName);
            }

            // Save backup to local storage
            this.setLocal('backup', backup);
            
            return backup;
        } catch (error) {
            console.error('Error creating backup:', error);
            throw error;
        }
    }

    async restore(backup) {
        try {
            for (const [storeName, storeData] of Object.entries(backup.stores)) {
                await this.importData(storeData);
            }
            
            return true;
        } catch (error) {
            console.error('Error restoring backup:', error);
            throw error;
        }
    }

    // Storage Usage Monitoring
    async getStorageUsage() {
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            try {
                const estimate = await navigator.storage.estimate();
                return {
                    quota: estimate.quota,
                    usage: estimate.usage,
                    percentage: (estimate.usage / estimate.quota) * 100
                };
            } catch (error) {
                console.error('Error getting storage estimate:', error);
            }
        }
        
        return null;
    }

    async cleanup() {
        try {
            // Clear expired cache
            await this.clearExpiredCache();
            
            // Remove old sync queue items (older than 7 days)
            const syncItems = await this.getAll('syncQueue');
            const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
            
            for (const item of syncItems) {
                if (item.timestamp < sevenDaysAgo) {
                    await this.delete('syncQueue', item.id);
                }
            }
            
            // Remove old bookings (completed and older than 6 months)
            const bookings = await this.getAll('bookings');
            const sixMonthsAgo = Date.now() - (6 * 30 * 24 * 60 * 60 * 1000);
            
            for (const booking of bookings) {
                if (booking.status === 'completed' && 
                    new Date(booking.endDate).getTime() < sixMonthsAgo) {
                    await this.delete('bookings', booking.id);
                }
            }
            
            console.log('Storage cleanup completed');
        } catch (error) {
            console.error('Error during cleanup:', error);
        }
    }

    // Event Listeners
    setupEventListeners() {
        // Online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('App is online - starting sync process');
            this.processSyncQueue();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('App is offline - queuing operations');
        });

        // Storage cleanup on app start
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => this.cleanup(), 5000);
        });
    }
}

// Initialize storage manager
window.storageManager = new StorageManager();
