const API = import.meta.env.VITE_API_URL || "http://localhost:8000/api";
export async function api(path, options = {}) {
  const token = localStorage.getItem("warehouse_token");
  const res = await fetch(`${API}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  });
  if (res.status === 204) return null;
  const data = await res
    .json()
    .catch(() => ({ message: "Unexpected server response" }));
  if (!res.ok) {
    const first = data.errors ? Object.values(data.errors).flat()[0] : null;
    throw new Error(first || data.message || `HTTP ${res.status}`);
  }
  return data;
}
export const money = (n) =>
  new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(n || 0));
export const num = (n) =>
  new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 3 }).format(
    Number(n || 0),
  );
