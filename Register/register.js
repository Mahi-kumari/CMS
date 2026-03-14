const form = document.getElementById("registerForm");

window.addEventListener("pageshow", function (e) {
  if (e.persisted) {
    form.reset();
  }
});

form.addEventListener("submit", async function (e) {
  e.preventDefault();

  const formData = new FormData(form);

  try {
    const res = await fetch(form.action, {
      method: "POST",
      body: formData
    });

    const data = await res.json().catch(() => null);

    if (!res.ok) {
      const msg = data?.message || "Registration failed. Please try again.";
      Swal.fire({ icon: "error", title: "Error", text: msg });
      return;
    }

    const detail = data?.insert_id
      ? `ID: ${data.insert_id} | DB: ${data.db_name || "unknown"}`
      : "";

    Swal.fire({
      icon: "success",
      title: "Registered!",
      text: data?.message || "Account created successfully.",
      footer: detail
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
