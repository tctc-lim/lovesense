async function login(event) {
    event.preventDefault(); // Prevent page reload

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const emailError = document.getElementById("emailError");
    const passwordError = document.getElementById("passwordError");
    const loader = document.getElementById("loader");
    const message = document.getElementById("message");

    // Reset previous errors
    emailError.innerText = "";
    passwordError.innerText = "";
    document.getElementById("email").classList.remove("error");
    document.getElementById("password").classList.remove("error");

    // Validate inputs
    if (!email) {
        emailError.innerText = "Email is required";
        document.getElementById("email").classList.add("error");
        return;
    }
    if (!password) {
        passwordError.innerText = "Password is required";
        document.getElementById("password").classList.add("error");
        return;
    }

    // Show loader
    loader.style.display = "block";

    try {
        const response = await fetch("http://localhost:8000/backend/auth/auth.php?action=login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            // Handle invalid credentials properly
            throw new Error(data.error || "Login failed");
        }

        message.style.color = "green";
        message.innerText = "Login successful!";

        // Save token and user details
        localStorage.setItem("token", data.token);
        localStorage.setItem("user", JSON.stringify(data.user));

        setTimeout(() => {
            window.location.href = "dashboard/index.html";
        }, 2000);
    } catch (error) {
        message.style.color = "red";
        message.innerText = error.message;
    } finally {
        loader.style.display = "none"; // Hide loader
    }
}
function VerifyLoginUser() {
    // Parse user object from localStorage
    const user = JSON.parse(localStorage.getItem("user"));
    const authtoken = (localStorage.getItem("token"));

    // Check if the role is not "admin"
    if (user && user.role && authtoken) {
        window.location.href = "/dashboard/index.html";
    }
}

VerifyLoginUser()