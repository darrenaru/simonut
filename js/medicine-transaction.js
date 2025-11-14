// ================================================
// medicine-transaction.js - Multiple Items Support
// ================================================

const API_URL = 'php/transaction-api.php';

let transaksiData = [];
let obatList = [];
let staffList = [];
let currentTab = 'keluar';
let editingId = null;
let cartItems = []; // Array untuk menyimpan item yang akan ditambahkan

// ================================================
// Load data dari database
// ================================================
async function loadTransaksi() {
    try {
        const response = await fetch(`${API_URL}?action=read&tipe=${currentTab}`);
        const result = await response.json();
        
        if (result.success) {
            transaksiData = result.data;
            renderTable(document.getElementById('searchInput').value);
            updateSummary();
        } else {
            console.error('Error loading data:', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data');
    }
}

// ================================================
// Load daftar obat
// ================================================
async function loadObatList() {
    try {
        const response = await fetch('php/api.php?action=read');
        const result = await response.json();
        
        if (result.success) {
            obatList = result.data;
            populateObatDropdown();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// ================================================
// Load daftar staff
// ================================================
async function loadStaffList() {
    try {
        const response = await fetch(`${API_URL}?action=get_staff`);
        const result = await response.json();
        
        if (result.success) {
            staffList = result.data;
            populateStaffDropdown();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// ================================================
// Populate dropdown obat
// ================================================
function populateObatDropdown() {
    const select = document.getElementById('idObat');
    select.innerHTML = '<option value="">-- Pilih Obat --</option>';
    
    obatList.forEach(obat => {
        const option = document.createElement('option');
        option.value = obat.id;
        option.textContent = `${obat.nama} - ${obat.dosis}`;
        option.dataset.nama = obat.nama;
        option.dataset.dosis = obat.dosis;
        select.appendChild(option);
    });
}

// ================================================
// Populate dropdown staff
// ================================================
function populateStaffDropdown() {
    const select = document.getElementById('idStaff');
    select.innerHTML = '<option value="">-- Pilih Staff --</option>';
    
    staffList.forEach(staff => {
        const option = document.createElement('option');
        option.value = staff.id;
        option.textContent = staff.nama_lengkap; // Menampilkan nama lengkap
        option.dataset.email = staff.email; // Menyimpan email jika diperlukan
        select.appendChild(option);
    });
}

// ================================================
// Update summary cards
// ================================================
async function updateSummary() {
    try {
        const response = await fetch(`${API_URL}?action=summary`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('totalKeluar').textContent = result.data.keluar;
            document.getElementById('totalMasuk').textContent = result.data.masuk;
            document.getElementById('totalTransaksi').textContent = result.data.total;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// ================================================
// Tambah item ke cart
// ================================================
function addToCart() {
    const idObat = document.getElementById('idObat').value;
    const jumlah = document.getElementById('jumlah').value;
    const satuan = document.getElementById('satuan').value;
    
    if (!idObat || !jumlah) {
        alert('Pilih obat dan masukkan jumlah terlebih dahulu');
        return;
    }
    
    const select = document.getElementById('idObat');
    const selectedOption = select.options[select.selectedIndex];
    const namaObat = selectedOption.dataset.nama;
    const dosis = selectedOption.dataset.dosis;
    
    // Cek apakah obat sudah ada di cart
    const existingIndex = cartItems.findIndex(item => item.idObat === idObat);
    
    if (existingIndex !== -1) {
        // Update jumlah jika sudah ada
        cartItems[existingIndex].jumlah = parseInt(cartItems[existingIndex].jumlah) + parseInt(jumlah);
    } else {
        // Tambah item baru
        cartItems.push({
            idObat: idObat,
            namaObat: namaObat,
            dosis: dosis,
            jumlah: parseInt(jumlah),
            satuan: satuan
        });
    }
    
    // Reset form item
    document.getElementById('idObat').value = '';
    document.getElementById('jumlah').value = '';
    document.getElementById('satuan').value = 'unit';
    
    renderCart();
}

// ================================================
// Render cart items
// ================================================
function renderCart() {
    const cartContainer = document.getElementById('cartItems');
    
    if (cartItems.length === 0) {
        cartContainer.innerHTML = '<p class="empty-cart">Belum ada obat yang ditambahkan</p>';
        return;
    }
    
    cartContainer.innerHTML = cartItems.map((item, index) => `
        <div class="cart-item">
            <div class="cart-item-info">
                <strong>${item.namaObat}</strong>
                <small>${item.dosis}</small>
            </div>
            <div class="cart-item-qty">
                ${item.jumlah} ${item.satuan}
            </div>
            <button type="button" class="cart-item-remove" onclick="removeFromCart(${index})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `).join('');
}

// ================================================
// Remove item dari cart
// ================================================
function removeFromCart(index) {
    cartItems.splice(index, 1);
    renderCart();
}

// ================================================
// Toggle tujuan field visibility
// ================================================
function toggleTujuanField() {
    const tujuanGroup = document.getElementById('tujuanGroup');
    const tujuanSelect = document.getElementById('tujuan');
    const tujuanHeader = document.getElementById('tujuanHeader');
    
    if (currentTab === 'keluar') {
        tujuanGroup.style.display = 'block';
        tujuanSelect.required = true;
        tujuanHeader.textContent = 'Tujuan';
    } else {
        tujuanGroup.style.display = 'none';
        tujuanSelect.required = false;
        tujuanSelect.value = '';
        document.getElementById('tujuanLainnyaGroup').style.display = 'none';
        tujuanHeader.textContent = 'Pengirim';
    }
}

// ================================================
// Render tabel
// ================================================
function renderTable(searchTerm = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = transaksiData;

    if (searchTerm) {
        filteredData = filteredData.filter(item => 
            item.nama_obat.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (item.tujuan && item.tujuan.toLowerCase().includes(searchTerm.toLowerCase())) ||
            (item.keterangan && item.keterangan.toLowerCase().includes(searchTerm.toLowerCase())) ||
            item.nama_staff.toLowerCase().includes(searchTerm.toLowerCase())
        );
    }

    if (filteredData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <p>Tidak ada data transaksi ditemukan</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredData.map(item => `
        <tr>
            <td>${formatDate(item.tanggal_transaksi)}</td>
            <td>${item.nama_obat}</td>
            <td>
                <span class="badge ${item.tipe_transaksi}">
                    ${item.jumlah} ${item.satuan}
                </span>
            </td>
            <td>${item.tujuan || '-'}</td>
            <td>${item.keterangan || '-'}</td>
            <td>${item.nama_staff}</td>
            <td>
                <button class="action-btn view-btn" onclick="viewDetail(${item.id})">
                    Detail
                </button>
                <button class="action-btn delete-btn" onclick="deleteTransaksi(${item.id})">
                    Hapus
                </button>
            </td>
        </tr>
    `).join('');
}

// ================================================
// Format tanggal
// ================================================
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// ================================================
// Open modal
// ================================================
function openModal() {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('transaksiForm');
    
    modalTitle.textContent = currentTab === 'keluar' ? 'Catat Obat Keluar' : 'Catat Obat Masuk';
    form.reset();
    cartItems = [];
    renderCart();
    
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggalTransaksi').value = today;
    
    toggleTujuanField();
    
    modal.classList.add('show');
}

// ================================================
// Close modal
// ================================================
function closeModal() {
    document.getElementById('modal').classList.remove('show');
    editingId = null;
    cartItems = [];
}

// ================================================
// View detail
// ================================================
async function viewDetail(id) {
    try {
        const response = await fetch(`${API_URL}?action=read_single&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const item = result.data;
            const detailContent = document.getElementById('detailContent');
            
            let detailHTML = `
                <div class="detail-row">
                    <span class="detail-label">Tipe Transaksi:</span>
                    <span class="detail-value">
                        <span class="badge ${item.tipe_transaksi}">
                            ${item.tipe_transaksi.toUpperCase()}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nama Obat:</span>
                    <span class="detail-value">${item.nama_obat}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dosis:</span>
                    <span class="detail-value">${item.dosis}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Kategori:</span>
                    <span class="detail-value">${item.kategori}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jumlah:</span>
                    <span class="detail-value">${item.jumlah} ${item.satuan}</span>
                </div>`;
            
            if (item.tipe_transaksi === 'keluar' && item.tujuan) {
                detailHTML += `
                <div class="detail-row">
                    <span class="detail-label">Tujuan Distribusi:</span>
                    <span class="detail-value">${item.tujuan}</span>
                </div>`;
            }
            
            detailHTML += `
                <div class="detail-row">
                    <span class="detail-label">Tanggal Transaksi:</span>
                    <span class="detail-value">${formatDate(item.tanggal_transaksi)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Staff:</span>
                    <span class="detail-value">${item.nama_staff}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Keterangan:</span>
                    <span class="detail-value">${item.keterangan || '-'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu Input:</span>
                    <span class="detail-value">${new Date(item.tanggal_dibuat).toLocaleString('id-ID')}</span>
                </div>
            `;
            
            detailContent.innerHTML = detailHTML;
            document.getElementById('detailModal').classList.add('show');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat detail');
    }
}

// ================================================
// Close detail modal
// ================================================
function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
}

// ================================================
// Delete transaksi
// ================================================
async function deleteTransaksi(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Data berhasil dihapus');
            loadTransaksi();
        } else {
            alert('Gagal menghapus data: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus data');
    }
}

// ================================================
// Event listener untuk form submit
// ================================================
document.getElementById('transaksiForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (cartItems.length === 0) {
        alert('Tambahkan minimal 1 obat terlebih dahulu');
        return;
    }

    const tanggalTransaksi = document.getElementById('tanggalTransaksi').value;
    const idStaff = document.getElementById('idStaff').value;
    const keterangan = document.getElementById('keterangan').value;
    
    if (!idStaff) {
        alert('Pilih staff terlebih dahulu');
        return;
    }
    
    let tujuan = null;
    if (currentTab === 'keluar') {
        const tujuanSelect = document.getElementById('tujuan').value;
        if (!tujuanSelect) {
            alert('Tujuan distribusi wajib diisi');
            return;
        }
        if (tujuanSelect === 'Lainnya') {
            tujuan = document.getElementById('tujuanLainnya').value;
        } else {
            tujuan = tujuanSelect;
        }
    }

    try {
        const requestData = {
            items: cartItems,
            tipe_transaksi: currentTab,
            tanggal_transaksi: tanggalTransaksi,
            id_staff: idStaff,
            keterangan: keterangan
        };
        
        if (currentTab === 'keluar' && tujuan) {
            requestData.tujuan = tujuan;
        }
        
        const response = await fetch(`${API_URL}?action=create_batch`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();
        
        if (result.success) {
            alert(`Berhasil menyimpan ${cartItems.length} transaksi`);
            closeModal();
            loadTransaksi();
        } else {
            alert('Gagal menyimpan data: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    }
});

// ================================================
// Event listener untuk tab navigation
// ================================================
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        currentTab = tab;
        
        const btnText = document.getElementById('btnText');
        btnText.textContent = tab === 'keluar' ? 'Catat Obat Keluar' : 'Catat Obat Masuk';
        
        const tujuanHeader = document.getElementById('tujuanHeader');
        tujuanHeader.textContent = tab === 'keluar' ? 'Tujuan' : 'Pengirim';
        
        loadTransaksi();
    });
});

// ================================================
// Event listener untuk tujuan select
// ================================================
document.getElementById('tujuan').addEventListener('change', function() {
    const tujuanLainnyaGroup = document.getElementById('tujuanLainnyaGroup');
    const tujuanLainnyaInput = document.getElementById('tujuanLainnya');
    
    if (this.value === 'Lainnya') {
        tujuanLainnyaGroup.style.display = 'block';
        tujuanLainnyaInput.required = true;
    } else {
        tujuanLainnyaGroup.style.display = 'none';
        tujuanLainnyaInput.required = false;
        tujuanLainnyaInput.value = '';
    }
});

// ================================================
// Event listener untuk pencarian
// ================================================
document.getElementById('searchInput').addEventListener('input', function(e) {
    renderTable(e.target.value);
});

// ================================================
// Event listener untuk filter tanggal
// ================================================
document.getElementById('filterDate').addEventListener('change', async function(e) {
    const date = e.target.value;
    
    if (date) {
        try {
            const response = await fetch(`${API_URL}?action=read&tipe=${currentTab}&tanggal=${date}`);
            const result = await response.json();
            
            if (result.success) {
                transaksiData = result.data;
                renderTable(document.getElementById('searchInput').value);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    } else {
        loadTransaksi();
    }
});

// ================================================
// Close modals on outside click
// ================================================
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

// ================================================
// Load data saat halaman dimuat
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    loadObatList();
    loadStaffList();
    loadTransaksi();
    toggleTujuanField();
});