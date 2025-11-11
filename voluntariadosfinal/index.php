<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voluntariados en Uruguay</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>
  
  <section class="banner">
    <div class="banner-content">
      <h1>Transformá tu intención en acción</h1>
      <p>Encontrá oportunidades de voluntariado en todo Uruguay y forma parte del cambio.</p>
      <div class="banner-buttons">
        <a href="voluntariados.php" class="btn-primary">Ver voluntariados</a>
        <a href="registro.php" class="btn-secondary">Registrarse</a>
      </div>
    </div>
  </section>

  <section class="features">
    <h2 class="section-title">¿Qué podés hacer?</h2>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🌱</div>
        <h3>Ambiental</h3>
        <p>Conservación urbana y rural, reforestación y educación ecológica.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📚</div>
        <h3>Educación</h3>
        <p>Apoyo escolar, talleres educativos y acompañamiento infantil.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">👵</div>
        <h3>Adultos Mayores</h3>
        <p>Acompañamiento, actividades recreativas y apoyo en hogares.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💼</div>
        <h3>Voluntariado Profesional</h3>
        <p>Salud, logística, administración y más según tu expertise.</p>
      </div>
    </div>
  </section>
  <div class="mapaback">
  <section class="map-section">
    <h2 class="section-title">Oportunidades por región</h2>
    <div class="mapa">
      <img src="imagenes/mapaconpines.png" alt="Mapa de Uruguay con pines de voluntariado">
    </div>
  </section>
  </div>

  <section class="stats">
    <div class="stats-container">
      <div class="stat">
        <h3>500+</h3>
        <p>Voluntarios activos</p>
      </div>
      <div class="stat">
        <h3>50+</h3>
        <p>Organizaciones aliadas</p>
      </div>
      <div class="stat">
        <h3>19</h3>
        <p>Departamentos cubiertos</p>
      </div>
      <div class="stat">
        <h3>1000+</h3>
        <p>Horas de impacto mensual</p>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>
</body>
</html>