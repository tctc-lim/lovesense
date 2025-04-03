document.addEventListener("DOMContentLoaded", async function () {
    // Fetch blogs on page load
fetchBlogs(1);
})

const token = localStorage.getItem("token"); // Get authentication token

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

// Function to Close Delete Modal
function closeDeleteModal() {
    document.getElementById("deleteBlogModal").style.display = "none";
}

async function confirmDelete() {
    const status = document.getElementById("status-deletebar")
    status.innerHTML = "Loading..."
    const blogId = document
        .getElementById("confirmDeleteButton")
        .getAttribute("data-id");

    try {
        const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php?id=${blogId}`, {
            method: "DELETE",
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });

        const result = await response.json();
        if (result.success) {
            status.innerHTML = "Blog deleted successfully!"
            status.style.color = "green"
            setTimeout(() => {
                closeDeleteModal();
                fetchBlogs(1); // Refresh blog list
            }, 1000);
        } else {
            status.innerHTML = "Failed to delete blog!"
            status.style.color = "red"
        }
    } catch (error) {
        console.error("Error deleting blog:", error);
    }
}




