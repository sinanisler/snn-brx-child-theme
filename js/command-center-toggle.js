// command-center-toggle.js
// JavaScript for toggling the command center visibility

document.addEventListener("keydown", function (e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'U') {
        toggleCommandCenter();
    }
});

document.getElementById("close-command-center").addEventListener("click", function () {
    toggleCommandCenter();
});

function toggleCommandCenter() {
    let commandCenter = document.getElementById('command-center');
    commandCenter.classList.toggle('visible');
}
