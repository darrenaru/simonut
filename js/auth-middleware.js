// ================================================
// auth-middleware.js
// Middleware untuk cek authentication dan authorization
// Include di semua halaman yang memerlukan login
// ================================================

const API_URL = 'php/auth-api.php';

// Definisi halaman dan role yang bisa mengakses
const PAGE_ACCESS = {
    'index.html': ['admin'],
    'medicine-stock.html': ['admin'],
    'medicine-transaction.html': ['admin', 'staff'],
    'users-management.html': ['admin']
};

// Function untuk check authentication
async function checkAuth() {
    const user = localStorage.getItem('user');
    
    if (!user) {
        // User belum login
        redirectToLogin();
        return false;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=check`);
        const result = await response.json();
        
        if (!result.success) {
            // Session expired
            localStorage.removeItem('user');
            redirectToLogin();
            return false;
        }
        
        // Check authorization
        const currentPage = window.location.pathname.split('/').pop();
        const userData = JSON.parse(user);
        
        if (PAGE_ACCESS[currentPage] && !PAGE_ACCESS[currentPage].includes(userData.role)) {
            alert('Anda tidak memiliki akses ke halaman ini!');
            // Redirect ke halaman sesuai role
            const redirectUrl = userData.role === 'admin' ? 'index.html' : 'medicine-transaction.html';
            window.location.href = redirectUrl;
            return false;
        }
        
        // Update profile info di navbar jika ada
        updateNavbarProfile(userData);
        
        return true;
    } catch (error) {
        console.error('Error checking auth:', error);
        redirectToLogin();
        return false;
    }
}

// Function untuk redirect ke login
function redirectToLogin() {
    if (!window.location.pathname.includes('login.html')) {
        alert('Silakan login terlebih dahulu');
        window.location.href = 'login.html';
    }
}

// Function untuk update profile di navbar
function updateNavbarProfile(userData) {
    const profileName = document.querySelector('.profile-name');
    const profileInfoName = document.querySelector('.profile-info-name');
    const profileInfoEmail = document.querySelector('.profile-info-email');
    
    if (profileName) {
        profileName.textContent = userData.role === 'admin' ? 'Admin' : 'Staff';
    }
    
    if (profileInfoName) {
        profileInfoName.textContent = userData.nama_lengkap;
    }
    
    if (profileInfoEmail) {
        profileInfoEmail.textContent = userData.email;
    }
}

// Function untuk logout
async function handleLogout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        try {
            const response = await fetch(`${API_URL}?action=logout`, {
                method: 'POST'
            });
            
            const result = await response.json();
            
            if (result.success) {
                localStorage.removeItem('user');
                alert('Anda telah keluar');
                window.location.href = 'login.html';
            }
        } catch (error) {
            console.error('Error:', error);
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        }
    }
}

// Setup logout buttons
function setupLogoutButtons() {
    const logoutButtons = document.querySelectorAll('.logout, a[href*="logout"]');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            handleLogout();
        });
    });
}

// Function untuk hide menu yang tidak sesuai role
function restrictMenuByRole() {
    const user = localStorage.getItem('user');
    if (!user) return;
    
    const userData = JSON.parse(user);
    
    // Jika staff, sembunyikan menu yang hanya untuk admin
    if (userData.role === 'staff') {
        // Hide menu Users Management
        const userManagementLink = document.querySelector('a[href="users-management.html"]');
        if (userManagementLink) {
            userManagementLink.closest('.nav-link')?.remove();
        }
        
        // Hide menu Stok Obat (Daftar Obat)
        const stockLink = document.querySelector('a[href="medicine-stock.html"]');
        if (stockLink) {
            stockLink.closest('.dropdown-item')?.remove();
        }
    }
}

// Run authentication check on page load
window.addEventListener('DOMContentLoaded', async function() {
    const isAuthenticated = await checkAuth();
    
    if (isAuthenticated) {
        setupLogoutButtons();
        restrictMenuByRole();
    }
});

// Export functions for use in other scripts
window.authMiddleware = {
    checkAuth,
    handleLogout,
    updateNavbarProfile
};