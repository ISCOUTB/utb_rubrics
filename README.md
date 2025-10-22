<div align="center">

# 🎓 UTB Rubrics - Plugin de Calificación Moodle

<img src="https://img.shields.io/badge/Moodle-4.0%2B-orange?style=for-the-badge&logo=moodle" alt="Moodle Version">
<img src="https://img.shields.io/badge/PHP-7.4%20to%208.2-777BB4?style=for-the-badge&logo=php" alt="PHP Version">
<img src="https://img.shields.io/badge/License-GPL%20v3-blue?style=for-the-badge" alt="License">
<img src="https://img.shields.io/github/v/release/ISCOUTB/utb_rubrics?style=for-the-badge" alt="Latest Release">

**Sistema integral de evaluación por rúbricas para la Universidad Tecnológica de Bolívar**

*Evaluación estructurada basada en resultados de aprendizaje con soporte bilingüe*

[🚀 Instalación](#-instalación) • [📖 Documentación](#-características) • [🧪 Pruebas](#-calidad-y-pruebas) • [🤝 Contribuir](#-contribuciones)

</div>

---

## 📋 Descripción

**UTB Rubrics** es un plugin avanzado de calificación para Moodle que implementa el sistema de rúbricas de la **Universidad Tecnológica de Bolívar (UTB)**. Diseñado específicamente para la evaluación basada en resultados de aprendizaje, ofrece una experiencia completa y estructurada para docentes y estudiantes.

### 🎯 **Propósito**
Facilitar la evaluación académica mediante un sistema de rúbricas que permite:
- Evaluación objetiva y consistente
- Retroalimentación detallada para estudiantes
- Seguimiento del progreso académico
- Alineación con estándares institucionales

---

## ✨ Características

### 🔥 **Funcionalidades Principales**

| 🎯 **Evaluación por Resultados** | 📊 **Indicadores Multinivel** | 🌐 **Soporte Bilingüe** |
|:---:|:---:|:---:|
| Sistema basado en Student Outcomes | Múltiples niveles de desempeño | Español e Inglés completos |

| 📱 **Diseño Responsivo** | 🔗 **Integración Total** | 🛡️ **Calidad Asegurada** |
|:---:|:---:|:---:|
| Funciona en todos los dispositivos | Totalmente integrado con Moodle | 55+ pruebas unitarias |

### 🛠️ **Características Técnicas**

- ✅ **Compatibilidad**: Moodle 4.0+
- ✅ **Base de Datos**: MySQL 5.7+, PostgreSQL 10+, MariaDB 10.3+
- ✅ **PHP**: Versiones 7.4 a 8.2
- ✅ **Arquitectura**: MVC con patrones de diseño modernos
- ✅ **Seguridad**: Validación completa y protección CSRF
- ✅ **Performance**: Optimizado para gran escala

---

## 🚀 Instalación

### 📦 **Método 1: Instalación Automática (Recomendado)**

1. **Descarga** el archivo ZIP desde [Releases](https://github.com/ISCOUTB/utb_rubrics/releases/latest)
2. **Accede** a tu sitio Moodle como administrador
3. **Navega** a: `Administración del sitio > Plugins > Instalar plugins`
4. **Sube** el archivo ZIP descargado
5. **Confirma** la instalación siguiendo las instrucciones

### 🔧 **Método 2: Instalación Manual**

```bash
# Descargar y extraer en el directorio correcto
cd /path/to/moodle/
wget https://github.com/ISCOUTB/utb_rubrics/releases/latest/download/moodle-gradingform_utbrubrics-X.X.X.zip
unzip moodle-gradingform_utbrubrics-X.X.X.zip
```

Luego visita: `Administración del sitio > Notificaciones`

### ⚡ **Verificación de Instalación**

Después de la instalación, verifica que:
- [ ] El plugin aparece en `Administración > Plugins > Resumen de plugins`
- [ ] Las tablas de base de datos se crearon correctamente
- [ ] Los archivos de idioma están disponibles
- [ ] No hay errores en los logs de Moodle

---

## 💡 Uso

### 📝 **Para Profesores**

1. **Crear Rúbrica**:
- Ve a tu curso → Calificaciones → Configurar → Métodos de calificación avanzada
    - Selecciona **"Rúbricas UTB"** (en español) o **"UTB Rubrics"** (en inglés)

2. **Configurar Evaluación**:
   - Define el **Student Outcome** para la actividad

3. **Evaluar Estudiantes**:
   - Accede a la actividad para calificar
   - Utiliza la rúbrica para evaluación estructurada
   - Proporciona retroalimentación específica

### 👨‍🎓 **Para Estudiantes**

- **Visualizar Rúbrica**: Conoce los criterios antes de entregar
- **Recibir Retroalimentación**: Entiende tu desempeño por cada criterio
- **Seguir Progreso**: Monitorea tu evolución académica

---

## 🧪 Calidad y Pruebas

### 🔍 **Suite de Pruebas Completa**

```
📊 Estadísticas de Pruebas:
├── 55+ Pruebas Unitarias
├── 230+ Aserciones
├── 6 Archivos de Prueba
└── 100% Cobertura Crítica
```

| **Archivo** | **Pruebas** | **Descripción** |
|-------------|-------------|-----------------|
| `controller_test.php` | 6 | Controladores y formularios |
| `database_test.php` | 10 | Integridad de base de datos |
| `helper_functions_test.php` | 11 | Funciones auxiliares |
| `lang_test.php` | 10 | Archivos de idioma |
| `renderer_test.php` | 14 | Renderizado y vistas |
| `workflow_test.php` | 4 | Flujos de trabajo completos |

### 🔄 **CI/CD Automatizado**

- ✅ **GitHub Actions**: Pruebas automáticas en cada commit
- ✅ **Múltiples Entornos**: PHP 8.0, 8.1 y 8.2
- ✅ **Bases de Datos**: PostgreSQL 15 y MariaDB 10
- ✅ **Releases Automáticos**: Versionado semántico

---

## 🛠️ Desarrollo

### 🏗️ **Arquitectura del Plugin**

```
utbrubrics/
├── 📁 db/                 # Esquema de base de datos
├── 📁 lang/              # Archivos de idioma (es/en)
├── 📁 tests/             # Suite de pruebas PHPUnit
├── 📄 lib.php           # Funciones principales
├── 📄 renderer.php      # Capa de presentación
├── 📄 edit.php         # Editor de rúbricas
└── 📄 version.php      # Metadatos del plugin
```

### 📊 **Base de Datos**

El plugin utiliza las siguientes tablas:

- `gradingform_utb_so` - Student Outcomes
- `gradingform_utb_ind` - Indicadores de evaluación de cada SO
- `gradingform_utb_lvl` - Niveles de desempeño de cada indicador por cada SO
- `gradingform_utb_eval` - Evaluaciones realizadas

---

## 🤝 Contribuciones

### 👥 **Equipo de Desarrollo**

<table align="center">
<tr>
  <td align="center">
    <b>Isaac Sanchez</b><br/>
    <i>🚀 Líder de Desarrollo</i><br/>
  </td>
  <td align="center">
    <b>Santiago Orejuela</b><br/>
    <i>💻 Desarrollador Principal</i><br/>
  </td>
</tr>
<tr>
  <td align="center">
    <b>Luis Diaz</b><br/>
    <i>💻 Desarrollador Principal</i><br/>
  </td>
  <td align="center">
    <b>Maria Valentina</b><br/>
    <i>💻 Desarrolladora Principal</i><br/>
  </td>
</tr>
</table>

### 🔧 **Cómo Contribuir**

1. 🍴 **Fork** el repositorio
2. 🌿 **Crea** una rama: `git checkout -b feature/nueva-funcionalidad`
3. 💾 **Commit** cambios: `git commit -am 'Agrega nueva funcionalidad'`
4. 📤 **Push** a la rama: `git push origin feature/nueva-funcionalidad`
5. 🎯 **Abre** un Pull Request

### 🐛 **Reportar Problemas**

¿Encontraste un bug? [🐛 Reporta aquí](https://github.com/ISCOUTB/utb_rubrics/issues)

---

## 📄 Licencia

Este proyecto está licenciado bajo la **GNU General Public License v3.0**

```
Copyright (C) 2025 Universidad Tecnológica de Bolívar (UTB)

Este programa es software libre: puedes redistribuirlo y/o modificarlo
bajo los términos de la Licencia Pública General GNU publicada por
la Free Software Foundation, ya sea la versión 3 de la Licencia, o
(a tu elección) cualquier versión posterior.
```

📜 [Ver licencia completa](LICENSE)

---

<div align="center">

## 🌟 ¿Te gustó el proyecto?

**¡Dale una estrella ⭐ si este plugin te fue útil!**

[![GitHub stars](https://img.shields.io/github/stars/ISCOUTB/utb_rubrics?style=social)](https://github.com/ISCOUTB/utb_rubrics/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/ISCOUTB/utb_rubrics?style=social)](https://github.com/ISCOUTB/utb_rubrics/forks)

---

*Desarrollado con ❤️ para la Universidad Tecnológica de Bolívar*

**© 2025 UTB - Todos los derechos reservados**

</div>