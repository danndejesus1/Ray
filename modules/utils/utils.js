/**
 * Utility Functions Module
 * Common helper functions used throughout the application
 */

// Date and Time Utilities
const DateUtils = {
    formatDate(date, format = 'short') {
        const d = new Date(date);
        
        const formats = {
            short: { month: 'short', day: 'numeric', year: 'numeric' },
            long: { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' },
            time: { hour: '2-digit', minute: '2-digit' },
            datetime: { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit', 
                minute: '2-digit' 
            },
            iso: null // Returns ISO string
        };

        if (format === 'iso') {
            return d.toISOString();
        }

        return d.toLocaleDateString('en-US', formats[format] || formats.short);
    },

    formatTime(date) {
        return new Date(date).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    formatDuration(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffMs = end - start;
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(diffHours / 24);

        if (diffDays > 0) {
            const remainingHours = diffHours % 24;
            return `${diffDays} day${diffDays > 1 ? 's' : ''}${remainingHours > 0 ? ` ${remainingHours}h` : ''}`;
        }
        
        return `${diffHours} hour${diffHours > 1 ? 's' : ''}`;
    },

    getRelativeTime(date) {
        const now = new Date();
        const target = new Date(date);
        const diffMs = now - target;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return this.formatDate(date);
    },

    addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    },

    addHours(date, hours) {
        const result = new Date(date);
        result.setHours(result.getHours() + hours);
        return result;
    },

    isSameDay(date1, date2) {
        const d1 = new Date(date1);
        const d2 = new Date(date2);
        return d1.getFullYear() === d2.getFullYear() &&
               d1.getMonth() === d2.getMonth() &&
               d1.getDate() === d2.getDate();
    },

    isToday(date) {
        return this.isSameDay(date, new Date());
    },

    isTomorrow(date) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return this.isSameDay(date, tomorrow);
    },

    isWeekend(date) {
        const day = new Date(date).getDay();
        return day === 0 || day === 6; // Sunday = 0, Saturday = 6
    }
};

// Currency and Number Utilities
const CurrencyUtils = {
    format(amount, currency = 'USD', locale = 'en-US') {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    formatNumber(number, decimals = 0) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },

    parseNumber(value) {
        const parsed = parseFloat(value.toString().replace(/[^0-9.-]+/g, ''));
        return isNaN(parsed) ? 0 : parsed;
    },

    calculateTax(amount, taxRate = 0.08) {
        return amount * taxRate;
    },

    calculateTotal(subtotal, taxRate = 0.08, discount = 0) {
        const tax = this.calculateTax(subtotal, taxRate);
        return subtotal + tax - discount;
    },

    formatPercentage(value, decimals = 1) {
        return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value / 100);
    }
};

// String Utilities
const StringUtils = {
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    capitalizeWords(str) {
        return str.replace(/\w\S*/g, (txt) => 
            txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
        );
    },

    truncate(str, length = 100, suffix = '...') {
        if (str.length <= length) return str;
        return str.substring(0, length) + suffix;
    },

    slugify(str) {
        return str
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    unescapeHtml(str) {
        const div = document.createElement('div');
        div.innerHTML = str;
        return div.textContent || div.innerText || '';
    },

    generateId(prefix = '', length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = prefix;
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    generateBookingId() {
        const prefix = 'BK';
        const timestamp = Date.now().toString().slice(-6);
        const random = Math.random().toString(36).substr(2, 3).toUpperCase();
        return `${prefix}${timestamp}${random}`;
    },

    mask(str, visibleChars = 4, maskChar = '*') {
        if (str.length <= visibleChars) return str;
        const visible = str.slice(-visibleChars);
        const masked = maskChar.repeat(str.length - visibleChars);
        return masked + visible;
    },

    formatPhoneNumber(phone) {
        const cleaned = phone.replace(/\D/g, '');
        const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
        if (match) {
            return '(' + match[1] + ') ' + match[2] + '-' + match[3];
        }
        return phone;
    }
};

// Validation Utilities
const ValidationUtils = {
    isEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    isPhoneNumber(phone) {
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    },

    isStrongPassword(password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special char
        const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        return strongRegex.test(password);
    },

    isCreditCard(number) {
        const cleanNumber = number.replace(/\s/g, '');
        const cardRegex = /^\d{13,19}$/;
        if (!cardRegex.test(cleanNumber)) return false;
        
        // Luhn algorithm
        let sum = 0;
        let isEven = false;
        
        for (let i = cleanNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cleanNumber.charAt(i), 10);
            
            if (isEven) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            
            sum += digit;
            isEven = !isEven;
        }
        
        return (sum % 10) === 0;
    },

    isValidDate(date) {
        const d = new Date(date);
        return d instanceof Date && !isNaN(d);
    },

    isFutureDate(date) {
        return new Date(date) > new Date();
    },

    isPastDate(date) {
        return new Date(date) < new Date();
    },

    isZipCode(zip) {
        const zipRegex = /^\d{5}(-\d{4})?$/;
        return zipRegex.test(zip);
    },

    validateForm(formData, rules) {
        const errors = {};
        
        Object.entries(rules).forEach(([field, fieldRules]) => {
            const value = formData[field];
            const fieldErrors = [];
            
            if (fieldRules.required && (!value || value.trim() === '')) {
                fieldErrors.push(`${field} is required`);
            }
            
            if (value && fieldRules.minLength && value.length < fieldRules.minLength) {
                fieldErrors.push(`${field} must be at least ${fieldRules.minLength} characters`);
            }
            
            if (value && fieldRules.maxLength && value.length > fieldRules.maxLength) {
                fieldErrors.push(`${field} must be no more than ${fieldRules.maxLength} characters`);
            }
            
            if (value && fieldRules.pattern && !fieldRules.pattern.test(value)) {
                fieldErrors.push(fieldRules.message || `${field} format is invalid`);
            }
            
            if (value && fieldRules.email && !this.isEmail(value)) {
                fieldErrors.push(`${field} must be a valid email address`);
            }
            
            if (value && fieldRules.phone && !this.isPhoneNumber(value)) {
                fieldErrors.push(`${field} must be a valid phone number`);
            }
            
            if (fieldErrors.length > 0) {
                errors[field] = fieldErrors;
            }
        });
        
        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }
};

// Array Utilities
const ArrayUtils = {
    groupBy(array, key) {
        return array.reduce((groups, item) => {
            const value = item[key];
            groups[value] = groups[value] || [];
            groups[value].push(item);
            return groups;
        }, {});
    },

    sortBy(array, key, direction = 'asc') {
        return [...array].sort((a, b) => {
            const aVal = a[key];
            const bVal = b[key];
            
            if (direction === 'desc') {
                return bVal > aVal ? 1 : bVal < aVal ? -1 : 0;
            }
            
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        });
    },

    filterBy(array, filters) {
        return array.filter(item => {
            return Object.entries(filters).every(([key, value]) => {
                if (Array.isArray(value)) {
                    return value.includes(item[key]);
                }
                return item[key] === value;
            });
        });
    },

    paginate(array, page = 1, pageSize = 10) {
        const startIndex = (page - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        
        return {
            data: array.slice(startIndex, endIndex),
            pagination: {
                page,
                pageSize,
                total: array.length,
                totalPages: Math.ceil(array.length / pageSize),
                hasNext: endIndex < array.length,
                hasPrev: page > 1
            }
        };
    },

    unique(array, key = null) {
        if (key) {
            const seen = new Set();
            return array.filter(item => {
                const value = item[key];
                if (seen.has(value)) {
                    return false;
                }
                seen.add(value);
                return true;
            });
        }
        
        return [...new Set(array)];
    },

    chunk(array, size) {
        const chunks = [];
        for (let i = 0; i < array.length; i += size) {
            chunks.push(array.slice(i, i + size));
        }
        return chunks;
    }
};

// URL Utilities
const UrlUtils = {
    getQueryParams() {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    },

    setQueryParam(key, value) {
        const url = new URL(window.location);
        url.searchParams.set(key, value);
        window.history.pushState({}, '', url);
    },

    removeQueryParam(key) {
        const url = new URL(window.location);
        url.searchParams.delete(key);
        window.history.pushState({}, '', url);
    },

    buildUrl(base, params = {}) {
        const url = new URL(base);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    }
};

// Local Storage Utilities
const StorageUtils = {
    set(key, value, expiry = null) {
        const data = {
            value,
            expiry: expiry ? Date.now() + expiry : null
        };
        localStorage.setItem(`cargo_${key}`, JSON.stringify(data));
    },

    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(`cargo_${key}`);
            if (!item) return defaultValue;
            
            const data = JSON.parse(item);
            
            if (data.expiry && Date.now() > data.expiry) {
                localStorage.removeItem(`cargo_${key}`);
                return defaultValue;
            }
            
            return data.value;
        } catch (error) {
            console.error('Error getting from localStorage:', error);
            return defaultValue;
        }
    },

    remove(key) {
        localStorage.removeItem(`cargo_${key}`);
    },

    clear() {
        Object.keys(localStorage)
            .filter(key => key.startsWith('cargo_'))
            .forEach(key => localStorage.removeItem(key));
    }
};

// Device Utilities
const DeviceUtils = {
    isMobile() {
        return window.innerWidth <= 768;
    },

    isTablet() {
        return window.innerWidth > 768 && window.innerWidth <= 1024;
    },

    isDesktop() {
        return window.innerWidth > 1024;
    },

    isOnline() {
        return navigator.onLine;
    },

    hasCamera() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    },

    hasGeolocation() {
        return 'geolocation' in navigator;
    },

    vibrate(pattern = 200) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    },

    copyToClipboard(text) {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        }
        
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        return Promise.resolve();
    }
};

// File Utilities
const FileUtils = {
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    getFileExtension(filename) {
        return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2);
    },

    isImageFile(filename) {
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        const extension = this.getFileExtension(filename).toLowerCase();
        return imageExtensions.includes(extension);
    },

    downloadFile(data, filename, type = 'text/plain') {
        const blob = new Blob([data], { type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    },

    readFileAsText(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = reject;
            reader.readAsText(file);
        });
    },

    readFileAsDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }
};

// Color Utilities
const ColorUtils = {
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    },

    rgbToHex(r, g, b) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    },

    getContrastColor(hex) {
        const rgb = this.hexToRgb(hex);
        if (!rgb) return '#000000';
        
        const brightness = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;
        return brightness > 128 ? '#000000' : '#ffffff';
    }
};

// Debounce and Throttle Utilities
const PerformanceUtils = {
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    },

    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    memoize(fn) {
        const cache = {};
        return function(...args) {
            const key = JSON.stringify(args);
            if (cache[key]) {
                return cache[key];
            }
            const result = fn.apply(this, args);
            cache[key] = result;
            return result;
        };
    }
};

// Export all utilities
window.Utils = {
    Date: DateUtils,
    Currency: CurrencyUtils,
    String: StringUtils,
    Validation: ValidationUtils,
    Array: ArrayUtils,
    Url: UrlUtils,
    Storage: StorageUtils,
    Device: DeviceUtils,
    File: FileUtils,
    Color: ColorUtils,
    Performance: PerformanceUtils
};
