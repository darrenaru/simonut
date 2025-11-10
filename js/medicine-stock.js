// Data obat
let obatData = [
    { id: 1, nama: "Paracetamol", dosis: "500 mg", kategori: "Analgesik / Antipiretik" },
    { id: 2, nama: "Amoxicillin", dosis: "500 mg", kategori: "Antibiotik" },
    { id: 3, nama: "OBH Combi", dosis: "100 ml", kategori: "Obat batuk" }
];

let editingId = null;
let currentFilter = 'all';
let deleteTargetId = null;

// Render tabel
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

// Buka modal
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

// Tutup modal
function closeModal() {
    const modal = document.getElementById('modal');
    modal.classList.remove('show');
    editingId = null;
}

// Edit obat
function editObat(id) {
    openModal(id);
}

// Hapus obat
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

// Tutup modal konfirmasi hapus
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteTargetId = null;
}

// Konfirmasi hapus
function confirmDelete() {
    const reason = document.getElementById('deleteReason').value.trim();
    
    if (!reason) {
        alert('Alasan penghapusan wajib diisi!');
        return;
    }
    
    if (deleteTargetId) {
        obatData = obatData.filter(obat => obat.id !== deleteTargetId);
        renderTable(currentFilter, document.getElementById('searchInput').value);
        closeDeleteModal();
    }
}

// Filter kategori
function filterCategory(category) {
    currentFilter = category;
    const btn = document.getElementById('categoryBtn');
    btn.textContent = category === 'all' ? 'Kategori ▼' : category + ' ▼';
    renderTable(category, document.getElementById('searchInput').value);
    document.getElementById('categoryDropdown').classList.remove('show');
}

// Event listener untuk form submit
document.getElementById('obatForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const nama = document.getElementById('namaObat').value;
    const dosis = document.getElementById('dosis').value;
    const kategori = document.getElementById('kategori').value;

    if (editingId) {
        // Update data existing
        const index = obatData.findIndex(o => o.id === editingId);
        obatData[index] = { id: editingId, nama, dosis, kategori };
    } else {
        // Tambah data baru
        const newId = obatData.length > 0 ? Math.max(...obatData.map(o => o.id)) + 1 : 1;
        obatData.push({ id: newId, nama, dosis, kategori });
    }

    closeModal();
    renderTable(currentFilter, document.getElementById('searchInput').value);
});

// Event listener untuk pencarian
document.getElementById('searchInput').addEventListener('input', function(e) {
    renderTable(currentFilter, e.target.value);
});

// Event listener untuk dropdown kategori
document.getElementById('categoryBtn').addEventListener('click', function() {
    const dropdown = document.getElementById('categoryDropdown');
    dropdown.classList.toggle('show');
});

// Tutup dropdown jika klik di luar
window.addEventListener('click', function(e) {
    if (!e.target.matches('.dropdown-btn')) {
        const dropdown = document.getElementById('categoryDropdown');
        if (dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
});

// Tutup modal jika klik di luar modal content
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeModal();
    }
});

// Tutup modal delete jika klik di luar modal content
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) {
        closeDeleteModal();
    }
});

// Render tabel saat halaman dimuat
renderTable();