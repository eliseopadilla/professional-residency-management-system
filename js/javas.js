document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        content.classList.toggle('active');
        
        // Animación del botón hamburguesa a X
        menuToggle.classList.toggle('active');
    });
});
// Datos iniciales (simulando una base de datos)
let users = [
    { 
        id: 1, 
        username: "admin", 
        email: "admin@empresa.com", 
        role: "admin", 
        permissions: ["create", "read", "update", "delete", "manage_users", "manage_content", "view_reports", "export_data"] 
    },
    { 
        id: 2, 
        username: "editor1", 
        email: "editor@empresa.com", 
        role: "editor", 
        permissions: ["create", "read", "update", "manage_content"] 
    },
    { 
        id: 3, 
        username: "usuario1", 
        email: "usuario@empresa.com", 
        role: "user", 
        permissions: ["read"] 
    }
];

// Variables globales
let currentUserId = null;
let isEditMode = false;

// Elementos del DOM
const tableBody = document.querySelector('#usersTable tbody');
const addUserBtn = document.getElementById('addUserBtn');
const userModal = document.getElementById('userModal');
const modalTitle = document.getElementById('modalTitle');
const userForm = document.getElementById('userForm');
const userIdInput = document.getElementById('userId');
const usernameInput = document.getElementById('username');
const emailInput = document.getElementById('email');
const userRoleInput = document.getElementById('userRole');
const cancelBtn = document.getElementById('cancelBtn');
const closeBtn = document.querySelector('.close');
const searchInput = document.getElementById('searchInput');

// Funciones
function renderTable(usersToRender = users) {
    tableBody.innerHTML = '';
    
    if (usersToRender.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="6" style="text-align: center;">No se encontraron usuarios</td>`;
        tableBody.appendChild(row);
        return;
    }
    
    usersToRender.forEach(user => {
        const row = document.createElement('tr');
        
        // Determinar la clase del badge según el rol
        let badgeClass = '';
        switch(user.role) {
            case 'admin': badgeClass = 'badge-admin'; break;
            case 'editor': badgeClass = 'badge-editor'; break;
            case 'user': badgeClass = 'badge-user'; break;
            case 'guest': badgeClass = 'badge-guest'; break;
            case 'alumno': badgeClass = 'badge-guest'; break;

        }
        
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td><span class="badge ${badgeClass}">${user.role}</span></td>
            <td>${user.permissions.join(', ')}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-primary btn-sm" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </button>
                </div>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

function openModal() {
    userModal.style.display = 'block';
}

function closeModal() {
    userModal.style.display = 'none';
    userForm.reset();
    isEditMode = false;
    currentUserId = null;
    
    // Desmarcar todos los checkboxes de permisos
    document.querySelectorAll('input[name="permissions"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function editUser(id) {
    const user = users.find(u => u.id === id);
    
    if (user) {
        isEditMode = true;
        currentUserId = id;
        modalTitle.textContent = 'Editar Usuario';
        
        // Llenar el formulario con los datos del usuario
        userIdInput.value = user.id;
        usernameInput.value = user.username;
        emailInput.value = user.email;
        userRoleInput.value = user.role;
        
        // Marcar los checkboxes de permisos
        user.permissions.forEach(perm => {
            const checkbox = document.querySelector(`input[name="permissions"][value="${perm}"]`);
            if (checkbox) checkbox.checked = true;
        });
        
        openModal();
    }
}

function deleteUser(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        users = users.filter(user => user.id !== id);
        renderTable();
        
        // Si estamos en modo búsqueda, actualizar la búsqueda
        if (searchInput.value.trim() !== '') {
            searchUsers();
        }
    }
}

function generateId() {
    return users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1;
}

function searchUsers() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    
    if (searchTerm === '') {
        renderTable();
        return;
    }
    
    const filteredUsers = users.filter(user => 
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm) ||
        user.role.toLowerCase().includes(searchTerm) ||
        user.permissions.some(perm => perm.toLowerCase().includes(searchTerm))
    );
    
    renderTable(filteredUsers);
}

// Event Listeners
addUserBtn.addEventListener('click', () => {
    modalTitle.textContent = 'Agregar Usuario';
    openModal();
});

closeBtn.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);

userForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    // Obtener los permisos seleccionados
    const selectedPermissions = [];
    document.querySelectorAll('input[name="permissions"]:checked').forEach(checkbox => {
        selectedPermissions.push(checkbox.value);
    });
    
    const userData = {
        username: usernameInput.value,
        email: emailInput.value,
        role: userRoleInput.value,
        permissions: selectedPermissions
    };
    
    if (isEditMode) {
        // Actualizar usuario existente
        const index = users.findIndex(u => u.id === currentUserId);
        if (index !== -1) {
            users[index] = { ...userData, id: currentUserId };
        }
    } else {
        // Agregar nuevo usuario
        userData.id = generateId();
        users.push(userData);
    }
    
    renderTable();
    closeModal();
    
    // Si estamos en modo búsqueda, actualizar la búsqueda
    if (searchInput.value.trim() !== '') {
        searchUsers();
    }
});

// Búsqueda en tiempo real
searchInput.addEventListener('input', searchUsers);

// Cerrar modal haciendo clic fuera del contenido
window.addEventListener('click', (e) => {
    if (e.target === userModal) {
        closeModal();
    }
});

// Cargar roles y permisos al cambiar el rol
userRoleInput.addEventListener('change', function() {
    const role = this.value;
    const permissionCheckboxes = document.querySelectorAll('input[name="permissions"]');
    
    // Desmarcar todos los permisos primero
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Asignar permisos predeterminados según el rol
    switch(role) {
        case 'admin':
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            break;
        case 'editor':
            ['create', 'read', 'update', 'manage_content'].forEach(perm => {
                const checkbox = document.querySelector(`input[name="permissions"][value="${perm}"]`);
                if (checkbox) checkbox.checked = true;
            });
            break;
        case 'user':
            document.querySelector('input[name="permissions"][value="read"]').checked = true;
            break;
        case 'guest':
            // Por defecto, los invitados no tienen permisos
            break;
         case 'alumno':
            // Por defecto, los invitados no tienen permisos
             
    }
});

// Inicializar la tabla
renderTable();
