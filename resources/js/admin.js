// admin.js - Zone Admin JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Configuration globale
    const config = {
        baseUrl: window.location.origin,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        toastDuration: 3000
    };

    // Utility functions
    const utils = {
        showToast: function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, config.toastDuration);
        },

        formatPrice: function(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        },

        formatDate: function(date) {
            return new Date(date).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        confirmDialog: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    };

    // Dashboard Management
    const dashboard = {
        init: function() {
            this.loadStats();
            this.setupCharts();
            this.setupRefreshButton();
        },

        loadStats: function() {
            fetch('/admin/dashboard/stats', {
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-orders').textContent = data.totalOrders;
                document.getElementById('total-revenue').textContent = utils.formatPrice(data.totalRevenue);
                document.getElementById('total-products').textContent = data.totalProducts;
                document.getElementById('total-users').textContent = data.totalUsers;
            })
            .catch(error => {
                console.error('Erreur lors du chargement des statistiques:', error);
                utils.showToast('Erreur lors du chargement des statistiques', 'error');
            });
        },

        setupCharts: function() {
            // Configuration des graphiques si Chart.js est disponible
            if (typeof Chart !== 'undefined') {
                this.createSalesChart();
                this.createOrdersChart();
            }
        },

        createSalesChart: function() {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
                    datasets: [{
                        label: 'Ventes',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },

        createOrdersChart: function() {
            const ctx = document.getElementById('ordersChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                    datasets: [{
                        label: 'Commandes',
                        data: [5, 8, 12, 7, 9, 6, 4],
                        backgroundColor: '#10B981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },

        setupRefreshButton: function() {
            const refreshBtn = document.getElementById('refresh-dashboard');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.loadStats();
                    utils.showToast('Tableau de bord actualisé');
                });
            }
        }
    };

    // Product Management
    const products = {
        init: function() {
            this.setupProductForm();
            this.setupProductTable();
            this.setupImageUpload();
            this.setupBulkActions();
        },

        setupProductForm: function() {
            const form = document.getElementById('product-form');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveProduct(form);
            });

            // Validation en temps réel
            const priceInput = form.querySelector('input[name="price"]');
            if (priceInput) {
                priceInput.addEventListener('input', this.validatePrice);
            }
        },

        validatePrice: function(e) {
            const value = parseFloat(e.target.value);
            const errorDiv = e.target.nextElementSibling;
            
            if (isNaN(value) || value <= 0) {
                e.target.classList.add('error');
                if (errorDiv) errorDiv.textContent = 'Le prix doit être un nombre positif';
            } else {
                e.target.classList.remove('error');
                if (errorDiv) errorDiv.textContent = '';
            }
        },

        saveProduct: function(form) {
            const formData = new FormData(form);
            const productId = form.dataset.productId;
            const url = productId ? `/admin/products/${productId}` : '/admin/products';
            const method = productId ? 'PUT' : 'POST';

            if (method === 'PUT') {
                formData.append('_method', 'PUT');
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    utils.showToast(data.message);
                    if (!productId) {
                        form.reset();
                    }
                    this.refreshProductTable();
                } else {
                    utils.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                utils.showToast('Erreur lors de la sauvegarde', 'error');
            });
        },

        setupProductTable: function() {
            const table = document.getElementById('products-table');
            if (!table) return;

            // Boutons de suppression
            table.addEventListener('click', (e) => {
                if (e.target.classList.contains('delete-product')) {
                    const productId = e.target.dataset.productId;
                    utils.confirmDialog('Êtes-vous sûr de vouloir supprimer ce produit ?', () => {
                        this.deleteProduct(productId);
                    });
                }
            });

            // Tri des colonnes
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    this.sortTable(header.dataset.sort);
                });
            });
        },

        deleteProduct: function(productId) {
            fetch(`/admin/products/${productId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    utils.showToast(data.message);
                    this.refreshProductTable();
                } else {
                    utils.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                utils.showToast('Erreur lors de la suppression', 'error');
            });
        },

        setupImageUpload: function() {
            const imageInput = document.getElementById('product-image');
            const previewContainer = document.getElementById('image-preview');
            
            if (!imageInput || !previewContainer) return;

            imageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewContainer.innerHTML = `
                            <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        setupBulkActions: function() {
            const selectAll = document.getElementById('select-all-products');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const bulkDeleteBtn = document.getElementById('bulk-delete');

            if (selectAll) {
                selectAll.addEventListener('change', (e) => {
                    productCheckboxes.forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                });
            }

            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', () => {
                    const selectedProducts = Array.from(productCheckboxes)
                        .filter(checkbox => checkbox.checked)
                        .map(checkbox => checkbox.value);

                    if (selectedProducts.length === 0) {
                        utils.showToast('Veuillez sélectionner au moins un produit', 'error');
                        return;
                    }

                    utils.confirmDialog(
                        `Êtes-vous sûr de vouloir supprimer ${selectedProducts.length} produit(s) ?`,
                        () => this.bulkDeleteProducts(selectedProducts)
                    );
                });
            }
        },

        bulkDeleteProducts: function(productIds) {
            fetch('/admin/products/bulk-delete', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: productIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    utils.showToast(data.message);
                    this.refreshProductTable();
                } else {
                    utils.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                utils.showToast('Erreur lors de la suppression', 'error');
            });
        },

        refreshProductTable: function() {
            location.reload(); // Simple refresh pour l'exemple
        },

        sortTable: function(column) {
            // Implémentation du tri
            console.log('Tri par:', column);
        }
    };

    // Order Management
    const orders = {
        init: function() {
            this.setupOrderTable();
            this.setupStatusUpdate();
            this.setupOrderFilters();
        },

        setupOrderTable: function() {
            const table = document.getElementById('orders-table');
            if (!table) return;

            table.addEventListener('click', (e) => {
                if (e.target.classList.contains('view-order')) {
                    const orderId = e.target.dataset.orderId;
                    this.viewOrderDetails(orderId);
                }
            });
        },

        setupStatusUpdate: function() {
            const statusSelects = document.querySelectorAll('.order-status-select');
            statusSelects.forEach(select => {
                select.addEventListener('change', (e) => {
                    const orderId = e.target.dataset.orderId;
                    const newStatus = e.target.value;
                    this.updateOrderStatus(orderId, newStatus);
                });
            });
        },

        updateOrderStatus: function(orderId, status) {
            fetch(`/admin/orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    utils.showToast(data.message);
                } else {
                    utils.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                utils.showToast('Erreur lors de la mise à jour', 'error');
            });
        },

        setupOrderFilters: function() {
            const statusFilter = document.getElementById('status-filter');
            const dateFilter = document.getElementById('date-filter');

            if (statusFilter) {
                statusFilter.addEventListener('change', this.filterOrders);
            }

            if (dateFilter) {
                dateFilter.addEventListener('change', this.filterOrders);
            }
        },

        filterOrders: function() {
            const status = document.getElementById('status-filter')?.value;
            const date = document.getElementById('date-filter')?.value;
            
            const params = new URLSearchParams();
            if (status) params.append('status', status);
            if (date) params.append('date', date);

            window.location.href = `/admin/orders?${params.toString()}`;
        },

        viewOrderDetails: function(orderId) {
            window.location.href = `/admin/orders/${orderId}`;
        }
    };

    // User Management
    const users = {
        init: function() {
            this.setupUserTable();
            this.setupUserFilters();
        },

        setupUserTable: function() {
            const table = document.getElementById('users-table');
            if (!table) return;

            table.addEventListener('click', (e) => {
                if (e.target.classList.contains('toggle-user-status')) {
                    const userId = e.target.dataset.userId;
                    this.toggleUserStatus(userId);
                }
            });
        },

        toggleUserStatus: function(userId) {
            fetch(`/admin/users/${userId}/toggle-status`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    utils.showToast(data.message);
                    location.reload();
                } else {
                    utils.showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                utils.showToast('Erreur lors de la mise à jour', 'error');
            });
        },

        setupUserFilters: function() {
            const roleFilter = document.getElementById('role-filter');
            const statusFilter = document.getElementById('user-status-filter');

            if (roleFilter) {
                roleFilter.addEventListener('change', this.filterUsers);
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', this.filterUsers);
            }
        },

        filterUsers: function() {
            const role = document.getElementById('role-filter')?.value;
            const status = document.getElementById('user-status-filter')?.value;
            
            const params = new URLSearchParams();
            if (role) params.append('role', role);
            if (status) params.append('status', status);

            window.location.href = `/admin/users?${params.toString()}`;
        }
    };

    // Sidebar Management
    const sidebar = {
        init: function() {
            this.setupToggle();
            this.setupActiveMenu();
        },

        setupToggle: function() {
            const toggleBtn = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                });

                // Restaurer l'état du sidebar
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    sidebar.classList.add('collapsed');
                }
            }
        },

        setupActiveMenu: function() {
            const currentPath = window.location.pathname;
            const menuItems = document.querySelectorAll('.sidebar-menu a');

            menuItems.forEach(item => {
                if (item.getAttribute('href') === currentPath) {
                    item.classList.add('active');
                    item.closest('.menu-item')?.classList.add('active');
                }
            });
        }
    };

    // Global Event Listeners
    const globalEvents = {
        init: function() {
            this.setupLogout();
            this.setupNotifications();
            this.setupKeyboardShortcuts();
        },

        setupLogout: function() {
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    utils.confirmDialog('Êtes-vous sûr de vouloir vous déconnecter ?', () => {
                        document.getElementById('logout-form').submit();
                    });
                });
            }
        },

        setupNotifications: function() {
            // Vérifier les nouvelles notifications
            setInterval(() => {
                fetch('/admin/notifications/check', {
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        const badge = document.getElementById('notification-badge');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'block';
                        }
                    }
                })
                .catch(error => console.error('Erreur notifications:', error));
            }, 30000); // Vérifier toutes les 30 secondes
        },

        setupKeyboardShortcuts: function() {
            document.addEventListener('keydown', (e) => {
                // Ctrl + / pour ouvrir/fermer le sidebar
                if (e.ctrlKey && e.key === '/') {
                    e.preventDefault();
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('collapsed');
                    }
                }

                // Échap pour fermer les modales
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(modal => modal.classList.remove('show'));
                }
            });
        }
    };

    // Initialize modules based on current page
    const currentPage = document.body.dataset.page;
    
    // Always initialize global components
    sidebar.init();
    globalEvents.init();

    // Initialize page-specific modules
    switch (currentPage) {
        case 'dashboard':
            dashboard.init();
            break;
        case 'products':
            products.init();
            break;
        case 'orders':
            orders.init();
            break;
        case 'users':
            users.init();
            break;
    }

    // Export functions for global access
    window.adminJS = {
        utils,
        dashboard,
        products,
        orders,
        users
    };
});