# Code Challenger API

Aplicación PHP 8 con Symfony, diseñada para optimizar reservas y calcular estadísticas.

---

## 📦 Requisitos

* Docker ≥ 20.x
* Docker Compose ≥ 2.x
* Make ≥ 4.x

---

## 🐳 Instalación y Entorno de Desarrollo

1. Construir la imagen Docker y dependencias:

```bash
make start
```

2. Levantar los contenedores:

```bash
make docker-up
```

3. Acceder al bash del contenedor (opcional):

```bash
make bash
```

4. Detener contenedores:

```bash
make docker-down
```

5. Reiniciar contenedores:

```bash
make docker-restart
```

---

## 🧪 Tests

Ejecutar todos los tests:

```bash
make test
```

Tests unitarios únicamente:

```bash
make test-unit
```

Tests de integración únicamente:

```bash
make test-integration
```

Generar reporte de cobertura HTML:

```bash
make test-coverage
```

---

## 🔍 Calidad de Código

Verificar estilo de código:

```bash
make cs-check
```

Corregir estilo de código automáticamente:

```bash
make cs-fix
```

Ejecutar análisis estático con PHPStan:

```bash
make phpstan
```

Ejecutar Rector (dry-run):

```bash
make rector
```

Aplicar cambios con Rector:

```bash
make rector-fix
```

Pipeline completo de calidad:

```bash
make quality
```

Aplicar correcciones automáticas de calidad:

```bash
make quality-fix
```

---

## 🌐 API

La aplicación expone los siguientes endpoints:

| Método | Ruta      | Descripción                               |
| ------ | --------- | ----------------------------------------- |
| POST   | /stats    | Given a list of booking requests, return the average, minimum, and maximum profit per night taking into account all the booking requests in the payload. The concept “profit per night” follows this calculation:|
| POST   | /maximize | Given a list of booking requests, return the best combination of requests that maximizes total profits.|

Ejemplo usando `curl`:

```bash
make api-test-stats
make api-test-maximize
make api-test-complex
```

---

## 📖 Documentación Interactiva

### Swagger UI

```bash
make api-docs
```

Accede a [http://localhost:8081](http://localhost:8081)

---

## 🧹 Limpiar Cache y Archivos Temporales

```bash
make clean
make cache-clear
```

---

## 🚀 Pipeline CI

Instalar dependencias, ejecutar tests y chequear calidad de código:

```bash
make ci
```
