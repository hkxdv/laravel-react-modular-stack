import { execSync } from "child_process";
import { log } from "./log";

export const runCommand = (command: string, description: string) => {
  try {
    log(`Ejecutando: ${description}`, "step");
    execSync(command, { stdio: "inherit" });
    log(`${description} completado`, "success");
  } catch (error) {
    log(`Error ejecutando: ${description}`, "error");
    throw error;
  }
};

export const runCommandEnv = (
  command: string,
  description: string,
  extraEnv: Record<string, string>,
) => {
  try {
    log(`Ejecutando: ${description}`, "step");
    execSync(command, { stdio: "inherit", env: { ...process.env, ...extraEnv } });
    log(`${description} completado`, "success");
  } catch (error) {
    log(`Error ejecutando: ${description}`, "error");
    throw error;
  }
};