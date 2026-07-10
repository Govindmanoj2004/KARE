document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-9-form");
  const googleBtn = document.getElementById("google-btn");
  const facebookBtn = document.getElementById("facebook-btn");

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    console.log("Login submitted:", { email, password });
  });

  googleBtn.addEventListener("click", () => {
    console.log("Google login clicked");
  });

  facebookBtn.addEventListener("click", () => {
    console.log("Facebook login clicked");
  });
});
