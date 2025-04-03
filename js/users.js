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
    const table = document.getElementById("userTable");

    table.innerHTML = "<p class='loader2' id ='loader2'>Loading...</p>";

    try {
        const response = await fetch(
            `${BASE_URL}/backend/users/read_users.php?search=${searchQuery}`,
            {
                headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
            }
        );

        const usersData = await response.json();
        const users = usersData.users;

        setTimeout(() => {
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
        }, 600);
    } catch (error) {
        document.getElementById("loader2").innerHTML = `${error}`
        document.getElementById("loader2").style.color = 'red'
    }
}

// ✅ Delete User
async function deleteUser(id) {
    const status = document.getElementById("status-bar3");
    status.innerHTML = "Loading ..."
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
        status.innerHTML = "successfully deleted user"
        status.style.color = "green"

        await fetchUsers(); // Refresh user list
        closeEditUserModal()
    } catch (error) {
        status.innerHTML = error;
        status.style.color = "red"
    }
}

// ✅ Add User
async function addUser(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const status = document.getElementById("status-bar")
    status.innerHTML = "Loading ...."

    try {
        const response = await fetch(
            `${BASE_URL}/backend/users/create_user.php?action=register`,
            {
                method: "POST",
                headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
                body: formData,
            }
        );

        if (!response.ok) {
            throw new Error("Failed to add user");
        }
        status.innerHTML = "Successfully added user";
        status.style.color = "green"

        await fetchUsers();
        closeUserModal();
    } catch (error) {
        status.innerHTML = error;
        status.style.color = "red"
    }
}

// ✅ Update User
async function updateUser(event) {
    event.preventDefault();

    const id = document.getElementById("editUserId").value;
    const name = document.getElementById("editUserName").value;
    const email = document.getElementById("editUserEmail").value;
    const role = document.getElementById("editUserRole").value;
    const status = document.getElementById("status-bar2")
    status.innerHTML = "Loading ...."

    try {
        const response = await fetch(`${BASE_URL}/backend/users/update_users.php`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
            body: JSON.stringify({ id, name, email, role }),
        });

        if (!response.ok) {
            throw new Error("Failed to add user");
        }
        status.innerHTML = "Successfully updated user";
        status.style.color = "green"

        await fetchUsers();
        closeEditUserModal();
    } catch (error) {
        status.innerHTML = error;
        status.style.color = "red";
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