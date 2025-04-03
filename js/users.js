document.addEventListener("DOMContentLoaded", async function () {
    fetchUsers();
    verifyUser()
});

function verifyUser() {
    // Parse user object from localStorage
    const user = JSON.parse(localStorage.getItem("user"));

    // Check if the role is not "admin"
    if (user && user.role !== "admin") {
        window.location.href = "index.html";
    }
}

// Show loading spinner
function showLoading() {
    document.getElementById("loading").style.display = "block";
}

// Hide loading spinner
function hideLoading() {
    document.getElementById("loading").style.display = "none";
}

// ✅ Setup event listeners
function setupEventListeners() {
    // Handle search input
    document.getElementById("search")?.addEventListener("input", fetchUsers);

    // Handle form submissions
    document.getElementById("addUserForm")?.addEventListener("submit", addUser);
    document
        .getElementById("editUserForm")
        ?.addEventListener("submit", updateUser);
}

// ✅ Render user table
function renderUserTable(users) { }

// ✅ Fetch users (with search functionality)
async function fetchUsers() {
    const searchQuery = document.getElementById("search")?.value || "";

    try {
        const response = await fetch(
            `${BASE_URL}/backend/users/read_users.php?search=${searchQuery}`,
            {
                headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
            }
        );

        const usersData = await response.json();
        const users = usersData.users;
        const table = document.getElementById("userTable");

        table.innerHTML = "";

        users.forEach((user) => {
            table.innerHTML += `
            <tr>
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td class="user-edit-delete">
                    <i class="fa-solid fa-edit" onclick="openEditUserModal(${user.id}, '${user.name}', '${user.email}', '${user.role}')"></i>
                    <i class="fa-solid fa-trash" onclick="openUserDeleteModal(${user.id})"></i>
                </td>
            </tr>
        `;
        });
    } catch (error) {
        console.error("Error fetching users:", error);
    }
}

// ✅ Delete User
async function deleteUser(id) {
    try {
        const response = await fetch(`${BASE_URL}/backend/users/delete_users.php`, {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
            body: JSON.stringify({ id }),
        });

        if (!response.ok) throw new Error("Failed to delete user");

        await fetchUsers(); // Refresh user list
    } catch (error) {
        console.error("Error deleting user:", error);
    }
}

// ✅ Add User
async function addUser(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    try {
        const response = await fetch(
            `${BASE_URL}/backend/users/create_user.php?action=register`,
            {
                method: "POST",
                headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
                body: formData,
            }
        );

        if (!response.ok) throw new Error("Failed to add user");

        await fetchUsers();
        closeUserModal();
    } catch (error) {
        console.error("Error adding user:", error);
    }
}

// ✅ Update User
async function updateUser(event) {
    event.preventDefault();

    const id = document.getElementById("editUserId").value;
    const name = document.getElementById("editUserName").value;
    const email = document.getElementById("editUserEmail").value;
    const role = document.getElementById("editUserRole").value;

    try {
        const response = await fetch(`${BASE_URL}/backend/users/update_users.php`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
            body: JSON.stringify({ id, name, email, role }),
        });

        if (!response.ok) throw new Error("Failed to update user");

        await fetchUsers();
        closeEditUserModal();
    } catch (error) {
        console.error("Error updating user:", error);
    }
}

// ✅ Modals Handling
function openUserAddModal() {
    document.getElementById("addUserForm").reset();
    document.getElementById("modalTitle").textContent = "Add User";
    document.getElementById("userModal").style.display = "flex";
}

function closeUserModal() {
    document.getElementById("userModal").style.display = "none";
}

function openUserDeleteModal(id) {
    document.getElementById("deleteModal").style.display = "flex";
    window.deleteUserId = id;
}

function closeUserDeleteModal() {
    document.getElementById("deleteModal").style.display = "none";
}

function confirmUserDelete() {
    deleteUser(window.deleteUserId);
    closeUserDeleteModal();
}

function openEditUserModal(id, name, email, role) {
    document.getElementById("editUserId").value = id;
    document.getElementById("editUserName").value = name;
    document.getElementById("editUserEmail").value = email;
    document.getElementById("editUserRole").value = role;
    document.getElementById("editUserModal").style.display = "flex";
}

function closeEditUserModal() {
    document.getElementById("editUserModal").style.display = "none";
}