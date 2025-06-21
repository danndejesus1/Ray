/**
 * Payment Management Component
 * Handles payment processing, methods, transactions, and billing
 */

class PaymentManager {
    constructor() {
        this.paymentMethods = [];
        this.transactions = [];
        this.activePayment = null;
        this.paymentGateways = {
            stripe: { enabled: true, publishableKey: 'pk_test_...' },
            paypal: { enabled: true, clientId: 'paypal_client_id' },
            razorpay: { enabled: false, keyId: 'rzp_test_...' }
        };
        this.init();
    }

    init() {
        this.loadPaymentMethods();
        this.loadTransactionHistory();
        this.setupEventListeners();
    }

    // Payment Methods Management
    async loadPaymentMethods() {
        try {
            const response = await window.apiManager.get('/payment/methods');
            this.paymentMethods = response.data || this.getMockPaymentMethods();
            this.renderPaymentMethods();
        } catch (error) {
            console.error('Error loading payment methods:', error);
            this.paymentMethods = this.getMockPaymentMethods();
            this.renderPaymentMethods();
        }
    }

    getMockPaymentMethods() {
        return [
            {
                id: 'pm_1',
                type: 'card',
                brand: 'visa',
                last4: '4242',
                expMonth: 12,
                expYear: 2025,
                isDefault: true,
                nickname: 'Personal Visa'
            },
            {
                id: 'pm_2',
                type: 'card',
                brand: 'mastercard',
                last4: '5555',
                expMonth: 8,
                expYear: 2026,
                isDefault: false,
                nickname: 'Business Card'
            }
        ];
    }

    async addPaymentMethod(paymentData) {
        try {
            const response = await window.apiManager.post('/payment/methods', paymentData);
            if (response.success) {
                this.paymentMethods.push(response.data);
                this.renderPaymentMethods();
                this.showNotification('Payment method added successfully', 'success');
                return response.data;
            }
        } catch (error) {
            console.error('Error adding payment method:', error);
            this.showNotification('Failed to add payment method', 'error');
            throw error;
        }
    }

    async removePaymentMethod(methodId) {
        try {
            const response = await window.apiManager.delete(`/payment/methods/${methodId}`);
            if (response.success) {
                this.paymentMethods = this.paymentMethods.filter(method => method.id !== methodId);
                this.renderPaymentMethods();
                this.showNotification('Payment method removed successfully', 'success');
            }
        } catch (error) {
            console.error('Error removing payment method:', error);
            this.showNotification('Failed to remove payment method', 'error');
        }
    }

    async setDefaultPaymentMethod(methodId) {
        try {
            // Update locally first
            this.paymentMethods.forEach(method => {
                method.isDefault = method.id === methodId;
            });

            const response = await window.apiManager.put(`/payment/methods/${methodId}/default`);
            if (response.success) {
                this.renderPaymentMethods();
                this.showNotification('Default payment method updated', 'success');
            }
        } catch (error) {
            console.error('Error setting default payment method:', error);
            this.showNotification('Failed to update default payment method', 'error');
        }
    }

    // Payment Processing
    async processPayment(bookingId, amount, paymentMethodId = null) {
        try {
            const paymentMethod = paymentMethodId ? 
                this.paymentMethods.find(pm => pm.id === paymentMethodId) :
                this.paymentMethods.find(pm => pm.isDefault);

            if (!paymentMethod) {
                throw new Error('No payment method selected');
            }

            const paymentData = {
                bookingId,
                amount,
                currency: 'USD',
                paymentMethodId: paymentMethod.id,
                description: `Car rental booking #${bookingId}`
            };

            this.activePayment = {
                ...paymentData,
                status: 'processing',
                timestamp: new Date().toISOString()
            };

            this.showPaymentModal(paymentData);

            const response = await window.apiManager.post('/payment/process', paymentData);
            
            if (response.success) {
                this.activePayment.status = 'completed';
                this.activePayment.transactionId = response.data.transactionId;
                this.transactions.unshift(this.activePayment);
                
                this.hidePaymentModal();
                this.showNotification('Payment completed successfully', 'success');
                
                // Trigger booking confirmation
                if (window.bookingManager) {
                    window.bookingManager.confirmBooking(bookingId, response.data.transactionId);
                }

                return response.data;
            } else {
                throw new Error(response.message || 'Payment failed');
            }
        } catch (error) {
            if (this.activePayment) {
                this.activePayment.status = 'failed';
                this.activePayment.error = error.message;
            }
            
            this.hidePaymentModal();
            this.showNotification(`Payment failed: ${error.message}`, 'error');
            console.error('Payment processing error:', error);
            throw error;
        }
    }

    async refundPayment(transactionId, amount = null, reason = '') {
        try {
            const refundData = {
                transactionId,
                amount, // null for full refund
                reason
            };

            const response = await window.apiManager.post('/payment/refund', refundData);
            
            if (response.success) {
                // Update transaction record
                const transaction = this.transactions.find(t => t.transactionId === transactionId);
                if (transaction) {
                    transaction.refunded = true;
                    transaction.refundAmount = response.data.refundAmount;
                    transaction.refundDate = new Date().toISOString();
                }

                this.showNotification('Refund processed successfully', 'success');
                return response.data;
            }
        } catch (error) {
            console.error('Refund processing error:', error);
            this.showNotification('Failed to process refund', 'error');
            throw error;
        }
    }

    // Transaction History
    async loadTransactionHistory() {
        try {
            const response = await window.apiManager.get('/payment/transactions');
            this.transactions = response.data || this.getMockTransactions();
        } catch (error) {
            console.error('Error loading transactions:', error);
            this.transactions = this.getMockTransactions();
        }
    }

    getMockTransactions() {
        return [
            {
                id: 'txn_1',
                transactionId: 'pi_3M1234567890',
                bookingId: 'BK001',
                amount: 150.00,
                currency: 'USD',
                status: 'completed',
                paymentMethod: 'Visa ****4242',
                timestamp: '2024-12-15T10:30:00Z',
                description: 'Car rental booking #BK001'
            },
            {
                id: 'txn_2',
                transactionId: 'pi_3M1234567891',
                bookingId: 'BK002',
                amount: 89.99,
                currency: 'USD',
                status: 'completed',
                paymentMethod: 'Mastercard ****5555',
                timestamp: '2024-12-10T14:45:00Z',
                description: 'Car rental booking #BK002',
                refunded: true,
                refundAmount: 89.99,
                refundDate: '2024-12-12T09:15:00Z'
            }
        ];
    }

    // UI Rendering
    renderPaymentMethods() {
        const container = document.getElementById('payment-methods-container');
        if (!container) return;

        container.innerHTML = this.paymentMethods.map(method => `
            <div class="payment-method-card ${method.isDefault ? 'default' : ''}" data-method-id="${method.id}">
                <div class="payment-method-info">
                    <div class="card-brand">
                        <i class="fab fa-cc-${method.brand}"></i>
                        <span class="brand-name">${method.brand.toUpperCase()}</span>
                    </div>
                    <div class="card-details">
                        <span class="card-number">•••• •••• •••• ${method.last4}</span>
                        <span class="card-expiry">${method.expMonth}/${method.expYear}</span>
                    </div>
                    ${method.nickname ? `<div class="card-nickname">${method.nickname}</div>` : ''}
                    ${method.isDefault ? '<div class="default-badge">Default</div>' : ''}
                </div>
                <div class="payment-method-actions">
                    ${!method.isDefault ? `<button class="btn btn-sm btn-outline set-default-btn">Set Default</button>` : ''}
                    <button class="btn btn-sm btn-outline edit-method-btn">Edit</button>
                    <button class="btn btn-sm btn-outline btn-danger remove-method-btn">Remove</button>
                </div>
            </div>
        `).join('');
    }

    renderTransactionHistory() {
        const container = document.getElementById('transaction-history-container');
        if (!container) return;

        container.innerHTML = this.transactions.map(transaction => `
            <div class="transaction-item ${transaction.status}" data-transaction-id="${transaction.id}">
                <div class="transaction-info">
                    <div class="transaction-description">${transaction.description}</div>
                    <div class="transaction-details">
                        <span class="transaction-date">${this.formatDate(transaction.timestamp)}</span>
                        <span class="transaction-method">${transaction.paymentMethod}</span>
                        <span class="transaction-id">ID: ${transaction.transactionId}</span>
                    </div>
                </div>
                <div class="transaction-amount">
                    <span class="amount ${transaction.refunded ? 'refunded' : ''}">${this.formatCurrency(transaction.amount)}</span>
                    <span class="status">${transaction.status}</span>
                    ${transaction.refunded ? `<div class="refund-info">Refunded: ${this.formatCurrency(transaction.refundAmount)}</div>` : ''}
                </div>
                <div class="transaction-actions">
                    <button class="btn btn-sm btn-outline view-receipt-btn">Receipt</button>
                    ${transaction.status === 'completed' && !transaction.refunded ? 
                        `<button class="btn btn-sm btn-outline btn-warning request-refund-btn">Refund</button>` : ''}
                </div>
            </div>
        `).join('');
    }

    // Payment Modal
    showPaymentModal(paymentData) {
        const modal = document.getElementById('payment-modal') || this.createPaymentModal();
        
        modal.querySelector('.payment-amount').textContent = this.formatCurrency(paymentData.amount);
        modal.querySelector('.payment-description').textContent = paymentData.description;
        
        modal.classList.add('active');
    }

    hidePaymentModal() {
        const modal = document.getElementById('payment-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    createPaymentModal() {
        const modal = document.createElement('div');
        modal.id = 'payment-modal';
        modal.className = 'modal payment-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Processing Payment</h3>
                </div>
                <div class="modal-body">
                    <div class="payment-progress">
                        <div class="spinner"></div>
                        <p>Processing your payment...</p>
                        <div class="payment-details">
                            <div class="payment-amount"></div>
                            <div class="payment-description"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        return modal;
    }

    // Event Listeners
    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('set-default-btn')) {
                const methodId = e.target.closest('.payment-method-card').dataset.methodId;
                this.setDefaultPaymentMethod(methodId);
            }

            if (e.target.classList.contains('remove-method-btn')) {
                const methodId = e.target.closest('.payment-method-card').dataset.methodId;
                if (confirm('Are you sure you want to remove this payment method?')) {
                    this.removePaymentMethod(methodId);
                }
            }

            if (e.target.classList.contains('request-refund-btn')) {
                const transactionId = e.target.closest('.transaction-item').dataset.transactionId;
                this.showRefundModal(transactionId);
            }

            if (e.target.classList.contains('view-receipt-btn')) {
                const transactionId = e.target.closest('.transaction-item').dataset.transactionId;
                this.downloadReceipt(transactionId);
            }
        });
    }

    // Utility Methods
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showNotification(message, type) {
        if (window.notificationManager) {
            window.notificationManager.show(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }

    async downloadReceipt(transactionId) {
        try {
            const response = await window.apiManager.get(`/payment/receipt/${transactionId}`, {
                responseType: 'blob'
            });
            
            const blob = new Blob([response.data], { type: 'application/pdf' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `receipt-${transactionId}.pdf`;
            a.click();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Error downloading receipt:', error);
            this.showNotification('Failed to download receipt', 'error');
        }
    }
}

// Initialize payment manager
window.paymentManager = new PaymentManager();
