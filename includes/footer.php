</main>
    
    <footer class="pemira-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <span class="footer-title">PEMIRA 2025</span>
                    </div>
                    <p class="footer-copyright">&copy; 2025 PEMIRA - Sistem E-Voting</p>
                </div>
                
                <div class="footer-info">
                    <div class="footer-social">
                        <a href="https://www.youtube.com/@PemiraKBMIT-PLN" class="social-icon"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.instagram.com/pemiraitpln/" class="social-icon"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>Dikembangkan oleh Bagian Sarana & Prasarana PEMIRA 2025</p>
            </div>
        </div>
    </footer>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>

<style>
/* Footer Styles - menggunakan kelas yang spesifik agar tidak mempengaruhi elemen lain */
.pemira-footer {
    background-color: #0c4f6a;
    color: #ffffff;
    padding: 3rem 0 1.5rem;
    margin-top: auto;
}

.pemira-footer .footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.pemira-footer .footer-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.pemira-footer .footer-brand {
    flex: 1;
}

.pemira-footer .footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 1rem;
}

.pemira-footer .footer-logo-img {
    height: 36px;
    width: auto;
}

.pemira-footer .footer-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
}

.pemira-footer .footer-copyright {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-left: 150px;
}

.pemira-footer .footer-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1.5rem;
}

.pemira-footer .footer-links {
    display: flex;
    gap: 1.5rem;
}

.pemira-footer .footer-link {
    color: #ffffff;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    position: relative;
}

.pemira-footer .footer-link::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: #fdbb2d;
    transition: width 0.3s ease;
}

.pemira-footer .footer-link:hover::after {
    width: 100%;
}

.pemira-footer .social-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pemira-footer .social-icon:hover {
    background-color: #fdbb2d;
    transform: translateY(-3px);
}

.pemira-footer .footer-social {
    display: flex;
    gap: 10px;
}

.pemira-footer .footer-bottom {
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.pemira-footer .footer-bottom p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .pemira-footer .footer-container {
        padding: 0 1.5rem;
    }
    
    .pemira-footer .footer-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .pemira-footer .footer-info {
        align-items: center;
        text-align: center;
    }
}

@media (max-width: 768px) {
    .pemira-footer {
        padding: 2rem 0 1rem;
    }
    
    .pemira-footer .footer-logo-img {
        height: 30px;
    }
    
    .pemira-footer .footer-title {
        font-size: 1.3rem;
    }
    
    .pemira-footer .footer-links {
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
    }
    
    .pemira-footer .footer-bottom {
        padding-top: 1rem;
    }
}
</style>