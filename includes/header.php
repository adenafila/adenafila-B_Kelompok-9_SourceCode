<?php
// Cek session untuk menentukan menu yang ditampilkan
$isPemilih = isset($_SESSION['pemilih_id']);
$isAdmin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
$isSuperadmin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'superadmin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEMIRA 2025 - <?php echo isset($pageTitle) ? $pageTitle : 'E-Voting'; ?></title>
    
    <!-- Favicon menggunakan logo.png dari folder includes -->
    <link rel="icon" type="image/png" href="includes/logo.png">
    
    <!-- Untuk berbagai perangkat dan resolusi -->
    <link rel="apple-touch-icon" href="includes/logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="includes/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="includes/logo.png">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Library untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Header Styles - menggunakan kelas yang spesifik agar tidak mempengaruhi elemen lain */
        .pemira-header {
            background-color: #ffffff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0;
            border-bottom: 1px solid rgba(12, 79, 106, 0.08);
        }
        
        .pemira-header .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .pemira-header .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .pemira-header .brand-logo {
            height: 45px;
            width: auto;
            transition: transform 0.3s ease;
        }
        
        .pemira-header .brand-logo:hover {
            transform: scale(1.05);
        }
        
        .pemira-header .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0c4f6a;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .pemira-header .navigation {
            flex-grow: 1;
            margin-left: 3rem;
        }
        
        .pemira-header .nav-list {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
            justify-content: flex-end;
        }
        
        .pemira-header .nav-item {
            position: relative;
        }
        
        .pemira-header .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            text-decoration: none;
            color: #333333;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }
        
        .pemira-header .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background-color: #0c4f6a;
            transition: width 0.3s ease;
        }
        
        .pemira-header .nav-link:hover {
            color: #0c4f6a;
            background-color: rgba(12, 79, 106, 0.04);
        }
        
        .pemira-header .nav-link:hover::before {
            width: 100%;
        }
        
        .pemira-header .nav-link.active {
            color: #0c4f6a;
            font-weight: 600;
        }
        
        .pemira-header .nav-link.active::before {
            width: 100%;
        }
        
        .pemira-header .nav-icon {
            margin-right: 8px;
            font-size: 0.95rem;
        }
        
        /* Mobile menu button */
        .pemira-header .menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            color: #0c4f6a;
        }
        
        .pemira-header .menu-toggle i {
            font-size: 1.4rem;
            transition: transform 0.3s ease;
        }
        
        .pemira-header .menu-toggle:hover i {
            transform: scale(1.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .pemira-header .header-container {
                padding: 1rem 1.5rem;
            }
            
            .pemira-header .navigation {
                margin-left: 2rem;
            }
            
            .pemira-header .nav-link {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .pemira-header .nav-icon {
                margin-right: 6px;
            }
        }
        
        @media (max-width: 768px) {
            .pemira-header .menu-toggle {
                display: block;
            }
            
            .pemira-header .header-container {
                padding: 0.8rem 1rem;
            }
            
            .pemira-header .brand-logo {
                height: 38px;
            }
            
            .pemira-header .brand-title {
                font-size: 1.5rem;
            }
            
            .pemira-header .navigation {
                position: fixed;
                top: 70px;
                left: 0;
                width: 100%;
                background-color: #ffffff;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                padding: 1rem;
                margin-left: 0;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.4s ease;
                z-index: 999;
            }
            
            .pemira-header .navigation.active {
                max-height: 500px;
            }
            
            .pemira-header .nav-list {
                flex-direction: column;
                gap: 8px;
            }
            
            .pemira-header .nav-item {
                width: 100%;
            }
            
            .pemira-header .nav-link {
                width: 100%;
                padding: 12px 16px;
                border-radius: 8px;
                justify-content: flex-start;
            }
            
            .pemira-header .nav-link::before {
                height: 0;
                width: 0;
                left: 0;
                top: 0;
                bottom: auto;
            }
            
            .pemira-header .nav-link:hover::before {
                width: 4px;
                height: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="pemira-header">
        <div class="header-container">
            <div class="brand">
                <img src="../assets/logo.png" alt="PEMIRA Logo" class="brand-logo">
                <h1 class="brand-title">PEMIRA 2025</h1>
            </div>
            
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="navigation" id="navigation">
                <ul class="nav-list">
                    <?php if ($isSuperadmin): ?>
                        <li class="nav-item"><a href="../superadmin/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="../superadmin/manage_candidates.php" class="nav-link"><i class="fas fa-users nav-icon"></i> Kandidat</a></li>
                        <li class="nav-item"><a href="../superadmin/manage_voters.php" class="nav-link"><i class="fas fa-user-cog nav-icon"></i> Pemilih</a></li>
                        <li class="nav-item"><a href="../superadmin/view_voters.php" class="nav-link"><i class="fas fa-list nav-icon"></i> Daftar</a></li>
                        <li class="nav-item"><a href="../superadmin/view_results.php" class="nav-link"><i class="fas fa-chart-bar nav-icon"></i> Hasil</a></li>
                        <li class="nav-item"><a href="../superadmin/active_sessions.php" class="nav-link"><i class="fas fa-clock nav-icon"></i> Sesi</a></li>
                    <?php elseif ($isAdmin): ?>
                        <li class="nav-item"><a href="../admin/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="../admin/manage_voters.php" class="nav-link"><i class="fas fa-user-cog nav-icon"></i> Pendaftaran</a></li>
                        <li class="nav-item"><a href="../admin/current_voters.php" class="nav-link"><i class="fas fa-list nav-icon"></i> Sesi</a></li>
                    <?php elseif ($isPemilih): ?>
                        <li class="nav-item"><a href="../pemilih/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="../pemilih/voting.php" class="nav-link"><i class="fas fa-vote-yea nav-icon"></i> Voting</a></li>
                    <?php endif; ?>
                    <?php if ($isAdmin || $isSuperadmin): ?>
                        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user nav-icon"></i> Profil</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt nav-icon"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('navigation').classList.toggle('active');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.getElementById('navigation').classList.remove('active');
                    const menuBtn = document.getElementById('menuToggle');
                    menuBtn.querySelector('i').classList.add('fa-bars');
                    menuBtn.querySelector('i').classList.remove('fa-times');
                }
            });
        });
    </script>
    
    <main>
        <div class="container">