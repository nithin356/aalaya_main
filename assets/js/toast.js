const Toast = {
    init() {
        if (!document.querySelector('.toast-container')) {
            const container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
    },

    show(message, type = 'success', title = '') {
        this.init();
        const container = document.querySelector('.toast-container');
        
        const toast = document.createElement('div');
        toast.className = `aalaya-toast toast-${type}`;
        
        const iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };

        const defaultTitles = {
            success: 'Success',
            error: 'Error',
            info: 'Notice'
        };

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="bi ${iconMap[type]}"></i>
            </div>
            <div class="toast-content">
                <strong class="toast-title">${title || defaultTitles[type]}</strong>
                <p class="toast-message">${message}</p>
            </div>
            <button class="toast-close">
                <i class="bi bi-x"></i>
            </button>
        `;

        container.appendChild(toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto remove
        const timeout = setTimeout(() => this.hide(toast), 5000);

        // Close button click
        toast.querySelector('.toast-close').onclick = () => {
            clearTimeout(timeout);
            this.hide(toast);
        };
    },

    hide(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 400);
    },

    success(message, title = '') { this.show(message, 'success', title); },
    error(message, title = '') { this.show(message, 'error', title); },
    info(message, title = '') { this.show(message, 'info', title); }
};

// Global Exposure
const showToast = (message, type, title) => Toast.show(message, type, title);
showToast.success = (message, title) => Toast.success(message, title);
showToast.error = (message, title) => Toast.error(message, title);
showToast.info = (message, title) => Toast.info(message, title);

window.showToast = showToast;
window.alert = (message) => Toast.info(message); // Legacy shim
