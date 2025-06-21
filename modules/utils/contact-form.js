/**
 * Contact Form Handler
 * Manages contact form submissions and validation
 */

class ContactForm {
    constructor() {
        this.form = document.getElementById('contact-form');
        this.responseMessage = document.getElementById('contact-response-message');
        this.init();
    }
    
    /**
     * Initialize contact form functionality
     */
    init() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }
    
    /**
     * Handle form submission
     * @param {Event} event - Form submit event
     */
    async handleSubmit(event) {
        event.preventDefault();
        
        // Validate form
        if (!this.validateForm()) {
            return;
        }
        
        // Get form data
        const formData = {
            name: this.form.querySelector('#name').value,
            email: this.form.querySelector('#email').value,
            message: this.form.querySelector('#message').value
        };
        
        // Show loading state
        this.showMessage('Sending message...', 'text-blue-500');
        this.toggleFormState(true);
        
        try {
            // Submit form data to API
            const response = await API.post('/contact', formData);
            
            if (response.success) {
                // Show success message
                this.showMessage(response.message || 'Thank you for your message. We will get back to you soon!', 'text-green-500');
                this.form.reset();
            } else {
                // Show error message
                this.showMessage(response.message || 'Failed to send message. Please try again.', 'text-red-500');
            }
        } catch (error) {
            // Show error message
            this.showMessage('An error occurred. Please try again later.', 'text-red-500');
            console.error('Contact form submission error:', error);
        } finally {
            // Enable form
            this.toggleFormState(false);
        }
    }
    
    /**
     * Validate form fields
     * @returns {boolean} - True if valid, false otherwise
     */
    validateForm() {
        const name = this.form.querySelector('#name').value.trim();
        const email = this.form.querySelector('#email').value.trim();
        const message = this.form.querySelector('#message').value.trim();
        
        if (!name) {
            this.showMessage('Please enter your name.', 'text-red-500');
            return false;
        }
        
        if (!email) {
            this.showMessage('Please enter your email.', 'text-red-500');
            return false;
        }
        
        // Simple email validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            this.showMessage('Please enter a valid email address.', 'text-red-500');
            return false;
        }
        
        if (!message) {
            this.showMessage('Please enter your message.', 'text-red-500');
            return false;
        }
        
        return true;
    }
    
    /**
     * Show response message
     * @param {string} message - Message text
     * @param {string} className - CSS class for styling
     */
    showMessage(message, className) {
        if (this.responseMessage) {
            this.responseMessage.textContent = message;
            this.responseMessage.className = `mt-4 text-center text-sm font-medium ${className}`;
            this.responseMessage.classList.remove('hidden');
        }
    }
    
    /**
     * Toggle form disabled state
     * @param {boolean} disabled - Whether to disable the form
     */
    toggleFormState(disabled) {
        const inputs = this.form.querySelectorAll('input, textarea, button');
        inputs.forEach(input => {
            input.disabled = disabled;
        });
        
        const submitButton = this.form.querySelector('button[type="submit"]');
        if (submitButton) {
            if (disabled) {
                submitButton.classList.add('opacity-70', 'cursor-not-allowed');
            } else {
                submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    }
}

// Initialize the contact form when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.contactForm = new ContactForm();
});
