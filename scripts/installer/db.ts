import { existsSync, unlinkSync } from "fs";
import { join } from "path";
import { log } from "./log";

export const cleanupSqliteArtifacts = () => {
  const expected = join("database", "database.sqlite");
  const legacy = join("backend", "database.sqlite");

  if (existsSync(legacy)) {
    log("Eliminando archivo SQLite en ruta antigua: backend/database.sqlite", "warning");
    try {
      unlinkSync(legacy);
      log("Archivo antiguo eliminado", "success");
    } catch (e) {
      log("No se pudo eliminar el archivo antiguo", "error");
    }
  }

  if (existsSync(expected)) {
    log("Base de datos SQLite detectada en ruta correcta", "success");
  } else {
    log(
      "Advertencia: No se encontró database/database.sqlite; Artisan la creará automáticamente cuando sea necesario",
      "warning",
    );
  }
};
