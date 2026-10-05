/**
 * MyFlix API Client
 */
const API = {
  csrfToken: null,

  async init() {
    try {
      const res = await this.get('/api/auth/me');
      if (res && res.csrf_token) {
        this.csrfToken = res.csrf_token;
      }
      return res;
    } catch (e) {
      console.warn('API init error:', e);
      return null;
    }
  },

  async request(endpoint, options = {}) {
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers || {})
    };

    if (this.csrfToken) {
      headers['X-CSRF-Token'] = this.csrfToken;
    }

    const config = {
      method: options.method || 'GET',
      headers,
      ...options
    };

    if (options.body && typeof options.body === 'object') {
      config.body = JSON.stringify(options.body);
    }

    const response = await fetch(endpoint, config);
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.error || `HTTP error ${response.status}`);
    }

    return data;
  },

  get(endpoint, params = {}) {
    const url = new URL(endpoint, window.location.origin);
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
    return this.request(url.pathname + url.search, { method: 'GET' });
  },

  post(endpoint, body = {}) {
    return this.request(endpoint, { method: 'POST', body });
  },

  put(endpoint, body = {}) {
    return this.request(endpoint, { method: 'PUT', body });
  },

  delete(endpoint, body = {}) {
    return this.request(endpoint, { method: 'DELETE', body });
  }
};
