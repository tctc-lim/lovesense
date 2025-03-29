const BASE_URL = "http://localhost:8000"; // Adjust for your setup

document.addEventListener("DOMContentLoaded", async function () {
    const urlParams = new URLSearchParams(window.location.search);
    const blogId = urlParams.get("id");

    if (!blogId) {
        alert("Invalid Blog ID");
        window.location.href = "index.html"; // Redirect if no ID
        return;
    }

    try {
        const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php?id=${blogId}`);
        const data = await response.json();

        if (!data.success || !data.blog) {
            alert("Blog not found");
            window.location.href = "index.html";
            return;
        }

        const blog = data.blog;
        document.getElementById("blogTitle").innerText = blog.title;
        document.getElementById("blogImage1").src = blog.image1;
        document.getElementById("blogContent1").innerText = blog.content1;
        document.getElementById("blogImage2").src = blog.image2;
        document.getElementById("blogContent2").innerText = blog.content2;
        document.getElementById("blogTags").innerText = `${blog.tag1}, ${blog.tag2}, ${blog.tag3}`;
        document.getElementById("blogAuthor").innerText = blog.author_id;
        document.getElementById("blogDate").innerText = new Date(blog.created_at).toLocaleString();
    } catch (error) {
        console.error("Error fetching blog details:", error);
    }
});
