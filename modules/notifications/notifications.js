/**
 * Notification Management Module
 * Handles push notifications, in-app notifications, email notifications, and SMS
 */

class NotificationManager {
    constructor() {
        this.notifications = [];
        this.settings = {
            push: true,
            email: true,
            sms: false,
            inApp: true,
            sound: true
        };
        this.swRegistration = null;
        this.init();
    }

    async init() {
        await this.loadSettings();
        await this.registerServiceWorker();
        this.loadNotifications();
        this.setupEventListeners();
        this.createNotificationContainer();
    }

    // Service Worker and Push Notifications
    async registerServiceWorker() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                this.swRegistration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registered successfully');
                
                await this.requestNotificationPermission();
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }

    async requestNotificationPermission() {
        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await this.subscribeToPush();
            }
        } else if (Notification.permission === 'granted') {
            await this.subscribeToPush();
        }
    }

    async subscribeToPush() {
        try {
            const applicationServerKey = this.urlB64ToUint8Array(window.appConfig.pushPublicKey);
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            // Send subscription to server
            await window.apiManager.post('/notifications/subscribe', {
                subscription: subscription.toJSON(),
                userId: window.authManager.currentUser?.id
            });

            console.log('Push notification subscription successful');
        } catch (error) {
            console.error('Push subscription failed:', error);
        }
    }

    // In-App Notifications
    show(message, type = 'info', duration = 5000, persistent = false) {
        const notification = {
            id: this.generateId(),
            message,
            type, // info, success, warning, error
            timestamp: new Date(),
            persistent,
            read: false
        };

        this.notifications.unshift(notification);
        this.renderNotification(notification, duration);
        this.updateNotificationBadge();

        return notification.id;
    }

    showSuccess(message, duration = 3000) {
        return this.show(message, 'success', duration);
    }

    showError(message, duration = 7000) {
        return this.show(message, 'error', duration, true);
    }

    showWarning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    showInfo(message, duration = 4000) {
        return this.show(message, 'info', duration);
    }

    // Advanced Notifications
    showBookingNotification(booking, type) {
        const messages = {
            confirmed: `Booking #${booking.id} confirmed for ${booking.vehicle.name}`,
            cancelled: `Booking #${booking.id} has been cancelled`,
            reminder: `Reminder: Your booking #${booking.id} starts in 1 hour`,
            completed: `Booking #${booking.id} completed. Please rate your experience`,
            modified: `Booking #${booking.id} has been modified`
        };

        const notification = {
            id: this.generateId(),
            message: messages[type] || `Booking update: ${booking.id}`,
            type: type === 'cancelled' ? 'warning' : 'info',
            timestamp: new Date(),
            persistent: true,
            read: false,
            data: {
                bookingId: booking.id,
                type: 'booking',
                action: type
            }
        };

        this.notifications.unshift(notification);
        this.renderNotification(notification);
        this.updateNotificationBadge();

        // Send push notification if enabled
        if (this.settings.push) {
            this.sendPushNotification(notification);
        }

        return notification.id;
    }

    showPaymentNotification(payment, type) {
        const messages = {
            success: `Payment of ${this.formatCurrency(payment.amount)} processed successfully`,
            failed: `Payment of ${this.formatCurrency(payment.amount)} failed`,
            refunded: `Refund of ${this.formatCurrency(payment.refundAmount)} processed`,
            pending: `Payment of ${this.formatCurrency(payment.amount)} is being processed`
        };

        return this.show(messages[type], type === 'failed' ? 'error' : 'success');
    }

    showSystemNotification(message, priority = 'normal') {
        const type = priority === 'high' ? 'warning' : 'info';
        return this.show(message, type, priority === 'high' ? 10000 : 5000, priority === 'high');
    }

    // Notification Management
    markAsRead(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.read = true;
            this.updateNotificationBadge();
            this.saveNotifications();
        }
    }

    markAllAsRead() {
        this.notifications.forEach(n => n.read = true);
        this.updateNotificationBadge();
        this.saveNotifications();
    }

    removeNotification(notificationId) {
        this.notifications = this.notifications.filter(n => n.id !== notificationId);
        this.updateNotificationBadge();
        this.saveNotifications();
        
        // Remove from DOM
        const element = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (element) {
            element.remove();
        }
    }

    clearAll() {
        this.notifications = [];
        this.updateNotificationBadge();
        this.saveNotifications();
        
        // Clear from DOM
        const container = document.getElementById('notifications-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    // Push Notifications
    async sendPushNotification(notification) {
        if (!this.swRegistration || Notification.permission !== 'granted') {
            return;
        }

        try {
            await window.apiManager.post('/notifications/push', {
                userId: window.authManager.currentUser?.id,
                title: 'CarGo Notification',
                body: notification.message,
                icon: '/icons/icon-192x192.png',
                badge: '/icons/badge-72x72.png',
                data: notification.data
            });
        } catch (error) {
            console.error('Failed to send push notification:', error);
        }
    }

    // Email/SMS Notifications
    async sendEmailNotification(userId, template, data) {
        try {
            await window.apiManager.post('/notifications/email', {
                userId,
                template,
                data
            });
        } catch (error) {
            console.error('Failed to send email notification:', error);
        }
    }

    async sendSMSNotification(userId, message) {
        try {
            await window.apiManager.post('/notifications/sms', {
                userId,
                message
            });
        } catch (error) {
            console.error('Failed to send SMS notification:', error);
        }
    }

    // UI Rendering
    createNotificationContainer() {
        if (document.getElementById('notification-toasts')) return;

        const container = document.createElement('div');
        container.id = 'notification-toasts';
        container.className = 'notification-toasts';
        document.body.appendChild(container);
    }

    renderNotification(notification, duration = 5000) {
        const container = document.getElementById('notification-toasts');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `notification-toast ${notification.type}`;
        toast.setAttribute('data-notification-id', notification.id);
        
        toast.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    ${this.getNotificationIcon(notification.type)}
                </div>
                <div class="notification-message">${notification.message}</div>
                <button class="notification-close" onclick="window.notificationManager.removeNotification('${notification.id}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        container.appendChild(toast);

        // Auto-remove after duration (unless persistent)
        if (!notification.persistent && duration > 0) {
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.add('removing');
                    setTimeout(() => {
                        this.removeNotification(notification.id);
                    }, 300);
                }
            }, duration);
        }

        // Play sound if enabled
        if (this.settings.sound) {
            this.playNotificationSound(notification.type);
        }
    }

    renderNotificationCenter() {
        const container = document.getElementById('notification-center');
        if (!container) return;

        const unreadCount = this.notifications.filter(n => !n.read).length;
        const recentNotifications = this.notifications.slice(0, 10);

        container.innerHTML = `
            <div class="notification-center-header">
                <h3>Notifications ${unreadCount > 0 ? `<span class="badge">${unreadCount}</span>` : ''}</h3>
                <div class="notification-actions">
                    <button class="btn btn-sm btn-outline" onclick="window.notificationManager.markAllAsRead()">Mark All Read</button>
                    <button class="btn btn-sm btn-outline" onclick="window.notificationManager.clearAll()">Clear All</button>
                </div>
            </div>
            <div class="notification-list">
                ${recentNotifications.length === 0 ? 
                    '<div class="no-notifications">No notifications</div>' :
                    recentNotifications.map(n => this.renderNotificationItem(n)).join('')
                }
            </div>
        `;
    }

    renderNotificationItem(notification) {
        return `
            <div class="notification-item ${notification.read ? 'read' : 'unread'}" data-notification-id="${notification.id}">
                <div class="notification-icon ${notification.type}">
                    ${this.getNotificationIcon(notification.type)}
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatRelativeTime(notification.timestamp)}</div>
                </div>
                <div class="notification-actions">
                    ${!notification.read ? 
                        `<button class="btn btn-xs btn-outline" onclick="window.notificationManager.markAsRead('${notification.id}')">Mark Read</button>` : ''
                    }
                    <button class="btn btn-xs btn-outline btn-danger" onclick="window.notificationManager.removeNotification('${notification.id}')">Remove</button>
                </div>
            </div>
        `;
    }

    updateNotificationBadge() {
        const unreadCount = this.notifications.filter(n => !n.read).length;
        const badges = document.querySelectorAll('.notification-badge');
        
        badges.forEach(badge => {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    // Settings Management
    async loadSettings() {
        try {
            const response = await window.apiManager.get('/notifications/settings');
            this.settings = { ...this.settings, ...response.data };
        } catch (error) {
            console.error('Failed to load notification settings:', error);
            // Use default settings
        }
    }

    async updateSettings(newSettings) {
        try {
            this.settings = { ...this.settings, ...newSettings };
            await window.apiManager.put('/notifications/settings', this.settings);
            this.show('Notification settings updated', 'success');
        } catch (error) {
            console.error('Failed to update notification settings:', error);
            this.show('Failed to update settings', 'error');
        }
    }

    renderSettingsForm() {
        const container = document.getElementById('notification-settings');
        if (!container) return;

        container.innerHTML = `
            <form id="notification-settings-form">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" ${this.settings.push ? 'checked' : ''} name="push">
                        <span class="checkmark"></span>
                        Push Notifications
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" ${this.settings.email ? 'checked' : ''} name="email">
                        <span class="checkmark"></span>
                        Email Notifications
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" ${this.settings.sms ? 'checked' : ''} name="sms">
                        <span class="checkmark"></span>
                        SMS Notifications
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" ${this.settings.inApp ? 'checked' : ''} name="inApp">
                        <span class="checkmark"></span>
                        In-App Notifications
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" ${this.settings.sound ? 'checked' : ''} name="sound">
                        <span class="checkmark"></span>
                        Notification Sounds
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        `;
    }

    // Event Listeners
    setupEventListeners() {
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'notification-settings-form') {
                e.preventDefault();
                const formData = new FormData(e.target);
                const settings = {
                    push: formData.has('push'),
                    email: formData.has('email'),
                    sms: formData.has('sms'),
                    inApp: formData.has('inApp'),
                    sound: formData.has('sound')
                };
                this.updateSettings(settings);
            }
        });

        // Listen for visibility change to update notifications
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadNotifications();
            }
        });
    }

    // Data Management
    async loadNotifications() {
        try {
            const response = await window.apiManager.get('/notifications');
            this.notifications = response.data || [];
            this.updateNotificationBadge();
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    saveNotifications() {
        // Save to localStorage as backup
        localStorage.setItem('cargo_notifications', JSON.stringify(this.notifications));
    }

    // Utility Methods
    getNotificationIcon(type) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons.info;
    }

    formatRelativeTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffMs = now - time;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return time.toLocaleDateString();
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    generateId() {
        return 'notif_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    playNotificationSound(type) {
        // Create audio element for notification sound
        const audio = new Audio();
        const sounds = {
            success: '/assets/sounds/success.mp3',
            error: '/assets/sounds/error.mp3',
            warning: '/assets/sounds/warning.mp3',
            info: '/assets/sounds/info.mp3'
        };
        
        audio.src = sounds[type] || sounds.info;
        audio.volume = 0.3;
        audio.play().catch(e => console.log('Could not play notification sound:', e));
    }

    urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

// Initialize notification manager
window.notificationManager = new NotificationManager();
