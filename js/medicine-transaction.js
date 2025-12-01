// ================================================
// medicine-transaction.js - WITH EDIT FEATURE
// ================================================

const API_URL = 'php/transaction-api.php';

let transaksiData = [];
let obatList = [];
let staffList = [];
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
        option.dataset.stok = obat.stok || 0;
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
        option.textContent = staff.nama_lengkap;
        option.dataset.email = staff.email;
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
// Check expiry date
// ================================================
function checkExpiryDate(expiryDate) {
    if (!expiryDate) return null;
    
    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return 'expired';
    } else if (diffDays <= 30) {
        return 'expiring-soon';
    }
    return 'valid';
}

// ================================================
// Format expiry date display
// ================================================
function formatExpiryDisplay(expiryDate) {
    if (!expiryDate) return '';
    
    const status = checkExpiryDate(expiryDate);
    const dateStr = formatDate(expiryDate);
    
    if (status === 'expired') {
        return `<span class="expiry-warning">⚠️ Kedaluwarsa: ${dateStr}</span>`;
    } else if (status === 'expiring-soon') {
        return `<span class="expiry-soon">⏰ Kedaluwarsa: ${dateStr}</span>`;
    }
    return `<small>Kedaluwarsa: ${dateStr}</small>`;
}

// ================================================
// Toggle tujuan field visibility
// ================================================
function toggleTujuanField() {
    const tujuanGroup = document.getElementById('tujuanGroup');
    const tujuanSelect = document.getElementById('tujuan');
    const kedaluwarsaGroup = document.getElementById('tanggalKedaluwarsa-group');
    const kedaluwarsaInput = document.getElementById('tanggalKedaluwarsa');
    
    if (currentTab === 'keluar') {
        tujuanGroup.style.display = 'block';
        tujuanSelect.required = true;
        kedaluwarsaGroup.style.display = 'none';
        kedaluwarsaInput.required = false;
        kedaluwarsaInput.value = '';
    } else {
        tujuanGroup.style.display = 'none';
        tujuanSelect.required = false;
        tujuanSelect.value = '';
        document.getElementById('tujuanLainnyaGroup').style.display = 'none';
        kedaluwarsaGroup.style.display = 'block';
        kedaluwarsaInput.required = true;
    }
    
    updateTableHeaders();
}

// ================================================
// Update table headers
// ================================================
function updateTableHeaders() {
    const tujuanHeader = document.getElementById('tujuanHeader');
    const kedaluwarsaHeader = document.getElementById('kedaluwarsaHeader');
    
    if (currentTab === 'keluar') {
        if (tujuanHeader) tujuanHeader.style.display = '';
        if (kedaluwarsaHeader) kedaluwarsaHeader.style.display = 'none';
    } else {
        if (tujuanHeader) tujuanHeader.style.display = 'none';
        if (kedaluwarsaHeader) kedaluwarsaHeader.style.display = '';
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
        const colspan = '8';
        tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" class="empty-state">
                    <p>Tidak ada data transaksi ditemukan</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredData.map(item => {
        let tujuanCell = '';
        let expiryCell = '';
        
        if (currentTab === 'keluar') {
            tujuanCell = `<td>${item.tujuan || '-'}</td>`;
        }
        
        if (currentTab === 'masuk') {
            if (item.tanggal_kedaluwarsa) {
                const status = checkExpiryDate(item.tanggal_kedaluwarsa);
                const dateStr = formatDate(item.tanggal_kedaluwarsa);
                
                if (status === 'expired') {
                    expiryCell = `<td><span style="color: #ef4444; font-weight: 600;">⚠️ ${dateStr}</span></td>`;
                } else if (status === 'expiring-soon') {
                    expiryCell = `<td><span style="color: #f39c12; font-weight: 600;">⏰ ${dateStr}</span></td>`;
                } else {
                    expiryCell = `<td>${dateStr}</td>`;
                }
            } else {
                expiryCell = '<td>-</td>';
            }
        }
        
        return `
        <tr>
            <td>${formatDate(item.tanggal_transaksi)}</td>
            <td>${item.nama_obat}</td>
            <td>
                <span class="badge ${item.tipe_transaksi}">
                    ${item.jumlah} ${item.satuan}
                </span>
            </td>
            ${tujuanCell}
            ${expiryCell}
            <td>${item.keterangan || '-'}</td>
            <td>${item.nama_staff}</td>
            <td>
                <button class="action-btn view-btn" onclick="viewDetail(${item.id})">
                    Detail
                </button>
                <button class="action-btn edit-btn" onclick="editTransaksi(${item.id})">
                    Edit
                </button>
                <button class="action-btn delete-btn" onclick="deleteTransaksi(${item.id})">
                    Hapus
                </button>
            </td>
        </tr>
    `;
    }).join('');
    
    updateTableHeaders();
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
    const submitBtnText = document.getElementById('submitBtnText');
    
    editingId = null;
    modalTitle.textContent = currentTab === 'keluar' ? 'Catat Obat Keluar' : 'Catat Obat Masuk';
    submitBtnText.textContent = 'Simpan Transaksi';
    form.reset();
    
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggalTransaksi').value = today;
    
    toggleTujuanField();
    loadObatList();
    
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
// Edit transaksi
// ================================================
async function editTransaksi(id) {
    try {
        const response = await fetch(`${API_URL}?action=read_single&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const item = result.data;
            editingId = id;
            
            const modal = document.getElementById('modal');
            const modalTitle = document.getElementById('modalTitle');
            const submitBtnText = document.getElementById('submitBtnText');
            
            modalTitle.textContent = item.tipe_transaksi === 'keluar' ? 'Edit Obat Keluar' : 'Edit Obat Masuk';
            submitBtnText.textContent = 'Update Transaksi';
            
            // Fill form with existing data
            await loadObatList(); // Load obat list first
            await loadStaffList(); // Load staff list first
            
            document.getElementById('idObat').value = item.id_obat;
            document.getElementById('jumlah').value = item.jumlah;
            document.getElementById('satuan').value = item.satuan;
            document.getElementById('tanggalTransaksi').value = item.tanggal_transaksi;
            document.getElementById('idStaff').value = item.id_staff;
            document.getElementById('keterangan').value = item.keterangan || '';
            
            if (item.tipe_transaksi === 'keluar') {
                document.getElementById('tujuan').value = item.tujuan || '';
                if (!document.getElementById('tujuan').value && item.tujuan) {
                    document.getElementById('tujuan').value = 'Lainnya';
                    document.getElementById('tujuanLainnya').value = item.tujuan;
                    document.getElementById('tujuanLainnyaGroup').style.display = 'block';
                }
            } else {
                if (item.tanggal_kedaluwarsa) {
                    document.getElementById('tanggalKedaluwarsa').value = item.tanggal_kedaluwarsa;
                }
            }
            
            toggleTujuanField();
            modal.classList.add('show');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data');
    }
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
            
            if (item.tipe_transaksi === 'masuk' && item.tanggal_kedaluwarsa) {
                const status = checkExpiryDate(item.tanggal_kedaluwarsa);
                let expiryDisplay = formatDate(item.tanggal_kedaluwarsa);
                
                if (status === 'expired') {
                    expiryDisplay = `<span style="color: #ef4444; font-weight: 600;">⚠️ ${expiryDisplay} (KEDALUWARSA)</span>`;
                } else if (status === 'expiring-soon') {
                    expiryDisplay = `<span style="color: #f39c12; font-weight: 600;">⏰ ${expiryDisplay} (SEGERA KEDALUWARSA)</span>`;
                }
                
                detailHTML += `
                <div class="detail-row">
                    <span class="detail-label">Tanggal Kedaluwarsa:</span>
                    <span class="detail-value">${expiryDisplay}</span>
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

    const idObat = document.getElementById('idObat').value;
    const jumlah = document.getElementById('jumlah').value;
    const satuan = document.getElementById('satuan').value;
    const tanggalTransaksi = document.getElementById('tanggalTransaksi').value;
    const idStaff = document.getElementById('idStaff').value;
    const keterangan = document.getElementById('keterangan').value;
    
    if (!idObat || !jumlah || !idStaff) {
        alert('Mohon lengkapi semua field yang diperlukan');
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
            if (!tujuan) {
                alert('Mohon isi tujuan lainnya');
                return;
            }
        } else {
            tujuan = tujuanSelect;
        }
    }
    
    let tanggalKedaluwarsa = null;
    if (currentTab === 'masuk') {
        tanggalKedaluwarsa = document.getElementById('tanggalKedaluwarsa').value;
        if (!tanggalKedaluwarsa) {
            alert('Tanggal kedaluwarsa wajib diisi untuk obat masuk');
            return;
        }
    }

    try {
        const requestData = {
            id_obat: idObat,
            id_staff: idStaff,
            tipe_transaksi: currentTab,
            jumlah: parseInt(jumlah),
            satuan: satuan,
            tanggal_transaksi: tanggalTransaksi,
            keterangan: keterangan
        };
        
        if (currentTab === 'keluar' && tujuan) {
            requestData.tujuan = tujuan;
        }
        
        if (currentTab === 'masuk' && tanggalKedaluwarsa) {
            requestData.tanggal_kedaluwarsa = tanggalKedaluwarsa;
        }
        
        let url = `${API_URL}?action=create`;
        let method = 'POST';
        
        if (editingId) {
            url = `${API_URL}?action=update`;
            method = 'PUT';
            requestData.id = editingId;
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();
        
        if (result.success) {
            alert(editingId ? 'Data berhasil diupdate' : 'Data berhasil disimpan');
            closeModal();
            loadTransaksi();
            loadObatList();
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
        
        updateTableHeaders();
        loadTransaksi();
    });
});

// ================================================
// Event listeners
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

document.getElementById('searchInput').addEventListener('input', function(e) {
    renderTable(e.target.value);
});

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