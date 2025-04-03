const BASE_URL = "http://localhost:8000"; // Adjust for your setup

const authtoken = localStorage.getItem("user"); 
const loggedUser = authtoken ? JSON.parse(authtoken) : null;

if (loggedUser) {
    document.getElementById("back-link").href = "/dashboard/my-blogs.html"
    document.getElementById("back-link").innerText = "Back to Blogs"
} else {
    document.getElementById("back-link").href = "index.html"
}

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
        document.getElementById("blogReadTime").innerText = blog.read_time;
        document.getElementById("blogImage1").src = `../backend/blogs/${blog.image1}`;
        document.getElementById("blogContent1").innerHTML = blog.content1;
        document.getElementById("blogImage2").src = `backend/blogs/${blog.image2}`;
        document.getElementById("blogContent2").innerHTML = blog.content2;
        document.getElementById("blogTags").innerHTML = `<p>${blog.tag1}</p> <p>${blog.tag2}</p> <p>${blog.tag3}</p>`;
        document.getElementById("blogDate").innerText = new Date(blog.created_at).toLocaleString();
    } catch (error) {
        console.error("Error fetching blog details:", error);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const menuTrigger = document.getElementById("mainMenu-trigger");
    const menu = document.getElementById("mainMenu");
    const linesButton = menuTrigger.querySelector(".lines-button");
    const body = document.body; // Correct way to target <body>

    menuTrigger.addEventListener("click", function () {
        menu.classList.toggle("active");
        linesButton.classList.toggle("x"); // Toggle X icon
        
        // Prevent scrolling when menu is open
        if (menu.classList.contains("active")) {
            body.style.overflowY = "hidden";
        } else {
            body.style.overflowY = "scroll";
        }
    });

    // Close menu when clicking outside
    document.addEventListener("click", function (event) {
        if (!menu.contains(event.target) && !menuTrigger.contains(event.target)) {
            menu.classList.remove("active");
            linesButton.classList.remove("x"); // Reset to three lines
            
            // Enable scrolling only if menu is closed
            body.style.overflowY = "scroll";
        }
    });
});



