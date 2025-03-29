async function login(event) {
    event.preventDefault(); // Prevent page reload

    // Get input values
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
        // Send API request
        const response = await fetch("http://localhost:8000/backend/auth/auth.php?action=login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            message.style.color = "green";
            message.innerText = "Login successful!";
            
            // Save token and user details to local storage
            localStorage.setItem("token", data.token); // Save token
            localStorage.setItem("user", JSON.stringify(data.user)); // Save user details as JSON

            setTimeout(() => {
                window.location.href = "dashboard/index.html"; // Redirect to dashboard
            }, 2000);
        } else {
            message.style.color = "red";
            message.innerText = data.error || "Login failed";
        }
    } catch (error) {
        message.style.color = "red";
        message.innerText = "Error connecting to server";
    } finally {
        loader.style.display = "none"; // Hide loader
    }
}
