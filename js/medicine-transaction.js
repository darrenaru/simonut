// ================================================
// medicine-transaction.js - Connected to Database
// ================================================

const API_URL = 'php/transaction-api.php';

let transaksiData = [];
let obatList = [];
let currentTab = 'keluar';
let editingId = null;

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
// Populate dropdown obat
// ================================================
function populateObatDropdown() {
    const select = document.getElementById('idObat');
    select.innerHTML = '<option value="">-- Pilih Obat --</option>';
    
    obatList.forEach(obat => {
        const option = document.createElement('option');
        option.value = obat.id;
        option.textContent = `${obat.nama} - ${obat.dosis}`;
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
// Render tabel
// ================================================
function renderTable(searchTerm = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = transaksiData;

    // Filter berdasarkan pencarian
    if (searchTerm) {
        filteredData = filteredData.filter(item => 
            item.nama_obat.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.keterangan.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.nama_staff.toLowerCase().includes(searchTerm.toLowerCase())
        );
    }

    // Jika tidak ada data
    if (filteredData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    <p>Tidak ada data transaksi ditemukan</p>
                </td>
            </tr>
        `;
        return;
    }

    // Render data
    tbody.innerHTML = filteredData.map(item => `
        <tr>
            <td>${formatDate(item.tanggal_transaksi)}</td>
            <td>${item.nama_obat}</td>
            <td>
                <span class="badge ${item.tipe_transaksi}">
                    ${item.jumlah} ${item.satuan}
                </span>
            </td>
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
    
    // Set tanggal hari ini
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggalTransaksi').value = today;
    
    modal.classList.add('show');
}

// ================================================
// Close modal
// ================================================
function closeModal() {
    document.getElementById('modal').classList.remove('show');
    editingId = null;
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
            
            detailContent.innerHTML = `
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
                </div>
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

    const idObat = document.getElementById('idObat').value;
    const jumlah = document.getElementById('jumlah').value;
    const satuan = document.getElementById('satuan').value;
    const tanggalTransaksi = document.getElementById('tanggalTransaksi').value;
    const namaStaff = document.getElementById('namaStaff').value;
    const keterangan = document.getElementById('keterangan').value;

    try {
        const response = await fetch(`${API_URL}?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_obat: idObat,
                tipe_transaksi: currentTab,
                jumlah: jumlah,
                satuan: satuan,
                tanggal_transaksi: tanggalTransaksi,
                nama_staff: namaStaff,
                keterangan: keterangan
            })
        });

        const result = await response.json();
        
        if (result.success) {
            alert('Data berhasil disimpan');
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
        
        // Update active state
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Update current tab
        currentTab = tab;
        
        // Update button text
        const btnText = document.getElementById('btnText');
        btnText.textContent = tab === 'keluar' ? 'Catat Obat Keluar' : 'Catat Obat Masuk';
        
        // Reload data
        loadTransaksi();
    });
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
    loadTransaksi();
});