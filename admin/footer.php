</div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // Auto-logout after inactivity (30 minutes)
        let inactivityTimer;
        
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                alert('Sesión expirada por inactividad. Serás redirigido al login.');
                window.location.href = 'logout.php';
            }, 1800000); // 30 minutes
        }
        
        // Reset timer on user activity
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);
        
        // Initialize timer
        resetInactivityTimer();
    </script>
</body>
</html>
