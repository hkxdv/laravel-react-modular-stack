#!/usr/bin/env bun

import { existsSync, copyFileSync, writeFileSync, readFileSync } from "fs";
import { log, logSection, createProgressBar } from "./installer/log";
import { generateAppKey, parseEnvFile, createEnvUsers } from "./installer/env";
import { runCommand } from "./installer/run";
import { cleanupSqliteArtifacts } from "./installer/db";
import { createInterface } from "readline";

const main = async () => {
  try {
    // Header
    console.log("\x1b[90mLaravel React Modular Stack - Configuración automática\x1b[0m\n");

    const progress = createProgressBar(9, "Progreso de instalación");

    // 1. Verificar que estamos en el directorio correcto
    progress.update(1, "Verificando estructura");
    if (!existsSync("package.json") || !existsSync("backend") || !existsSync("frontend")) {
      log(
        "No se detectó la estructura del proyecto. Asegúrate de estar en el directorio raíz.",
        "error",
      );
      process.exit(1);
    }
    log("\nEstructura del proyecto verificada", "success");

    // 2. Crear archivo .env.local si no existe
    progress.update(2, "Configurando entorno");
    if (!existsSync(".env.local")) {
      if (existsSync(".env.local.example")) {
        copyFileSync(".env.local.example", ".env.local");
        log("Archivo .env.local creado desde plantilla", "success");

        // Generar APP_KEY
        const envContent = readFileSync(".env.local", "utf8");
        const appKey = generateAppKey();
        const updatedContent = envContent.replace("APP_KEY=", `APP_KEY=${appKey}`);
        writeFileSync(".env.local", updatedContent);
        log("Clave de aplicación generada", "success");
      } else {
        log("No se encontró .env.local.example", "error");
        process.exit(1);
      }
    } else {
      log("Archivo .env.local ya existe", "warning");
    }

    // 3. Crear archivo .env.users si no existe
    progress.update(3, "Configurando usuarios");
    if (!existsSync(".env.users")) {
      const rl = createInterface({ input: process.stdin, output: process.stdout });
      const ask = (query: string) => new Promise<string>((resolve) => rl.question(query, resolve));
      await createEnvUsers(ask);
      rl.close();
    } else {
      log("Archivo .env.users ya existe", "warning");
    }

    // 4. Instalar dependencias
    logSection("Instalación de Dependencias");
    progress.update(4, "Instalando dependencias");
    runCommand("bun install", "Dependencias del workspace");
    runCommand("bun run i:be", "Dependencias del backend");
    runCommand("bun run i:fe", "Dependencias del frontend");

    // 5. Configurar base de datos (Laravel Artisan la creará automáticamente)
    progress.update(5, "Configurando base de datos");
    log("La base de datos SQLite será creada automáticamente por Laravel", "info");

    // 6. Ejecutar migraciones y seeders (no interactivos y forzados)
    logSection("Configuración de Base de Datos");
    progress.update(6, "Ejecutando migraciones");
    runCommand(
      "php backend/artisan migrate --force --no-interaction",
      "Migraciones de base de datos",
    );
    runCommand("php backend/artisan db:seed --force --no-interaction", "Datos iniciales");

    // 7. Verificar y unificar ruta de base de datos SQLite
    progress.update(7, "Verificando ruta de base de datos");
    cleanupSqliteArtifacts();

    // 8. Generar rutas Ziggy
    progress.update(8, "Generando rutas");
    runCommand("bun run ziggy", "Rutas del frontend");

    // 9. Limpiar cachés y optimizar autoload
    progress.update(9, "Optimizando");
    runCommand("bun run clear:all", "Limpieza y optimización");

    // Success message
    logSection("Instalación Completada");

    console.log("\x1b[32m✓ Instalación completada exitosamente\x1b[0m\n");

    console.log("\x1b[1m\x1b[37mPara iniciar el proyecto:\x1b[0m");
    console.log("\x1b[90m  $ \x1b[36mbun dev\x1b[0m\n");

    console.log("\x1b[1m\x1b[37mCredenciales de acceso:\x1b[0m");
    const userEmail = parseEnvFile(".env.users")["USER_STAFF_1_EMAIL"] || "admin@example.com";
    console.log(`\x1b[90m  Email:      \x1b[37m${userEmail}\x1b[0m`);
    console.log(`\x1b[90m  Contraseña: \x1b[37mLa que configuraste\x1b[0m\n`);

    console.log("\x1b[1m\x1b[37mURLs del proyecto:\x1b[0m");
    console.log("\x1b[90m  Backend:  \x1b[37mhttp://localhost:8080\x1b[0m");
    console.log("\x1b[90m  Frontend: \x1b[37mhttp://localhost:5173\x1b[0m\n");

    console.log("\x1b[90m" + "─".repeat(50) + "\x1b[0m");
    console.log("\x1b[1m\x1b[37mGestión de usuarios adicionales:\x1b[0m");
    console.log("\x1b[90m  1. Edita \x1b[37m.env.users\x1b[90m con la convención:\x1b[0m");
    console.log('\x1b[90m     USER_STAFF_{N}_NAME="Nombre"\x1b[0m');
    console.log('\x1b[90m     USER_STAFF_{N}_EMAIL="email@domain.com"\x1b[0m');
    console.log('\x1b[90m     USER_STAFF_{N}_PASSWORD="password"\x1b[0m');
    console.log('\x1b[90m     USER_STAFF_{N}_ROLE="ROLE" (opcional)\x1b[0m');
    console.log("\x1b[90m  2. Ejecuta: \x1b[36mbun run seed:users\x1b[0m\n");
  } catch (error) {
    log("Error durante la instalación", "error");
    console.error(error);
    process.exit(1);
  }
};

main();
