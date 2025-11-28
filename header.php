<div class="encabezado">
  <img src="../imagenes/logo.jpeg" alt="Logo Empresa" class="logo">
  <h1>Sistema de Control de Empleados</h1>
</div>
<hr>

<style>
body {
  position: relative;
}
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('../imagenes/logo.jpeg');
  background-size: 300px 300px;
  background-repeat: repeat;
  background-position: center;
  opacity: 0.05;
  z-index: -1;
  pointer-events: none;
}
.encabezado {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  background-color: #ffffff;
  padding: 10px;
}

.encabezado .logo {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  object-fit: contain;
}
</style>
