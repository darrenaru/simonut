// ================================================
// medicine-stock.js - Connected to Database
// ================================================

// URL API
const API_URL = 'php/api.php';

// Data obat (akan diisi dari database)
let obatData = [];
let editingId = null;
let currentFilter = 'all';
let deleteTargetId = null;

// ================================================
// Fungsi untuk load data dari database
// ================================================
async function loadData() {
    try {
        const response = await fetch(`${API_URL}?action=read`);
        const result = await response.json();
        
        if (result.success) {
            obatData = result.data;
            renderTable(currentFilter, document.getElementById('searchInput').value);
        } else {
            console.error('Error loading data:', result.message);
            alert('Gagal memuat data: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data');
    }
}

// ================================================
// Render tabel
// ================================================
function renderTable(filter = 'all', searchTerm = '') {
    const tbody = document.getElementById('tableBody');
    let filteredData = obatData;

    // Filter berdasarkan kategori
    if (filter !== 'all') {
        filteredData = filteredData.filter(obat => obat.kategori === filter);
    }

    // Filter berdasarkan pencarian
    if (searchTerm) {
        filteredData = filteredData.filter(obat => 
            obat.nama.toLowerCase().includes(searchTerm.toLowerCase()) ||
            obat.dosis.toLowerCase().includes(searchTerm.toLowerCase()) ||
            obat.kategori.toLowerCase().includes(searchTerm.toLowerCase())
        );
    }

    // Jika tidak ada data
    if (filteredData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-state">
                    <p>Tidak ada data obat ditemukan</p>
                </td>
            </tr>
        `;
        return;
    }

    // Render data
    tbody.innerHTML = filteredData.map(obat => `
        <tr>
            <td>${obat.nama}</td>
            <td>${obat.dosis}</td>
            <td>${obat.kategori}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editObat(${obat.id})">Edit</button>
                <button class="action-btn delete-btn" onclick="deleteObat(${obat.id})">Hapus</button>
            </td>
        </tr>
    `).join('');
}

// ================================================
// Buka modal
// ================================================
function openModal(id = null) {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('obatForm');

    if (id) {
        // Mode edit
        const obat = obatData.find(o => o.id === id);
        modalTitle.textContent = 'Edit Obat';
        document.getElementById('namaObat').value = obat.nama;
        document.getElementById('dosis').value = obat.dosis;
        document.getElementById('kategori').value = obat.kategori;
        editingId = id;
    } else {
        // Mode tambah
        modalTitle.textContent = 'Tambah Obat Baru';
        form.reset();
        editingId = null;
    }

    modal.classList.add('show');
}

// ================================================
// Tutup modal
// ================================================
function closeModal() {
    const modal = document.getElementById('modal');
    modal.classList.remove('show');
    editingId = null;
}

// ================================================
// Edit obat
// ================================================
function editObat(id) {
    openModal(id);
}

// ================================================
// Hapus obat
// ================================================
function deleteObat(id) {
    const obat = obatData.find(o => o.id === id);
    if (obat) {
        deleteTargetId = id;
        document.getElementById('deleteNama').textContent = obat.nama;
        document.getElementById('deleteDosis').textContent = obat.dosis;
        document.getElementById('deleteKategori').textContent = obat.kategori;
        document.getElementById('deleteReason').value = '';
        document.getElementById('deleteModal').classList.add('show');
    }
}

// ================================================
// Tutup modal konfirmasi hapus
// ================================================
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteTargetId = null;
}

// ================================================
// Konfirmasi hapus
// ================================================
async function confirmDelete() {
    const reason = document.getElementById('deleteReason').value.trim();
    
    if (!reason) {
        alert('Alasan penghapusan wajib diisi!');
        return;
    }
    
    if (deleteTargetId) {
        try {
            const response = await fetch(`${API_URL}?action=delete`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: deleteTargetId,
                    alasan: reason
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Data berhasil dihapus');
                closeDeleteModal();
                loadData(); // Reload data dari database
            } else {
                alert('Gagal menghapus data: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        }
    }
}

// ================================================
// Filter kategori
// ================================================
function filterCategory(category) {
    currentFilter = category;
    const btn = document.getElementById('categoryBtn');
    btn.textContent = category === 'all' ? 'Kategori ▼' : category + ' ▼';
    renderTable(category, document.getElementById('searchInput').value);
    document.getElementById('categoryDropdown').classList.remove('show');
}

// ================================================
// Event listener untuk form submit
// ================================================
document.getElementById('obatForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const nama = document.getElementById('namaObat').value;
    const dosis = document.getElementById('dosis').value;
    const kategori = document.getElementById('kategori').value;

    try {
        let response;
        
        if (editingId) {
            // Update data existing
            response = await fetch(`${API_URL}?action=update`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: editingId,
                    nama: nama,
                    dosis: dosis,
                    kategori: kategori
                })
            });
        } else {
            // Tambah data baru
            response = await fetch(`${API_URL}?action=create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nama: nama,
                    dosis: dosis,
                    kategori: kategori
                })
            });
        }

        const result = await response.json();
        
        if (result.success) {
            alert(editingId ? 'Data berhasil diupdate' : 'Data berhasil ditambahkan');
            closeModal();
            loadData(); // Reload data dari database
        } else {
            alert('Gagal menyimpan data: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    }
});

// ================================================
// Event listener untuk pencarian
// ================================================
document.getElementById('searchInput').addEventListener('input', function(e) {
    renderTable(currentFilter, e.target.value);
});

// ================================================
// Event listener untuk dropdown kategori
// ================================================
document.getElementById('categoryBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('categoryDropdown');
    dropdown.classList.toggle('show');
});

// ================================================
// Tutup dropdown jika klik di luar
// ================================================
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        const dropdown = document.getElementById('categoryDropdown');
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
});

// ================================================
// Tutup modal jika klik di luar modal content
// ================================================
const modal = document.getElementById('modal');
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeModal();
    }
});

// ================================================
// Tutup modal delete jika klik di luar modal content
// ================================================
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) {
        closeDeleteModal();
    }
});

// ================================================
// Mobile menu toggle
// ================================================
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function() {
        console.log('Mobile menu clicked');
    });
}

// ================================================
// Load data saat halaman dimuat
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    loadData();
});