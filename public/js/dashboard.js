/* globals Chart:false, feather:false, bootstrap:false */

// Logout Modal Functions (global)
function showLogoutModal() {
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
    // Re-initialize feather icons for the modal
    setTimeout(() => feather.replace({ 'aria-hidden': 'true' }), 100);
}

function confirmLogout() {
    window.location.href = 'index.php?logout=true';
}

// Initialize when DOM is loaded
(function () {
  feather.replace({ 'aria-hidden': 'true' });
})()
