// Central Axios instance (add axios to dependencies if not present)
// Not yet imported anywhere; safe scaffolding.
import axios from 'axios'

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL + '/api',
  timeout: 12000,
  headers: { Accept: 'application/json' }
})

apiClient.interceptors.request.use(cfg => {
  const token = localStorage.getItem('access_token')
  if (token) cfg.headers.Authorization = 'Bearer ' + token
  return cfg
})

apiClient.interceptors.response.use(
  r => r,
  err => {
    const status = err.response?.status
    const data = err.response?.data
    return Promise.reject({ status, data, message: data?.message || err.message })
  }
)
