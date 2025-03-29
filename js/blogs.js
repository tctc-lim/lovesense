document.addEventListener("DOMContentLoaded", async function () {
    fetchBlogs();
});

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = "No Image";
    }
}

document.getElementById("image1").addEventListener("change", function () {
    previewImage(this, "preview1");
});

document.getElementById("image2").addEventListener("change", function () {
    previewImage(this, "preview2");
});

document.getElementById("blogForm").addEventListener("submit", async (e) => {
    e.preventDefault(); // Stop form submission

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

// Function to Open Edit Modal
function openEditModal(blog) {
    document.getElementById("editBlogId").value = blog.id;
    document.getElementById("editBlogTitle").value = blog.title;
    document.getElementById("editBlogContent1").value = blog.content1;
    document.getElementById("editBlogContent2").value = blog.content2;
    document.getElementById("editBlogTag1").value = blog.tag1;
    document.getElementById("editBlogTag2").value = blog.tag2;
    document.getElementById("editBlogTag3").value = blog.tag3;
    document.getElementById("editBlogStatus").value = blog.thestatus;

    document.getElementById("editBlogModal").style.display = "block";
}

// Function to Close Edit Modal
function closeEditModal() {
    document.getElementById("editBlogModal").style.display = "none";
}

// ✅ Function to Submit Edit Request
document
    .getElementById("editBlogForm")
    .addEventListener("submit", async function (event) {
        event.preventDefault(); // Prevent page reload

        const blogId = document.getElementById("editBlogId").value;
        const updatedBlog = {
            id: blogId,
            title: document.getElementById("editBlogTitle").value,
            content1: document.getElementById("editBlogContent1").value,
            content2: document.getElementById("editBlogContent2").value,
            tag1: document.getElementById("editBlogTag1").value,
            tag2: document.getElementById("editBlogTag2").value,
            tag3: document.getElementById("editBlogTag3").value,
            thestatus: document.getElementById("editBlogStatus").value = blog.thestatus,
        };

        try {
            const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php?id=${blogId}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
                body: JSON.stringify(updatedBlog),
            });

            const result = await response.json();
            if (result.success) {
                alert("Blog updated successfully!");
                closeEditModal();
                fetchBlogs(); // Refresh blog list
            } else {
                alert("Failed to update blog.");
            }
        } catch (error) {
            console.error("Error updating blog:", error);
        }

        return false; // Explicitly prevent any default behavior
    });

// Function to Open Delete Confirmation Modal
function openDeleteModal(blogId) {
    document
        .getElementById("confirmDeleteButton")
        .setAttribute("data-id", blogId);
    document.getElementById("deleteBlogModal").style.display = "block";
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
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });

        const result = await response.json();
        if (result.success) {
            alert("Blog deleted successfully!");
            closeUserDeleteModal();
            fetchBlogs(); // Refresh blog list
        } else {
            alert("Failed to delete blog.");
        }
    } catch (error) {
        console.error("Error deleting blog:", error);
    }
}

// Function to Fetch Blogs (for display)
async function fetchBlogs() {
    try {
        const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php`);
        const blogdata = await response.json();
        blogs = blogdata.blogs;

        const blogTable = document.getElementById("blogTable");
        blogTable.innerHTML = ""; // Clear previous entries

        blogs.forEach((blog, index) => {
            blogTable.innerHTML += `
                <div class="blog-data">
                    <div class="blog-data-content">
                        <p class="the-blog-id">${index + 1}.</p>
                        <img src="../backend/blogs/${blog.image1}" alt="Image1" width="300px" height="200px" class="blog-img">
                    </div>
                    <div class="blog-details">
                        <h2>${blog.title}</h2>
                        <p>${blog.content1.substring(0, 100)}...</p>
                        <p>Status: ${blog.thestatus}</p>
                        <button onclick="viewBlog(${blog.id})">See More</button>
                        <button onclick='openEditModal(${JSON.stringify(blog)})'>Edit</button>
                        <button onclick='openDeleteModal(${blog.id})'>Delete</button>
                    </div>
                </div>
              `;
        });
    } catch (error) {
        console.error("Error fetching blogs:", error);
    }
}

function viewBlog(blogId) {
    window.location.href = `/blog-details.html?id=${blogId}`;
}
