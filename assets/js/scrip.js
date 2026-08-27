const menuButton = document.getElementById("mobileMenuButton");
const sidebar = document.querySelector(".sidebar");

if (menuButton && sidebar) {
    menuButton.addEventListener("click", function () {
        sidebar.classList.toggle("show");
    });
}