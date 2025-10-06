import { existsSync, readFileSync, writeFileSync } from "fs";

export const generateAppKey = (): string => {
  const key = Buffer.from(
    Array.from({ length: 32 }, () => Math.floor(Math.random() * 256)),
  ).toString("base64");
  return `base64:${key}`;
};

export const parseEnvFile = (path: string): Record<string, string> => {
  try {
    if (!existsSync(path)) return {};
    const content = readFileSync(path, "utf8");
    const env: Record<string, string> = {};
    for (const rawLine of content.split(/\r?\n/)) {
      const line = rawLine.trim();
      if (!line || line.startsWith("#")) continue;
      const match = line.match(/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/);
      if (!match) continue;
      let value = match[2].trim();
      if (
        (value.startsWith('"') && value.endsWith('"')) ||
        (value.startsWith("'") && value.endsWith("'"))
      ) {
        value = value.slice(1, -1);
      }
      env[match[1]] = value;
    }
    return env;
  } catch {
    return {};
  }
};

export const createEnvUsers = async (ask: (query: string) => Promise<string>): Promise<void> => {
  // Solicita y crea .env.users
  const name = (await ask("\x1b[37m→ Nombre (Admin): \x1b[0m")) || "Admin";
  const email = (await ask("\x1b[37m→ Email (admin@domain.com): \x1b[0m")) || "admin@domain.com";
  const password = (await ask("\x1b[37m→ Contraseña (AdminPass123): \x1b[0m")) || "AdminPass123";

  const envUsersContent = `# Configuración de usuarios del sistema
# Este archivo debe mantenerse seguro y no debe ser incluido en el control de versiones

# Número máximo de usuarios a crear (1-50)
USER_STAFF_MAX=1

# Usuario 1
USER_STAFF_1_NAME="${name}"
USER_STAFF_1_EMAIL="${email}"
USER_STAFF_1_PASSWORD="${password}"
USER_STAFF_1_ROLE="ADMIN"
USER_STAFF_1_FORCE_PASSWORD_UPDATE=true
`;

  writeFileSync(".env.users", envUsersContent);
};
