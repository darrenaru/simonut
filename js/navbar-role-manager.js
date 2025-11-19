// ================================================
// navbar-role-manager.js
// Script untuk mengatur navbar berdasarkan role user
// Tambahkan script ini di akhir body SETIAP halaman (kecuali login.html)
// ================================================

(function() {
    'use strict';
    
    console.log('Navbar Role Manager loaded');
    
    // Fungsi untuk setup navbar berdasarkan role
    function setupNavbarByRole() {
        const user = localStorage.getItem('user');
        
        if (!user) {
            return;
        }
        
        try {
            const userData = JSON.parse(user);
            const role = userData.role;
            
            // Update profile display
            updateProfileDisplay(userData);
            
            // Setup menu berdasarkan role
            if (role === 'admin') {
                setupAdminNavbar();
            } else if (role === 'staff') {
                setupStaffNavbar();
            }
            
            // Setup logout buttons
            setupLogoutButtons();
            
        } catch (error) {
            console.error('Error parsing user data:', error);
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        }
    }
    
    // Update informasi profile di navbar
    function updateProfileDisplay(userData) {
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
    
    // Setup navbar untuk admin
    function setupAdminNavbar() {
        const navbarMenu = document.querySelector('.navbar-menu');
        if (!navbarMenu) return;
        
        navbarMenu.innerHTML = `
            <a href="index.html" class="nav-link ${isCurrentPage('index.html') ? 'active' : ''}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-dropdown">
                <a href="#" class="nav-link ${isCurrentPage(['medicine-stock.html', 'medicine-transaction.html']) ? 'active' : ''}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Transaksi Obat</span>
                    <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </a>
                <div class="dropdown-menu">
                    <a href="medicine-stock.html" class="dropdown-item">Daftar Obat</a>
                    <a href="medicine-transaction.html" class="dropdown-item">Obat Keluar/Masuk</a>
                </div>
            </div>

            <a href="calendar.html" class="nav-link ${isCurrentPage('calendar.html') ? 'active' : ''}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Kalender</span>
            </a>

            <a href="users-management.html" class="nav-link ${isCurrentPage('users-management.html') ? 'active' : ''}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Manajemen Users</span>
            </a>
        `;
    }
    
    // Setup navbar untuk staff
    function setupStaffNavbar() {
        const navbarMenu = document.querySelector('.navbar-menu');
        if (!navbarMenu) return;
        
        navbarMenu.innerHTML = `
            <a href="medicine-transaction.html" class="nav-link ${isCurrentPage('medicine-transaction.html') ? 'active' : ''}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Halaman Utama</span>
            </a>

            <a href="medicine-transaction.html" class="nav-link ${isCurrentPage('medicine-transaction.html') ? 'active' : ''}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Transaksi Obat</span>
            </a>

            <a href="calendar.html" class="nav-link ${isCurrentPage('calendar.html') ? 'active' : ''}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Kalender</span>
            </a>
        `;
    }
    
    // Check apakah halaman saat ini
    function isCurrentPage(page) {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        
        if (Array.isArray(page)) {
            return page.includes(currentPage);
        }
        
        return currentPage === page;
    }
    
    // Setup tombol logout
    function setupLogoutButtons() {
        document.querySelectorAll('.logout').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                handleLogout();
            });
        });
    }
    
    // Handle logout
    async function handleLogout() {
        if (confirm('Apakah Anda yakin ingin keluar?')) {
            try {
                const response = await fetch('php/auth-api.php?action=logout', {
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
    
    // Jalankan saat halaman dimuat
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupNavbarByRole);
    } else {
        setupNavbarByRole();
    }
    
})();