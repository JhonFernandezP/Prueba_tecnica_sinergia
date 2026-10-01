/**
 * Módulo de Comunicación con la API RESTful (Fetch API Wrapper)
 * Gestiona autenticación JWT, cabeceras, serialización y manejo de errores
 */

const API_BASE_URL = (() => {
  // Detecta automáticamente si el proyecto se encuentra en /GestionPacientes/api o /api
  const pathname = window.location.pathname;
  if (pathname.includes('/GestionPacientes')) {
    return '/GestionPacientes/api';
  }
  return '/api';
})();

const StorageKey = {
  TOKEN: 'gp_auth_token',
  USER: 'gp_auth_user'
};

const ApiService = {
  /**
   * Obtiene el token JWT almacenado
   */
  getToken() {
    return localStorage.getItem(StorageKey.TOKEN);
  },

  /**
   * Guarda el token JWT
   */
  setToken(token) {
    localStorage.setItem(StorageKey.TOKEN, token);
  },

  /**
   * Obtiene el usuario autenticado almacenado
   */
  getUser() {
    const user = localStorage.getItem(StorageKey.USER);
    try {
      return user ? JSON.parse(user) : null;
    } catch (e) {
      return null;
    }
  },

  /**
   * Guarda el usuario autenticado
   */
  setUser(user) {
    localStorage.setItem(StorageKey.USER, JSON.stringify(user));
  },

  /**
   * Limpia la sesión
   */
  clearAuth() {
    localStorage.removeItem(StorageKey.TOKEN);
    localStorage.removeItem(StorageKey.USER);
  },

  /**
   * Indica si hay un usuario autenticado
   */
  isAuthenticated() {
    return !!this.getToken();
  },

  /**
   * Método principal para realizar peticiones HTTP con Fetch
   */
  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint.startsWith('/') ? endpoint : '/' + endpoint}`;
    
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(options.headers || {})
    };

    const token = this.getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const config = {
      ...options,
      headers
    };

    if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
      config.body = JSON.stringify(config.body);
    }

    try {
      const response = await fetch(url, config);
      let data;
      
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        data = await response.json();
      } else {
        const text = await response.text();
        data = { message: text || 'Respuesta no formateada' };
      }

      if (!response.ok) {
        // Manejo de error 401 (Sesión expirada o token inválido)
        if (response.status === 401 && !endpoint.includes('/login')) {
          this.clearAuth();
          window.dispatchEvent(new CustomEvent('auth:unauthorized'));
        }

        const error = new Error(data.message || `Error HTTP ${response.status}`);
        error.status = response.status;
        error.data = data;
        error.errors = data.errors || null;
        throw error;
      }

      return data;
    } catch (err) {
      if (err.name === 'TypeError' && err.message.includes('fetch')) {
        const netErr = new Error('No se pudo conectar con el servidor backend. Verifique que Apache y MySQL estén activos.');
        netErr.status = 0;
        throw netErr;
      }
      throw err;
    }
  },

  get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = query ? `${endpoint}?${query}` : endpoint;
    return this.request(url, { method: 'GET' });
  },

  post(endpoint, body = {}) {
    return this.request(endpoint, { method: 'POST', body });
  },

  put(endpoint, body = {}) {
    return this.request(endpoint, { method: 'PUT', body });
  },

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
};
