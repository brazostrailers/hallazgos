# 🏭 Sistema de Gestión de Calidad - Hallazgos

Un sistema completo de gestión de calidad diseñado para el control y seguimiento de hallazgos en procesos industriales, con funcionalidades avanzadas de análisis y reportes.

## 🚀 Características Principales

### 📊 Dashboard Administrativo
- **Tres tablas de flujo de trabajo**: Activos → Cuarentena → Scrap
- **Análisis en tiempo real** con gráficos interactivos
- **Filtros avanzados** por fecha, área, modelo y estado
- **Exportación profesional** a Excel con metadatos y estadísticas
- **Contadores automáticos** y métricas en vivo

### 📱 Compatibilidad Móvil
- **Aplicación Android** optimizada
- **Carga múltiple de archivos** (hasta 10 fotos de 5MB cada una)
- **Interfaz responsive** para todos los dispositivos
- **Conectividad mejorada** con manejo de errores robusto

### 💰 Gestión de Scrap
- **Tracking completo** de costos de scrap
- **Registro de fechas** y valores monetarios
- **Análisis de pérdidas** económicas
- **Reportes detallados** con totales y promedios

### 🔐 Sistema de Autenticación
- **Roles diferenciados**: Usuario y Encargado
- **Sesiones seguras** con validación
- **Acceso controlado** a funcionalidades administrativas

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 8.0+, MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 5
- **Charts**: Chart.js para visualizaciones
- **Containerización**: Docker & Docker Compose
- **Base de datos**: MySQL con optimizaciones

## 📦 Instalación

### Requisitos Previos
- Docker y Docker Compose
- Git

### Instalación con Docker (Recomendado)

1. **Clonar el repositorio**
```bash
git clone https://github.com/brazostrailers/hallazgos.git
cd hallazgos
```

2. **Iniciar los contenedores**
```bash
docker-compose up -d
```

3. **Acceder a la aplicación**
- **Sistema Principal**: http://localhost:8085
- **PhpMyAdmin**: http://localhost:8080
- **Usuario por defecto**: Samuel / password

### Instalación Manual (Laragon/XAMPP)

1. **Configurar la base de datos**
```sql
-- Importar: app/sql/estructura_completa.sql
```

2. **Configurar conexión**
```php
// Editar: app/includes/db_config.php
$host = 'localhost';
$db = 'hallazgos';
$user = 'root';
$pass = '';
```

## 📋 Estructura del Proyecto

```
📁 hallazgos/
├── 📁 app/                          # Aplicación principal
│   ├── 📄 index.php                 # Página de inicio
│   ├── 📄 login.php                 # Sistema de login
│   ├── 📄 dashboard.php             # Dashboard de usuario
│   ├── 📄 admin_dashboard.php       # Dashboard administrativo
│   ├── 📁 includes/                 # Scripts PHP backend
│   │   ├── 📄 db_config.php         # Configuración de DB
│   │   ├── 📄 hallazgos_data.php    # API de hallazgos
│   │   ├── 📄 scrap_table_data.php  # API de scrap
│   │   └── 📄 ...                   # Otros endpoints
│   ├── 📁 assets/                   # Recursos estáticos
│   │   ├── 📁 js/                   # JavaScript
│   │   ├── 📁 css/                  # Estilos CSS
│   │   └── 📁 img/                  # Imágenes
│   ├── 📁 uploads/                  # Evidencias subidas
│   ├── 📁 usa/                      # Módulo USA
│   └── 📁 sql/                      # Scripts de BD
├── 📁 docker/                       # Configuración Docker
├── 📄 docker-compose.yml           # Orquestación de servicios
├── 📄 dockerfile                   # Imagen de la aplicación
└── 📄 README.md                    # Este archivo
```

## 🎯 Funcionalidades

### Para Usuarios
- ✅ Registro de hallazgos con evidencias fotográficas
- ✅ Seguimiento del estado de hallazgos
- ✅ Interfaz móvil optimizada
- ✅ Carga múltiple de archivos

### Para Encargados/Administradores
- 📊 **Dashboard completo** con métricas y gráficos
- 📈 **Análisis de tendencias** por área, modelo, usuario
- 📋 **Gestión de estados**: Activo, Cuarentena, Scrap
- 💰 **Control de costos** de scrap con valores monetarios
- 📤 **Exportación avanzada** a Excel con formato profesional
- 🔍 **Filtros múltiples** y búsqueda avanzada

### Flujo de Trabajo
1. **Registro** → Usuario registra hallazgo con evidencias
2. **Revisión** → Encargado revisa y clasifica
3. **Acción** → Retrabajo, Cuarentena o Scrap
4. **Seguimiento** → Monitoreo hasta resolución
5. **Análisis** → Reportes y métricas de calidad

## 🔧 Configuración

### Variables de Entorno
```env
# Base de datos
DB_HOST=hallazgos_db
DB_NAME=hallazgos
DB_USER=usuario
DB_PASS=secreto

# Aplicación
APP_DEBUG=false
APP_ENV=production
```

### Puertos
- **80**: Aplicación web
- **3306**: MySQL
- **8080**: PhpMyAdmin

## 📊 Base de Datos

### Tablas Principales
- `hallazgos` - Registro principal de hallazgos
- `usuarios` - Gestión de usuarios del sistema
- `hallazgos_defectos` - Detalles de defectos encontrados
- `hallazgos_evidencias` - Evidencias fotográficas
- `scrap_records` - Registro de elementos enviados a scrap

## 🚀 Desarrollo

### Comandos Útiles
```bash
# Iniciar desarrollo
docker-compose up -d

# Ver logs
docker-compose logs -f

# Reiniciar servicios
docker-compose restart

# Acceder al contenedor
docker exec -it hallazgos_web bash

# Backup de base de datos
docker exec hallazgos_db mysqldump -u usuario -psecreto hallazgos > backup.sql
```

## 📈 Métricas y Análisis

El sistema proporciona análisis detallados incluyendo:

- **Por Área**: Plasma, Prensas, Beam Welder, etc.
- **Por Modelo**: Análisis de productos específicos
- **Por Usuario**: Rendimiento del personal
- **Por Defecto**: Tipos de problemas más comunes
- **Costos de Scrap**: Impacto económico de pérdidas
- **Tendencias Temporales**: Evolución de la calidad

## 🔒 Seguridad

- ✅ Autenticación basada en sesiones
- ✅ Validación de archivos subidos
- ✅ Protección contra inyección SQL
- ✅ Sanitización de datos de entrada
- ✅ Control de acceso por roles

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto es propiedad de Brazos Trailers y está destinado para uso interno.

## 📞 Soporte

Para soporte técnico o consultas:
- **Desarrollado por**: GitHub Copilot
- **Empresa**: Brazos Trailers
- **Repositorio**: https://github.com/brazostrailers/hallazgos

---

**Nota**: Este sistema fue desarrollado específicamente para las necesidades de control de calidad de Brazos Trailers, con funcionalidades adaptadas a procesos industriales de manufactura.
