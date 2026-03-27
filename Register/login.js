const form = document.getElementById("loginForm");
const errorEls = document.querySelectorAll(".error-text");

function clearErrors() {
  errorEls.forEach((el) => (el.textContent = ""));
}

function setError(field, message) {
  const el = document.querySelector(`[data-error-for="${field}"]`);
  if (el) el.textContent = message || "";
}

function clientValidate(data) {
  const errors = {};
  const email = (data.get("email") || "").trim();
  const password = data.get("password") || "";

  if (!email) errors.email = "Email is required.";
  else {
    const at = email.indexOf("@");
    const dot = email.lastIndexOf(".");
    if (at < 1 || dot <= at + 1 || dot === email.length - 1) {
      errors.email = "Email must contain @ and a valid domain.";
    }
  }

  if (!password) errors.password = "Password is required.";
  else if (password.length < 6)
    errors.password = "Password must be at least 6 characters.";

  return errors;
}

form.addEventListener("submit", async function (e) {
  e.preventDefault();

  const formData = new FormData(form);
  clearErrors();

  const clientErrors = clientValidate(formData);
  if (Object.keys(clientErrors).length) {
    Object.entries(clientErrors).forEach(([field, msg]) =>
      setError(field, msg)
    );
    return;
  }

  try {
    const res = await fetch(form.action, {
      method: "POST",
      body: formData
    });

    const data = await res.json().catch(() => null);

    if (!res.ok) {
      if (res.status === 404) {
        Swal.fire({
          icon: "error",
          title: "Not Registered",
          text: data?.message || "You are not registered. Please sign up."
        });
        return;
      }
      if (res.status === 401) {
        Swal.fire({
          icon: "error",
          title: "Login Failed",
          text: "Invalid email or password."
        });
        return;
      }
      if (data?.errors) {
        Object.entries(data.errors).forEach(([field, msg]) =>
          setError(field, msg)
        );
        return;
      }
      const msg = data?.message || "Login failed. Please try again.";
      Swal.fire({ icon: "error", title: "Error", text: msg });
      return;
    }

    Swal.fire({
      icon: "success",
      title: "Welcome!",
      text: data?.message || "Login successful."
    }).then(() => {
      form.reset();
      window.location.href = "../Dashboard/index.php";
    });
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "Network Error",
      text: "Please check your connection and try again."
    });
  }
});
