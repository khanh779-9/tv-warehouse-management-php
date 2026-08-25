import React, { createContext, useContext, useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import {
  BrowserRouter,
  Navigate,
  NavLink,
  Route,
  Routes,
  useLocation,
} from "react-router-dom";
import { api } from "./api";
import "./styles.css";
import Dashboard from "./pages/Dashboard";
import Products from "./pages/Products";
import Inventory from "./pages/Inventory";
import DeviceSerials from "./pages/DeviceSerials";
import Reservations from "./pages/Reservations";
import Orders from "./pages/Orders";
import Transfers from "./pages/Transfers";
import StockCounts from "./pages/StockCounts";
import ServiceOps from "./pages/ServiceOps";
import MasterData from "./pages/MasterData";
import Reports from "./pages/Reports";
import AuditLogs from "./pages/AuditLogs";

const Auth = createContext(null);
export const useAuth = () => useContext(Auth);
function AuthProvider({ children }) {
  const [user, setUser] = useState(null),
    [loading, setLoading] = useState(true);
  useEffect(() => {
    if (!localStorage.getItem("warehouse_token")) {
      setLoading(false);
      return;
    }
    api("/auth/me")
      .then(setUser)
      .catch(() => localStorage.removeItem("warehouse_token"))
      .finally(() => setLoading(false));
  }, []);
  const login = async (email, password) => {
    const d = await api("/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    });
    localStorage.setItem("warehouse_token", d.token);
    setUser(d.user);
  };
  const logout = async () => {
    try {
      await api("/auth/logout", { method: "POST" });
    } finally {
      localStorage.removeItem("warehouse_token");
      setUser(null);
    }
  };
  return (
    <Auth.Provider value={{ user, login, logout }}>
      {loading ? <div className="center">Loading…</div> : children}
    </Auth.Provider>
  );
}
function Login() {
  const { login } = useAuth();
  const [email, setEmail] = useState("admin@warehouse.local"),
    [password, setPassword] = useState("password"),
    [error, setError] = useState("");
  const submit = async (e) => {
    e.preventDefault();
    setError("");
    try {
      await login(email, password);
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <div className="login-shell">
      <form className="login-card" onSubmit={submit}>
        <div className="brand-mark">TV</div>
        <h1>TV Warehouse WMS</h1>
        <p>Laravel + React portfolio for television distribution</p>
        {error && <div className="alert error">{error}</div>}
        <label>
          Email
          <input value={email} onChange={(e) => setEmail(e.target.value)} />
        </label>
        <label>
          Password
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </label>
        <button>Sign in</button>
        <small>Demo: admin@warehouse.local / password</small>
      </form>
    </div>
  );
}
const baseNav = [
  ["/", "Dashboard", "◫"],
  ["/products", "TV Catalog", "▦"],
  ["/inventory", "Inventory", "≋"],
  ["/serials", "TV Serials", "⌁"],
  ["/reservations", "Reservations", "⊙"],
  ["/purchase-orders", "Purchase Orders", "↓"],
  ["/sales-orders", "Sales Orders", "↑"],
  ["/transfers", "Transfers", "⇄"],
  ["/stock-counts", "Stock Counts", "✓"],
  ["/service", "Returns & Warranty", "◇"],
  ["/master-data", "Master Data", "◎"],
  ["/reports", "Reports", "▤"],
];
function Layout() {
  const { user, logout } = useAuth();
  const loc = useLocation();
  const nav = [
    ...baseNav,
    ...(["admin", "manager"].includes(user?.role)
      ? [["/audit", "Audit Logs", "☷"]]
      : []),
  ];
  return (
    <div className="app">
      <aside>
        <div className="logo">
          <b>TV</b>
          <span>TV Warehouse WMS</span>
        </div>
        <nav>
          {nav.map(([to, label, icon]) => (
            <NavLink key={to} to={to} end={to === "/"}>
              <span>{icon}</span>
              {label}
            </NavLink>
          ))}
        </nav>
        <div className="side-user">
          <div>
            <strong>{user?.name}</strong>
            <small>{user?.role}</small>
          </div>
          <button className="ghost" onClick={logout}>
            Logout
          </button>
        </div>
      </aside>
      <main>
        <header>
          <div>
            <h2>
              {nav.find((n) => n[0] === loc.pathname)?.[1] ||
                "TV Warehouse WMS"}
            </h2>
            <span className="muted">
              TV distribution & warehouse operations
            </span>
          </div>
          <span className="badge">Laravel API connected</span>
        </header>
        <section className="content">
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/products" element={<Products />} />
            <Route path="/inventory" element={<Inventory />} />
            <Route path="/serials" element={<DeviceSerials />} />
            <Route path="/reservations" element={<Reservations />} />
            <Route
              path="/purchase-orders"
              element={<Orders type="purchase" />}
            />
            <Route path="/sales-orders" element={<Orders type="sales" />} />
            <Route path="/transfers" element={<Transfers />} />
            <Route path="/stock-counts" element={<StockCounts />} />
            <Route path="/service" element={<ServiceOps />} />
            <Route path="/master-data" element={<MasterData />} />
            <Route path="/reports" element={<Reports />} />
            <Route path="/audit" element={<AuditLogs />} />
            <Route path="*" element={<Navigate to="/" />} />
          </Routes>
        </section>
      </main>
    </div>
  );
}
function App() {
  const { user } = useAuth();
  return user ? <Layout /> : <Login />;
}
createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <BrowserRouter>
      <AuthProvider>
        <App />
      </AuthProvider>
    </BrowserRouter>
  </React.StrictMode>,
);
