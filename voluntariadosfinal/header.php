<header>
    <div class="logo">
        <img src="imagenes/logosinfondo.png" alt="Logo">
        <span>Tu granito de arena</span>
    </div>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="sobre.php">Sobre nosotros</a>
        <a href="voluntariados.php">Voluntariados</a>
        <a href="sumate.php" class="btn-header">Súmate</a>
        
        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
            <a href="admin.php" class="btn-admin">🛠️ Panel Admin</a>
            <a href="logout.php" class="btn-login">Cerrar (Admin)</a>
        <?php elseif (isset($_SESSION['organizacion_id'])): ?>
            <a href="panel_organizacion.php" class="btn-admin">🏢 Mis Voluntariados</a>
            <a href="logout.php" class="btn-login">Cerrar (Org)</a>
        <?php elseif (isset($_SESSION['voluntario_nombre'])): ?>
            <a href="logout.php" class="btn-login">Cerrar Sesión 
                <?php if (!empty($_SESSION['voluntario_nombre'])) {

                        $nombre = explode(" ", $_SESSION['voluntario_nombre'])[0];
                        echo "($nombre)";
                    }
                    if (!empty($_SESSION['organizacion_nombree'])) {
                        $nombre = explode(" ", $_SESSION['organizacion_nombree'])[0];
                        echo "($nombre)";
                    }
                    if (!empty($_SESSION['admin_nombre'])) {
                        $nombre = explode(" ", $_SESSION['admin_nombre'])[0];
                        echo "($nombre)";
                    } 
                ?> 
            </a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Iniciar Sesión</a>
            <a href="registro.php" class="btn-login">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>