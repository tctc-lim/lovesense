document.addEventListener("DOMContentLoaded", async function () {
    // Fetch blogs on page load
fetchBlogs(1);
})

function previewImage(input, previewId) {
    const file = input.files[0]; // Get the first file

    if (file) {
        const reader = new FileReader();
        
        reader.onload = function (e) {
            document.getElementById(previewId).innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; height: auto;">`;
        };

        reader.onerror = function (error) {
            console.error("Error loading file:", error);
        };

        reader.readAsDataURL(file);
    } else {
        document.getElementById(previewId).innerHTML = "No Image";
    }
}

// Event listeners for image inputs
document.getElementById("image1").addEventListener("change", function () {
    previewImage(this, "preview1");
});

document.getElementById("image2").addEventListener("change", function () {
    previewImage(this, "preview2");
});

document.getElementById("blogForm").addEventListener("submit", async (e) => {
    e.preventDefault(); // Stop form submission

    // Ensure TinyMCE content is updated in the form data
    tinymce.triggerSave();

    let formData = new FormData(e.target);
    const authtoken = localStorage.getItem("token");

    try {
        let response = await fetch("http://localhost:8000/backend/blogs/add_blog.php", {
            method: "POST",
            body: formData,
            headers: {
                Authorization: `Bearer ${authtoken}`,
            },
        });

        let result = await response.json();
        alert(result.success || result.error);
    } catch (error) {
        console.error("Error:", error);
    }

    return false; // Ensure no refresh
});


const token = localStorage.getItem("token"); // Get authentication token

// Function to Open Delete Confirmation Modal
function openDeleteModal(blogId) {
    document
        .getElementById("confirmDeleteButton")
        .setAttribute("data-id", blogId);
    document.getElementById("deleteBlogModal").style.display = "flex";
}

// Function to Close Delete Modal
function closeDeleteModal() {
    document.getElementById("deleteBlogModal").style.display = "none";
}

async function confirmDelete() {
    const blogId = document
        .getElementById("confirmDeleteButton")
        .getAttribute("data-id");

    try {
        const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php?id=${blogId}`, {
            method: "DELETE",
            // headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });

        const result = await response.json();
        if (result.success) {
            alert("Blog deleted successfully!");
            closeDeleteModal();
            fetchBlogs(1); // Refresh blog list
        } else {
            alert("Failed to delete blog.");
        }
    } catch (error) {
        console.error("Error deleting blog:", error);
    }
}

// Function to Fetch Blogs (for display) with Pagination
async function fetchBlogs(page = 1) {
    try {
        const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php?page=${page}&limit=7`);
        const blogdata = await response.json();
        blogs = blogdata.blogs;

        const blogTable = document.getElementById("blogTable");
        blogTable.innerHTML = ""; // Clear previous entries

        blogs.forEach((blog, index) => {
            blogTable.innerHTML += `
                <div class="blog-data">
                    <div class="blog-data-content">
                        <p class="the-blog-id">${index + 1 + (page - 1) * 5}.</p>
                        <img src="../backend/blogs/${blog.image1}" alt="Image1" width="300px" height="200px" class="blog-img">
                    </div>
                    <div class="blog-details">
                        <h2>${blog.title}</h2>
                        <p>${blog.content1.substring(0, 100)}...</p>
                        <p>Status: ${blog.status}</p>
                        <button onclick="viewBlog(${blog.id})">See More</button>
                        <button class="edit-btn" data-id="${blog.id}">Edit</button>
                        <button onclick='openDeleteModal(${blog.id})'>Delete</button>
                    </div>
                </div>
            `;
        });

        // Attach event listener to all Edit buttons
        document.querySelectorAll(".edit-btn").forEach(button => {
            button.addEventListener("click", function () {
                const blogId = this.getAttribute("data-id");
                window.location.href = `edit-blog.html?id=${blogId}`;
            });
        });

        updatePagination(blogdata.current_page, blogdata.total_pages);
    } catch (error) {
        console.error("Error fetching blogs:", error);
    }
}

function viewBlog(blogId) {
    window.location.href = `../blog-details.html?id=${blogId}`;
}


// Function to Update Pagination Controls
function updatePagination(currentPage, totalPages) {
    const paginationContainer = document.getElementById("paginationControls") || document.createElement("div");
    paginationContainer.id = "paginationControls";

    let pageLinks = "";

    // Create "Previous" Button
    pageLinks += `
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""}>Previous</button>
    `;

    // Create page number buttons (1 to totalPages, but limited range for UI)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        pageLinks += `
            <button onclick="changePage(${i})" ${i === currentPage ? "class='active'" : ""}>${i}</button>
        `;
    }

    // Create "Next" Button
    pageLinks += `
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""}>Next</button>
    `;

    paginationContainer.innerHTML = pageLinks;
    document.getElementById("blogTable").after(paginationContainer);
}

// Function to Change Page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    fetchBlogs(page);
}




