const modal = document.getElementById("forgotModal");
const openBtn = document.getElementById("openForgot");
const closeBtn = document.getElementById("closeForgot");

openBtn.onclick = function (e) {
  e.preventDefault();
  modal.style.display = "flex";
};

closeBtn.onclick = function () {
  modal.style.display = "none";
};

window.onclick = function (event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
};
