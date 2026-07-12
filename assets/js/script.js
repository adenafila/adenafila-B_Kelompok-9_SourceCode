document.addEventListener('DOMContentLoaded', function() {
    // Auto refresh untuk halaman yang memerlukan update real-time
    if (document.querySelector('.auto-refresh')) {
        setInterval(function() {
            location.reload();
        }, 30000); // Refresh setiap 30 detik
    }
    
    // Konfirmasi sebelum hapus data
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle password visibility
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
    }
});