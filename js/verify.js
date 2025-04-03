const BASE_URL = "http://localhost:8000"; // Adjust for your setup

document.addEventListener("DOMContentLoaded", async function () {
    const userData = localStorage.getItem("user");
    const user = userData ? JSON.parse(userData) : null; // ✅ Prevent errors if user data is missing
    const loggedIn = document.getElementById("loggedinuser");

    try {
        if (loggedIn) {
            loggedIn.textContent = user.name;
        }
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
        alert("Failed to load dashboard data. Please try again later.");
    }
});

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

// ✅ Setup event listeners
function setupEventListeners() {
    document.querySelector(".nav-links[href='../login.html']")
        ?.addEventListener("click", function () {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            window.location.href = "../login.html";
        });
}

document.addEventListener("DOMContentLoaded", async function () {
    await checkLogin()
    setupNavigation(); // Setup navigation events
    setupEventListeners(); // Setup general event listeners
});