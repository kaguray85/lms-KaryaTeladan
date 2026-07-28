const APP_BASE = '/lms-KaryaTeladan';
const API_BASE = `${APP_BASE}/api`;

async function apiRequest(endpoint, options = {}) {
  const config = {
    method: options.method || 'GET',
    credentials: 'include',
    headers: options.headers || {},
  };

  if (options.body instanceof FormData) {
    config.body = options.body;
  } else if (options.body) {
    config.headers['Content-Type'] = 'application/json';
    config.body = JSON.stringify(options.body);
  }

  const response = await fetch(`${API_BASE}${endpoint}`, config);
  let result;

  try {
    result = await response.json();
  } catch (error) {
    result = {
      status: false,
      message: 'Response server tidak valid.',
      data: [],
      errors: [],
    };
  }

  if (!response.ok || result.status === false) {
    const message = result.message || 'Request gagal.';
    const customError = new Error(message);
    customError.status = response.status;
    customError.response = result;
    throw customError;
  }

  return result;
}
