import type { AxiosInstance } from 'axios';

/**
 * Extiende el objeto `global` de TypeScript para exponer la instancia de axios
 * configurada en toda la aplicación sin necesidad de importarla en cada archivo.
 */
declare global {
  interface Window {
    axios: AxiosInstance;
  }
}
