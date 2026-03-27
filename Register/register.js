const form = document.getElementById("registerForm");
const errorEls = document.querySelectorAll(".error-text");

window.addEventListener("pageshow", function (e) {
  if (e.persisted) {
    form.reset();
    clearErrors();
  }
});

function clearErrors() {
  errorEls.forEach((el) => (el.textContent = ""));
}

function setError(field, message) {
  const el = document.querySelector(`[data-error-for="${field}"]`);
  if (el) el.textContent = message || "";
}

function clientValidate(data) {
  const errors = {};
  const fullName = (data.get("full_name") || "").trim();
  const email = (data.get("email") || "").trim();
  const phone = (data.get("phone") || "").trim();
  const password = data.get("password") || "";
  const confirm = data.get("confirm_password") || "";

  if (!fullName) errors.full_name = "Full name is required.";
  else if (fullName.length < 3 || fullName.length > 100)
    errors.full_name = "Full name must be 3 to 100 characters.";

  if (!email) errors.email = "Email is required.";
  else {
    const at = email.indexOf("@");
    const dot = email.lastIndexOf(".");
    if (at < 1 || dot <= at + 1 || dot === email.length - 1) {
      errors.email = "Email must contain @ and a valid domain.";
    }
  }

  if (!phone) errors.phone = "Phone number is required.";
  else if (!/^[0-9]{10}$/.test(phone))
    errors.phone = "Phone number must be 10 digits.";

  if (!password) errors.password = "Password is required.";
  else if (password.length < 6)
    errors.password = "Password must be at least 6 characters.";

  if (!confirm) errors.confirm_password = "Confirm password is required.";
  else if (password !== confirm)
    errors.confirm_password = "Passwords do not match.";

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
      if (res.status === 409) {
        Swal.fire({
          icon: "error",
          title: "Already Registered",
          text: data?.message || "You are already registered. Please login."
        });
        return;
      }
      if (data?.errors) {
        Object.entries(data.errors).forEach(([field, msg]) =>
          setError(field, msg)
        );
      } else {
        const msg = data?.message || "Registration failed. Please try again.";
        Swal.fire({ icon: "error", title: "Error", text: msg });
      }
      return;
    }

    Swal.fire({
      icon: "success",
      title: "Registered!",
      text: data?.message || "Account created successfully.",
      footer: ""
    }).then(() => {
      form.reset();
      window.location.replace("login.html");
    });
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "Network Error",
      text: "Please check your connection and try again."
    });
  }
});
