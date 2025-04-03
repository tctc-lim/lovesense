const BASE_URL = "http://localhost:8000"; // Adjust for your setup

document.addEventListener("DOMContentLoaded", async function () {
    await checkLogin(); // Ensure user is logged in
    setupNavigation(); // Setup navigation events
    setupEventListeners(); // Setup general event listeners

    const userData = localStorage.getItem("user");
    const user = userData ? JSON.parse(userData) : null; // ✅ Prevent errors if user data is missing

    try {
        // ✅ Fetch blog data
        const response = await fetch(`${BASE_URL}/backend/blogs/blogs.php`);
        const thedata = await response.json();
        const blogs = thedata.blogs || [];

        // ✅ Count total blogs
        const totalBlogs = blogs.length;
        const pendingBlogs = blogs.filter(blog => blog.status === "PENDING").length;
        const completedBlogs = blogs.filter(blog => blog.status === "COMPLETED").length;
        console.log(pendingBlogs)

        // ✅ Update the dashboard safely
        const blogCards = document.querySelectorAll(".cards .card p");
        if (blogCards.length >= 3) {
            blogCards[0].textContent = totalBlogs;
            blogCards[1].textContent = pendingBlogs;
            blogCards[2].textContent = completedBlogs;
        }

        if (user && user.role === "admin") {
            // ✅ Fetch registered users
            const userResponse = await fetch(`${BASE_URL}/backend/users/read_users.php`, {
                headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
            });

            const userDataResponse = await userResponse.json();
            const users = userDataResponse.users || [];

            // ✅ Ensure the element exists before setting text
            const loggedInUserElement = document.getElementById("loggedinuser");
            if (loggedInUserElement) {
                loggedInUserElement.textContent = user.name;
            }

            // ✅ Ensure user count card exists before updating it
            const userCountCard = document.querySelector(".cards .card:nth-child(4) p");
            if (userCountCard) {
                userCountCard.textContent = users.length;
            }
        } else {
            const userCard = document.getElementById("userCard");
            if (userCard) userCard.style.display = "none";
        }

        const userResponse = await fetch(`${BASE_URL}/backend/users/read_users.php`, {
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });

        const userDataResponse = await userResponse.json();
        const users = userDataResponse.users || [];

        // ✅ Ensure the element exists before setting text
        const loggedInUserElement = document.getElementById("loggedinuser");
        if (loggedInUserElement) {
            loggedInUserElement.textContent = user.name;
        }
        
        // ✅ Fetch recent activities
        const activityResponse = await fetch(`${BASE_URL}/backend/users/users_activities.php`, {
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });

        const activities = await activityResponse.json();
        const activityList = document.querySelector("#activity-list");

        if (activityList) {
            activityList.innerHTML = ""; // ✅ Clear loading text

            if (!Array.isArray(activities) || activities.length === 0) {
                activityList.innerHTML = "<li>No recent activities found.</li>";
                return;
            }

            // ✅ Loop through activities and display them in the list
            activities.forEach(activity => {
                const listItem = document.createElement("li");
                listItem.textContent = `${activity.action} - ${new Date(activity.timestamp).toLocaleString()}`;
                activityList.appendChild(listItem);
            });
        }

    } catch (error) {
        console.error("Error fetching dashboard data:", error);
        alert("Failed to load dashboard data. Please try again later.");
    }

    try {
        const response = await fetch(`${BASE_URL}/backend/blogs/recent_blogs.php`);
        const data = await response.json();

        const recentBlogs = data.recent_blogs;
        const blogTable = document.querySelector("#recent-blogs-table tbody");

        if (!recentBlogs || recentBlogs.length === 0) {
            blogTable.innerHTML = "<tr><td colspan='3'>No recent blogs found.</td></tr>";
            return;
        }

        blogTable.innerHTML = ""; // Clear existing rows

        recentBlogs.forEach((blog, index) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${blog.title}</td>
                <td>${new Date(blog.created_at).toLocaleString()}</td>
                <td class="recent-status">${blog.status}</td>
            `;
            blogTable.appendChild(row);
        });

        // ✅ Select all elements with class "recent-status"
        document.querySelectorAll(".recent-status").forEach(statusCell => {
            if (statusCell.textContent.trim() === "PENDING") {
                statusCell.style.backgroundColor = "yellow";
            } else {
                statusCell.style.backgroundColor = "green";
                statusCell.style.color = "white";
            }
        })
    } catch (error) {
        blogTable.innerHTML = `<tr><td colspan='3'>${error}</td></tr>`;
    }
});

// ✅ Navigation & Active Link Handling
function setupNavigation() {
    const nav = document.querySelector(".nav");
    const menuIcon = document.querySelector(".fa-bars");
    const navLinks = document.querySelectorAll(".nav-links");
    const currentPath = window.location.pathname;

    // Check localStorage for collapsed state
    if (localStorage.getItem("navCollapsed") === "true") {
        nav.classList.add("collapsed");
    }

    // Toggle nav collapse and store state
    if (menuIcon) {
        menuIcon.addEventListener("click", function () {
            nav.classList.toggle("collapsed");
            localStorage.setItem("navCollapsed", nav.classList.contains("collapsed"));
        });
    }

    // Highlight active link
    navLinks.forEach((link) => {
        if (link.getAttribute("href") === currentPath) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });
}

// ✅ Ensure user is logged in before accessing the page
async function checkLogin() {
    const token = localStorage.getItem("token");
    const userData = localStorage.getItem("user");
    const user = userData ? JSON.parse(userData) : null;
    const usertab = document.getElementById("userstab");

    if (!token) {
        window.location.href = "../login.html";
        return;
    }

    if (user && user.role !== "admin" && usertab) {
        usertab.style.display = "none";
    }

    try {
        const response = await fetch(`${BASE_URL}/backend/auth/check_login.php`, {
            method: "GET",
            headers: { Authorization: `Bearer ${token}` },
        });

        const data = await response.json();

        if (!data.loggedIn) {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            window.location.href = "../login.html";
        }
    } catch (error) {
        console.error("Error checking login:", error);
    }
}

// ✅ Setup event listeners
function setupEventListeners() {
    document.querySelector(".nav-links[href='../login.html']")
        ?.addEventListener("click", function () {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            window.location.href = "../login.html";
        });
}
